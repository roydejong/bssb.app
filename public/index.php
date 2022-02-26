<?php

use app\Controllers\API\V1\AnnounceController;
use app\Controllers\API\V1\BrowseController;
use app\Controllers\API\V1\BrowseDetailController;
use app\Controllers\API\V1\BrowseServerCodeController;
use app\Controllers\API\V1\StatusController;
use app\Controllers\API\V1\UnAnnounceController;
use app\Controllers\API\V2\UnAnnounceControllerV2;
use app\Controllers\DedicatedServersController;
use app\Controllers\DownloadController;
use app\Controllers\GameDetailController;
use app\Controllers\HomeController;
use app\Controllers\MasterServersController;
use app\Controllers\PlayerDetailController;
use app\Controllers\PrivacyController;
use app\Controllers\StatsController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;

require_once "../bootstrap.php";

$router = new RequestRouter();

// Site routes
$router->register('/', HomeController::class, 'index');
$router->register('/download', DownloadController::class, 'getDownloadPage');
$router->register('/stats', StatsController::class, 'getStats');
$router->register('/stats/top/$urlSection', StatsController::class, 'getTopLevelsSubPage');
$router->register('/stats/top/$urlSection/playlist', StatsController::class, 'getTopLevelsPlaylist');
$router->register('/game/$hashId', GameDetailController::class, 'getGameDetail');
$router->register('/player/$userId', PlayerDetailController::class, 'getPlayerDetail');
$router->register('/privacy', PrivacyController::class, 'getPrivacy');
$router->register('/master-servers', MasterServersController::class, 'getServerList');
$router->register('/dedicated-servers', DedicatedServersController::class, 'getServerList');

// API routes
$router->register('/api/v1/announce', AnnounceController::class, 'announce');
$router->register('/api/v1/unannounce', UnAnnounceController::class, 'unAnnounce');
$router->register('/api/v1/browse', BrowseController::class, 'browse');
$router->register('/api/v1/browse/$hashId', BrowseDetailController::class, 'browseDetail');
$router->register('/api/v1/browse/code/$serverCode', BrowseServerCodeController::class, 'browseServerCode');
$router->register('/api/v1/status', StatusController::class, 'getStatus');

// API routes (v2)
$router->register('/api/v2/unannounce', UnAnnounceControllerV2::class, 'unAnnounce');

// Run!
$request = Request::deduce();
$response = $router->dispatch($request);
$response->send();