<?php

// Config file for GitHub actions

use SoftwarePunt\Instarecord\Instarecord;

global $bssbConfig;

$bssbConfig['cache_enabled'] = false;
$bssbConfig['response_cache_enabled'] = false;
$bssbConfig['hashids_salt'] = "🧂";
$bssbConfig['steam_web_api_key'] = "";
$bssbConfig['master_server_blacklist'] = [];
$bssbConfig['allow_multiple_results'] = false;
$bssbConfig['enable_guide'] = false;

$dbConfig = Instarecord::config();
$dbConfig->host = "127.0.0.1";
$dbConfig->port = 3306;
$dbConfig->username = "user";
$dbConfig->password = "secret";
$dbConfig->database = "test_db";
$dbConfig->unix_socket = null;