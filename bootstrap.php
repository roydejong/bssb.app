<?php

use app\BSSB;

const DIR_BASE = __DIR__;
const DIR_PUBLIC = DIR_BASE . "/public";
const DIR_VIEWS = DIR_BASE . "/views";
const DIR_STORAGE = DIR_BASE . "/storage";
const DIR_CACHE = DIR_STORAGE . "/cache";

global $bssbConfig;

require_once DIR_BASE . "/vendor/autoload.php";

if (getenv('DOCKER_ENV'))
    require_once DIR_BASE . "/config.env.php";
else
    require_once DIR_BASE . "/config.php";

BSSB::bootstrap();