<?php

use app\Controllers\API\AnnounceController;
use app\Controllers\API\BrowseController;
use app\Controllers\API\UnAnnounceController;
use app\Controllers\HomeController;
use app\HTTP\Request;
use app\HTTP\RequestRouter;

require_once "../bootstrap.php";

$router = new RequestRouter();

// Site routes
$router->register("/", [new HomeController(), 'index']);

// API routes
$router->register("/api/v1/announce", [new AnnounceController(), 'announce']);
$router->register("/api/v1/unannounce", [new UnAnnounceController(), 'unAnnounce']);
$router->register("/api/v1/browse", [new BrowseController(), 'browse']);

// Run!
$request = Request::deduce();
$response = $router->dispatch($request);
$response->send();