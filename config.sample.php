<?php

use Instasell\Instarecord\Instarecord;

global $config;

/**
 * cache_enabled, boolean
 *  If enabled: Enable compilation and response caching behavior (for production).
 */

$config['cache_enabled'] = true;

/**
 * Instarecord configuration
 */
$dbConfig = Instarecord::config();
$dbConfig->unix_socket = "/var/lib/mysql/mysql.sock";
$dbConfig->username = "user";
$dbConfig->password = "password";
$dbConfig->database = "bssb";