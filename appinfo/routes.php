<?php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#list_assets', 'url' => '/api/list', 'verb' => 'GET'],
        ['name' => 'api#get_files', 'url' => '/api/files', 'verb' => 'GET'],
        ['name' => 'api#create_folder', 'url' => '/api/folder', 'verb' => 'POST'],
        ['name' => 'api#upload_file', 'url' => '/api/upload', 'verb' => 'POST'],
        ['name' => 'api#download_package', 'url' => '/api/package', 'verb' => 'GET'],
    ]
];
