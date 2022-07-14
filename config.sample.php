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
 * steam_web_api_key, string
 *  → Steam Web API key (optional, for getting steam usernames).
 */
$bssbConfig['steam_web_api_key'] = "";

/**
 * master server blacklist, array of strings
 *  → Games with a master server from this list will not be allowed to announce.
 */
$bssbConfig['master_server_blacklist'] = [];

/**
 * allow_multiple_results, boolean
 *  → If enabled, AnnounceResultsController will not ignore duplicate or late results and process them like new
 */
$bssbConfig['allow_multiple_results'] = false;

/**
 * enable_guide, boolean
 *  → If enabled, enable multiplayer guide page/feature
 */
$bssbConfig['enable_guide'] = false;

/**
 * allow_boring, boolean
 *  → If enabled, allow "boring" games (local/LAN announces that aren't relevant).
 */
$bssbConfig['allow_boring'] = false;

/**
 * Instarecord configuration
 */
$dbConfig = Instarecord::config();
$dbConfig->unix_socket = "/var/lib/mysql/mysql.sock";
$dbConfig->username = "user";
$dbConfig->password = "password";
$dbConfig->database = "bssb";