<?php

declare(strict_types=1);

namespace OCA\NextcloudMicrostockDam\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'nextcloud-microstock-dam';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        // Registration logic if needed
    }

    public function boot(IBootContext $context): void
    {
        // Boot logic if needed
    }
}
