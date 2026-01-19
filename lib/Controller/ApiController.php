<?php

declare(strict_types=1);

namespace OCA\Assets\Controller;

use OCA\Assets\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;

class ApiController extends Controller
{
    private IRootFolder $rootFolder;
    private ?string $userId = null;

    public function __construct(
        IRequest $request,
        IRootFolder $rootFolder,
        IUserSession $userSession
    ) {
        parent::__construct(Application::APP_ID, $request);
        $this->rootFolder = $rootFolder;
        $user = $userSession->getUser();
        $this->userId = $user ? $user->getUID() : null;
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listAssets(string $path = '/'): DataResponse
    {
        try {
            if (!$this->userId) {
                return new DataResponse(['error' => 'User not logged in'], 401);
            }

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
                    if (empty($preview) && $this->isImage($sub)) {
                        $preview = $this->generatePreviewUrl($sub);
                    }
                }

                if (empty($preview)) {
                    $preview = '/core/img/filetypes/folder.svg'; 
                }

                $folders[] = [
                    'name' => $child->getName(),
                    'path' => $child->getPath(),
                    'preview' => $preview,
                    'fileCount' => $fileCount,
                    'isFolder' => true
                ];
            }

            return new DataResponse(array_values($folders));

        } catch (\Throwable $e) {
            \OCP\Util::writeLog(Application::APP_ID, $e->getMessage() . ' Trace: ' . $e->getTraceAsString(), \OCP\Util::ERROR);
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFiles(string $path = '/'): DataResponse
    {
        try {
            if (!$this->userId) {
                return new DataResponse(['error' => 'User not logged in'], 401);
            }

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
                    $preview = '/core/img/filetypes/file.svg';
                }

                $files[] = [
                    'id' => $child->getId(),
                    'name' => $child->getName(),
                    'size' => $this->humanFilesize($child->getSize()),
                    'ext' => strtolower(pathinfo($child->getName(), PATHINFO_EXTENSION)),
                    'url' => '/remote.php/webdav' . $child->getPath(),
                    'preview' => $preview,
                    'mime' => $child->getMimetype()
                ];
            }

            return new DataResponse($files);

        } catch (\Throwable $e) {
            \OCP\Util::writeLog(Application::APP_ID, $e->getMessage(), \OCP\Util::ERROR);
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function createFolder(string $path, string $name): DataResponse
    {
        try {
            if (!$this->userId) {
                return new DataResponse(['error' => 'User not logged in'], 401);
            }

            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            $fullPath = $path . '/' . $name;
            $fullPath = trim($fullPath, '/');
            
            if (!$userFolder->nodeExists($fullPath)) {
                $userFolder->newFolder($fullPath);
                return new DataResponse(['status' => 'success', 'path' => $fullPath]);
            } else {
                return new DataResponse(['error' => 'Folder already exists'], 409);
            }
        } catch (\Throwable $e) {
            \OCP\Util::writeLog(Application::APP_ID, $e->getMessage(), \OCP\Util::ERROR);
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function uploadFile(string $path): DataResponse
    {
        try {
            if (!$this->userId) {
                return new DataResponse(['error' => 'User not logged in'], 401);
            }

            $userFolder = $this->rootFolder->getUserFolder($this->userId);
            
            if (!$userFolder->nodeExists($path)) {
                return new DataResponse(['error' => 'Target folder not found'], 404);
            }

            if (empty($_FILES['file'])) {
                return new DataResponse(['error' => 'No file data'], 400);
            }
            
            $uploadedFile = $_FILES['file'];
            $filename = $uploadedFile['name'];
            $tempPath = $uploadedFile['tmp_name'];
            $targetFolder = $userFolder->get($path);
            
            $fileNode = null;
            if ($targetFolder->nodeExists($filename)) {
                $fileNode = $targetFolder->get($filename);
            } else {
                $fileNode = $targetFolder->newFile($filename);
            }
            
            $content = file_get_contents($tempPath);
            $fileNode->putContent($content);
            
            return new DataResponse(['status' => 'success', 'file' => $filename]);

        } catch (\Throwable $e) {
            \OCP\Util::writeLog(Application::APP_ID, $e->getMessage(), \OCP\Util::ERROR);
            return new DataResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function downloadPackage(string $path): DataResponse
    {
         return new DataResponse(['status' => 'not_implemented_use_webdav']);
    }

    private function isImage($node): bool
    {
        $mime = $node->getMimetype();
        return strpos($mime, 'image/') === 0;
    }

    private function generatePreviewUrl($node): string
    {
        return '/core/preview?fileId=' . $node->getId() . '&x=400&y=400&a=true';
    }

    private function humanFilesize($bytes, $decimals = 2): string
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        // Fix for 0 bytes
        if ($bytes <= 0) return '0B';
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
