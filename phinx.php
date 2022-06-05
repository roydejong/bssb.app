<?php

use SoftwarePunt\Instarecord\Instarecord;

require_once "bootstrap.php";

$dbConfig = Instarecord::config();

if (($ENV_DB_PORT = intval(getenv('DB_PORT'))) > 0) {
    $dbConfig->port = $ENV_DB_PORT;
}

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