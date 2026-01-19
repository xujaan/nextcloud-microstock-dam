<?php
namespace OCA\Assets\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends Controller {
    private $rootFolder;
    private $userId;

    public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, IUserSession $userSession) {
        parent::__construct($AppName, $request);
        $this->rootFolder = $rootFolder;
        $this->userId = $userSession->getUser()->getUID();
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function listAssets($path = '/') {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            if (!$userFolder->nodeExists($path)) {
                return new DataResponse(['error' => 'Path not found'], 404);
            }
            
            $node = $userFolder->get($path);
            $children = $node->getDirectoryListing();
            $folders = [];

            foreach ($children as $child) {
                if ($child->getType() !== \OCP\Files\FileInfo::TYPE_DIR) continue;

                $preview = '';
                $fileCount = 0;
                $subChildren = $child->getDirectoryListing();
                
                foreach ($subChildren as $sub) {
                    $fileCount++;
                    // Find first image for preview
                    if (empty($preview) && $this->isImage($sub)) {
                        $preview = $this->generatePreviewUrl($sub);
                    }
                }

                // If no image found, use default icon
                if (empty($preview)) {
                    $preview = '/core/img/filetypes/folder.svg'; 
                }

                $folders[] = [
                    'name' => $child->getName(),
                    'path' => $child->getPath(), // Relative to user folder basically
                    'preview' => $preview,
                    'fileCount' => $fileCount,
                    'isFolder' => true
                ];
            }

            return new DataResponse(array_values($folders));

        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function getFiles($path = '/') {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            if (!$userFolder->nodeExists($path)) {
                return new DataResponse(['error' => 'Path not found'], 404);
            }

            $node = $userFolder->get($path);
            $children = $node->getDirectoryListing();
            $files = [];

            foreach ($children as $child) {
                if ($child->getType() === \OCP\Files\FileInfo::TYPE_DIR) continue;

                $preview = '';
                if ($this->isImage($child)) {
                    $preview = $this->generatePreviewUrl($child);
                } else {
                    $preview = '/core/img/filetypes/file.svg'; // Simplified
                }

                $files[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'size' => $this->human_filesize($child->getSize()),
                    'ext' => strtolower(pathinfo($child->getName(), PATHINFO_EXTENSION)),
                    'url' => '/remote.php/webdav' . $child->getPath(),
                    'preview' => $preview,
                    'mime' => $child->getMimetype()
                ];
            }

            return new DataResponse($files);

        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function createFolder($path, $name) {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            // Normalize path
            $fullPath = $path . '/' . $name;
            $fullPath = trim($fullPath, '/');
            
            if (!$userFolder->nodeExists($fullPath)) {
                $userFolder->newFolder($fullPath);
                return new DataResponse(['status' => 'success', 'path' => $fullPath]);
            } else {
                return new DataResponse(['error' => 'Folder already exists'], 409);
            }
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function uploadFile($path, $file = null) {
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            if (!$userFolder->nodeExists($path)) {
                return new DataResponse(['error' => 'Target folder not found'], 404);
            }

            // In Nextcloud AppFramework, uploaded files are usually available in $this->request->getUploadedFile('file')
            // But since we use generic $_FILES argument name matching in signature?
            // Let's use standard PHP $_FILES if the argument binding is tricky or rely on $request.
            // Actually, best practice is to depend on IRequest. 
            // In the signature `uploadFile($path)`, if we POST 'file', valid OC controller should handle it,
            // but let's look at how we get the file content.
            
            // We'll iterate $_FILES to be safe or use $this->request
            $files = $this->request->getUploadedFile('file'); // 'file' is the form field name
            
            if (!$files) {
                 return new DataResponse(['error' => 'No file uploaded'], 400);
            }

            $targetFolder = $userFolder->get($path);
            $filename = $files['name']; // Nextcloud UploadedFile wrapper array or object?
            // Actually getUploadedFile returns array-like or IUploadedFile?
            // Let's assume standard PHP $_FILES behavior first or check OCP docs.
            // Simplified: Use $this->request->getUploadedFile('file');
            // If it returns null, check $_FILES.
            
            // Fix: generic way
            if (empty($_FILES['file'])) {
                return new DataResponse(['error' => 'No file data'], 400);
            }
            
            $uploadedFile = $_FILES['file'];
            $filename = $uploadedFile['name'];
            $tempPath = $uploadedFile['tmp_name'];
            
            // Check if file exists, if so auto-rename or overwrite?
            // Let's overwrite or skip.
            // Safest: $targetFolder->newFile($filename);
            
            $fileNode = null;
            if ($targetFolder->nodeExists($filename)) {
                $fileNode = $targetFolder->get($filename);
            } else {
                $fileNode = $targetFolder->newFile($filename);
            }
            
            // Write content
            $content = file_get_contents($tempPath);
            $fileNode->putContent($content);
            
            return new DataResponse(['status' => 'success', 'file' => $filename]);

        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    private function isImage($node) {
        $mime = $node->getMimetype();
        return strpos($mime, 'image/') === 0;
    }

    private function generatePreviewUrl($node) {
        return '/core/preview?fileId=' . $node->getId() . '&x=400&y=400&a=true';
    }

    private function human_filesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
    
    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function downloadPackage($path) {
         // Return a download URL for the folder
         // Since implementing zip streaming is complex, we instruct frontend to use existing Nextcloud endpoints
         // or we can redirect
         $userFolder = $this->rootFolder->getUserFolder($this->userId);
         if (!$userFolder->nodeExists($path)) {
             return new DataResponse(['error' => 'Path not found'], 404);
         }
         
         $node = $userFolder->get($path);
         // This is a naive way, but Nextcloud usually allows downloading a folder via /index.php/apps/files/ajax/download.php?dir=...
         // But the proper way for an API might be different.
         // For now, let's just return the path so frontend can construct the link?
         // Or just don't implement this if frontend handles it via OC.generateUrl
         
         return new DataResponse(['status' => 'not_implemented_use_webdav']);
    }
}
