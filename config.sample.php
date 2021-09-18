<?php

use SoftwarePunt\Instarecord\Instarecord;

global $bssbConfig;

/**
 * Sentry error logging
 */
if (!defined('IS_TEST_RUN')) {
    Sentry\init(['dsn' => 'SET_ME']);
}

/**
 * cache_enabled, boolean
 *  → If enabled: Enable compilation and response caching behavior (for production).
 */
$bssbConfig['cache_enabled'] = true;

/**
 * response_cache_enabled, boolean
 *  → If enabled: Enable response caching behavior.
 */
$bssbConfig['response_cache_enabled'] = true;

/**
 * hashids_salt, string
 *  → The salt used to calculate hashids.
 */
$bssbConfig['hashids_salt'] = "🧂";

/**
 * master server blacklist, array of strings
 *  → Games with a master server from this list will not be allowed to announce.
 */
$bssbConfig['master_server_blacklist'] = [];

/**
 * Instarecord configuration
 */
$dbConfig = Instarecord::config();
$dbConfig->unix_socket = "/var/lib/mysql/mysql.sock";
$dbConfig->username = "user";
$dbConfig->password = "password";
$dbConfig->database = "bssb";