<?php

// Config file/adapter for Docker via env variables

use SoftwarePunt\Instarecord\Instarecord;

global $bssbConfig;

$bssbConfig['cache_enabled'] = true;
$bssbConfig['response_cache_enabled'] = true;
$bssbConfig['hashids_salt'] = getenv('hashids_salt');
$bssbConfig['steam_web_api_key'] = getenv('steam_web_api_key');
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