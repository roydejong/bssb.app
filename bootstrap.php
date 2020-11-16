<?php

use Instasell\Instarecord\Instarecord;

define('DIR_BASE', __DIR__);
define('DIR_VIEWS', DIR_BASE . "/views");
define('DIR_STORAGE', DIR_BASE . "/storage");
define('DIR_CACHE', DIR_STORAGE . "/cache");

require_once DIR_BASE . "/vendor/autoload.php";
require_once "config.php";

$dbConfig = Instarecord::config();

echo "Target test database: {$dbConfig->database} ({$dbConfig->username}@{$dbConfig->host})" . PHP_EOL;