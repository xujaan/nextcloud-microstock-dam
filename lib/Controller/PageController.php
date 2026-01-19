<?php

declare(strict_types=1);

namespace OCA\NextcloudMicrostockDam\Controller;

use OCA\NextcloudMicrostockDam\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller
{
    public function __construct(IRequest $request)
    {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse
    {
        // Load our custom script and style
        \OCP\Util::addScript(Application::APP_ID, 'script');
        \OCP\Util::addStyle(Application::APP_ID, 'style');

        return new TemplateResponse(Application::APP_ID, 'main');
    }
}
