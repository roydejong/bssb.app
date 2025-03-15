<?php

use app\Controllers\Admin\AdminBansController;
use app\Controllers\Admin\AdminConnectionsController;
use app\Controllers\Admin\AdminDashController;
use app\Controllers\Admin\AdminNewsController;
use app\Controllers\Admin\AdminTrendsController;
use app\Controllers\Admin\TwitterCallbackController;
use app\Controllers\AdminController;
use app\Controllers\API\V1\AnnounceController;
use app\Controllers\API\V1\AnnounceResultsController;
use app\Controllers\API\V1\BrowseController;
use app\Controllers\API\V1\BrowseDetailController;
use app\Controllers\API\V1\BrowseServerCodeController;
use app\Controllers\API\V1\StatusController;
use app\Controllers\API\V1\UnAnnounceController;
use app\Controllers\API\V2\ConfigController;
use app\Controllers\API\V2\SteamAvatarController;
use app\Controllers\API\V2\SyncFriendsController;
use app\Controllers\API\V2\UnAnnounceControllerV2;
use app\Controllers\BefriendController;
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
use app\Controllers\ResultsController;
use app\Controllers\StatsController;
use app\Controllers\UserSettingsController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;
use app\Session\Session;

// ---------------------------------------------------------------------------------------------------------------------
// Bootstrap

require_once "../bootstrap.php";

// ---------------------------------------------------------------------------------------------------------------------
// Routes

$router = new RequestRouter();

// Public site
$router->register('/', HomeController::class, 'index');
$router->register('/download', DownloadController::class, 'getDownloadPage');
$router->register('/stats', StatsController::class, 'getStats');
$router->register('/stats/top/$urlSection', StatsController::class, 'getTopLevelsSubPage');
$router->register('/stats/top/$urlSection/playlist', StatsController::class, 'getTopLevelsPlaylist');
$router->register('/guide', GuideController::class, 'getGuideIndex');
$router->register('/guide/$platform/$version', GuideController::class, 'getGuideResult');
$router->register('/game/$hashId', GameDetailController::class, 'getGameDetail');
$router->register('/game/$hashId/players', GameDetailController::class, 'getGameDetailPlayers');
$router->register('/game/$hashId/plays', GameDetailController::class, 'getGameDetailPlays');
$router->register('/player/$userId', PlayerProfileController::class, 'getPlayerProfile');
$router->register('/player/$userId/$profileSection', PlayerProfileController::class, 'getPlayerProfile');
$router->register('/befriend', BefriendController::class, 'getBefriend');
$router->register('/privacy', PrivacyController::class, 'getPrivacy');
$router->register('/master-servers', MasterServersController::class, 'getServerList');
$router->register('/results/$guid', ResultsController::class, 'getResultsDetail');

// Auth + user
$router->register('/me', MeController::class, 'getMe');
$router->register('/login', LoginController::class, 'getLogin');
$router->register('/login/return', LoginController::class, 'getLoginReturn');
$router->register('/settings', UserSettingsController::class, 'getUserSettings');

// API routes (v1)
$router->register('/api/v1/announce', AnnounceController::class, 'announce');
$router->register('/api/v1/announce_results', AnnounceResultsController::class, 'announceResults');
$router->register('/api/v1/unannounce', UnAnnounceController::class, 'unAnnounce');
$router->register('/api/v1/browse', BrowseController::class, 'browse');
$router->register('/api/v1/browse/$hashId', BrowseDetailController::class, 'browseDetail');
$router->register('/api/v1/browse/code/$serverCode', BrowseServerCodeController::class, 'browseServerCode');
$router->register('/api/v1/status', StatusController::class, 'getStatus');

// API routes (v2)
$router->register('/api/v2/browse', \app\Controllers\API\V2\BrowseController::class, 'browse');
$router->register('/api/v2/config', ConfigController::class, 'getConfig');
$router->register('/api/v2/login', \app\Controllers\API\V2\LoginController::class, 'login');
$router->register('/api/v2/sync_friends', SyncFriendsController::class, 'syncFriends');
$router->register('/api/v2/unannounce', UnAnnounceControllerV2::class, 'unAnnounce');

// Admin
$router->register('/admin', AdminDashController::class, 'getDash');
$router->register('/admin/bans', AdminBansController::class, 'getBans');
$router->register('/admin/bans/$id', AdminBansController::class, 'getBanItem');
$router->register('/admin/news', AdminNewsController::class, 'getNews');
$router->register('/admin/news/$id', AdminNewsController::class, 'getNewsItem');
$router->register('/admin/connections', AdminConnectionsController::class, 'getConnections');
$router->register('/admin/trends', AdminTrendsController::class, 'getTrends');
$router->register('/callback/twitter', TwitterCallbackController::class, 'handleCallback');

// ---------------------------------------------------------------------------------------------------------------------
// Session

$session = Session::getInstance();
$request = Request::deduce();
$session->onRequest($request);

// ---------------------------------------------------------------------------------------------------------------------
// Response

$response = $router->dispatch($request);

$session->beforeResponse($response);
$response->send();