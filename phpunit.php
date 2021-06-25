<?php

use SoftwarePunt\Instarecord\Instarecord;

define('IS_TEST_RUN', true);

require_once __DIR__ . "/bootstrap.php";

$dbConfig = Instarecord::config();

$ENV_DB_PORT = intval(getenv('DB_PORT'));

if ($ENV_DB_PORT > 0) {
    $dbConfig->port = $ENV_DB_PORT;
}

if ($dbConfig->unix_socket) {
    echo "Target test database: {$dbConfig->database} ({$dbConfig->username}@{$dbConfig->unix_socket})" . PHP_EOL;
} else {
    echo "Target test database: {$dbConfig->database} ({$dbConfig->username}@{$dbConfig->host}:{$dbConfig->port})" . PHP_EOL;
}