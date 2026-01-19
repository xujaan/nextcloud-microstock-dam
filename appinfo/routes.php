<?php
return [
    'routes' => [
        ['name' => 'Page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'Api#listAssets', 'url' => '/api/list', 'verb' => 'GET'],
        ['name' => 'Api#getFiles', 'url' => '/api/files', 'verb' => 'GET'],
        ['name' => 'Api#createFolder', 'url' => '/api/folder', 'verb' => 'POST'],
        ['name' => 'Api#uploadFile', 'url' => '/api/upload', 'verb' => 'POST'],
        ['name' => 'Api#downloadPackage', 'url' => '/api/package', 'verb' => 'GET'],
    ]
];
