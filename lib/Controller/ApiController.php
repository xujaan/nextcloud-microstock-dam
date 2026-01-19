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
    public function __construct(IRequest $request)
    {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listAssets(string $path = '/'): DataResponse
    {
        return new DataResponse([
            [
                'name' => 'Debug Folder', 
                'path' => '/Debug', 
                'preview' => '', 
                'fileCount' => 0, 
                'isFolder' => true
            ]
        ]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFiles(string $path = '/'): DataResponse
    {
        return new DataResponse([]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function createFolder(string $path, string $name): DataResponse
    {
        return new DataResponse(['status' => 'success', 'path' => $name]);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function uploadFile(string $path): DataResponse
    {
         return new DataResponse(['status' => 'success', 'file' => 'debug']);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function downloadPackage(string $path): DataResponse
    {
         return new DataResponse(['status' => 'not_implemented']);
    }
}
