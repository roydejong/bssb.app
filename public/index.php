<?php

use app\Controllers\API\V1\AnnounceController;
use app\Controllers\API\V1\AnnounceResultsController;
use app\Controllers\API\V1\BrowseController;
use app\Controllers\API\V1\BrowseDetailController;
use app\Controllers\API\V1\BrowseServerCodeController;
use app\Controllers\API\V1\StatusController;
use app\Controllers\API\V1\UnAnnounceController;
use app\Controllers\API\V2\UnAnnounceControllerV2;
use app\Controllers\DedicatedServersController;
use app\Controllers\DownloadController;
use app\Controllers\GameDetailController;
use app\Controllers\GuideController;
use app\Controllers\HomeController;
use app\Controllers\LoginController;
use app\Controllers\MasterServersController;
use app\Controllers\MeController;
use app\Controllers\PlayerProfileController;
use app\Controllers\PrivacyController;
use app\Controllers\StatsController;
use app\Controllers\UserSettingsController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;
use app\Session\Session;

require_once "../bootstrap.php";

$router = new RequestRouter();

// Site routes
$router->register('/', HomeController::class, 'index');
$router->register('/download', DownloadController::class, 'getDownloadPage');
$router->register('/stats', StatsController::class, 'getStats');
$router->register('/stats/top/$urlSection', StatsController::class, 'getTopLevelsSubPage');
$router->register('/stats/top/$urlSection/playlist', StatsController::class, 'getTopLevelsPlaylist');
$router->register('/guide', GuideController::class, 'getGuideIndex');
$router->register('/guide/$platform/$version', GuideController::class, 'getGuideResult');
$router->register('/game/$hashId', GameDetailController::class, 'getGameDetail');
$router->register('/player/$userId', PlayerProfileController::class, 'getPlayerProfile');
$router->register('/player/$userId/$profileSection', PlayerProfileController::class, 'getPlayerProfile');
$router->register('/privacy', PrivacyController::class, 'getPrivacy');
$router->register('/master-servers', MasterServersController::class, 'getServerList');
$router->register('/dedicated-servers', DedicatedServersController::class, 'getServerList');

// Auth + user
$router->register('/me', MeController::class, 'getMe');
$router->register('/login', LoginController::class, 'getLogin');
$router->register('/login/return', LoginController::class, 'getLoginReturn');
$router->register('/settings', UserSettingsController::class, 'getUserSettings');

// API routes
$router->register('/api/v1/announce', AnnounceController::class, 'announce');
$router->register('/api/v1/announce_results', AnnounceResultsController::class, 'announceResults');
$router->register('/api/v1/unannounce', UnAnnounceController::class, 'unAnnounce');
$router->register('/api/v1/browse', BrowseController::class, 'browse');
$router->register('/api/v1/browse/$hashId', BrowseDetailController::class, 'browseDetail');
$router->register('/api/v1/browse/code/$serverCode', BrowseServerCodeController::class, 'browseServerCode');
$router->register('/api/v1/status', StatusController::class, 'getStatus');

// API routes (v2)
$router->register('/api/v2/unannounce', UnAnnounceControllerV2::class, 'unAnnounce');

// Runtime
$session = Session::getInstance();
$request = Request::deduce();
$session->onRequest($request);

$response = $router->dispatch($request);

$session->beforeResponse($response);
$response->send();