<?php

use app\Controllers\API\AnnounceController;
use app\Controllers\API\BrowseController;
use app\Controllers\API\StatusController;
use app\Controllers\API\UnAnnounceController;
use app\Controllers\GameDetailController;
use app\Controllers\HomeController;
use app\Controllers\PrivacyController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;

require_once "../bootstrap.php";

$router = new RequestRouter();

// Site routes
$router->register('/', [new HomeController(), 'index']);
$router->register('/game/$hashId', [new GameDetailController(), 'getGameDetail']);
$router->register('/privacy', [new PrivacyController(), 'getPrivacy']);

// API routes
$router->register('/api/v1/announce', [new AnnounceController(), 'announce']);
$router->register('/api/v1/unannounce', [new UnAnnounceController(), 'unAnnounce']);
$router->register('/api/v1/browse', [new BrowseController(), 'browse']);
$router->register('/api/v1/status', [new StatusController(), 'getStatus']);

// Run!
$request = Request::deduce();
$response = $router->dispatch($request);
$response->send();