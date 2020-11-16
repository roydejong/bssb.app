<?php

// Config file for GitHub actions

use Instasell\Instarecord\Instarecord;

global $config;

$config['cache_enabled'] = false;

$dbConfig = Instarecord::config();
$dbConfig->host = "127.0.0.1";
$dbConfig->port = 3306;
$dbConfig->username = "user";
$dbConfig->password = "secret";
$dbConfig->database = "test_db";
$dbConfig->unix_socket = null;