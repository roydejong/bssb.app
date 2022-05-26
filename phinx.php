<?php

use SoftwarePunt\Instarecord\Instarecord;

require_once "bootstrap.php";

$dbConfig = Instarecord::config();

return [
    'environments' => [
        'default_environment' => 'default',
        'default' => [
            'name' => Instarecord::config()->database,
            'connection' => Instarecord::connection()->getPdo()
        ]
    ],
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/migrations'
    ]
];