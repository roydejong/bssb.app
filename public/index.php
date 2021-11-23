<?php

use app\Controllers\API\AnnounceController;
use app\Controllers\API\BrowseController;
use app\Controllers\API\BrowseDetailController;
use app\Controllers\API\StatusController;
use app\Controllers\API\UnAnnounceController;
use app\Controllers\DedicatedServersController;
use app\Controllers\GameDetailController;
use app\Controllers\HomeController;
use app\Controllers\PrivacyController;
use app\Controllers\StatsController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;

require_once "../bootstrap.php";

$router = new RequestRouter();

// Site routes
$router->register('/', HomeController::class, 'index');
$router->register('/stats', StatsController::class, 'getStats');
$router->register('/stats/top/$urlSection', StatsController::class, 'getTopLevelsSubPage');
$router->register('/stats/top/$urlSection/playlist', StatsController::class, 'getTopLevelsPlaylist');
$router->register('/game/$hashId', GameDetailController::class, 'getGameDetail');
$router->register('/privacy', PrivacyController::class, 'getPrivacy');
$router->register('/dedicated-servers', DedicatedServersController::class, 'getServerList');

// API routes
$router->register('/api/v1/announce', AnnounceController::class, 'announce');
$router->register('/api/v1/unannounce', UnAnnounceController::class, 'unAnnounce');
$router->register('/api/v1/browse', BrowseController::class, 'browse');
$router->register('/api/v1/browse/$hashId', BrowseDetailController::class, 'browseDetail');
$router->register('/api/v1/status', StatusController::class, 'getStatus');

// Run!
$request = Request::deduce();
$response = $router->dispatch($request);
$response->send();