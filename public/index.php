<?php

use app\HTTP\IncomingRequest;
use app\HTTP\RequestRouter;

require_once "../bootstrap.php";

$router = new RequestRouter();

$router->register("/", function () {
    die("hi");
});

$router->dispatch(IncomingRequest::deduce());