<?php

// Config file/adapter for Docker via env variables

use SoftwarePunt\Instarecord\Instarecord;

global $bssbConfig;

if (!defined('IS_TEST_RUN') && getenv('SENTRY_DSN')) {
    Sentry\init(['dsn' => getenv('SENTRY_DSN')]);
}

$enableCache = !!getenv('CACHE_ENABLED');

$bssbConfig['cache_enabled'] = $enableCache;
$bssbConfig['response_cache_enabled'] = $enableCache;
$bssbConfig['hashids_salt'] = getenv('HASHIDS_SALT') ?: "🧂";
$bssbConfig['steam_web_api_key'] = getenv('STEAM_WEB_API_KEY') ?: "";
$bssbConfig['master_server_blacklist'] = [];
$bssbConfig['allow_multiple_results'] = false;
$bssbConfig['enable_guide'] = false;

$dbConfig = Instarecord::config();
$dbConfig->unix_socket = null;
$dbConfig->host = "mysql";
$dbConfig->port = 3306;
$dbConfig->username = getenv('MYSQL_USER');
$dbConfig->password = getenv('MYSQL_PASSWORD');
$dbConfig->database = getenv('MYSQL_DATABASE');