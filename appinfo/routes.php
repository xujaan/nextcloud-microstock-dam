<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#listAssets', 'url' => '/api/list', 'verb' => 'GET'],
        ['name' => 'api#getFiles', 'url' => '/api/files', 'verb' => 'GET'],
        ['name' => 'api#createFolder', 'url' => '/api/folder', 'verb' => 'POST'],
        ['name' => 'api#uploadFile', 'url' => '/api/upload', 'verb' => 'POST'],
        ['name' => 'api#downloadPackage', 'url' => '/api/package', 'verb' => 'GET'],
    ]
];
