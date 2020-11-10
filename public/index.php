<?php

use app\HTTP\IncomingRequest;

require_once "../bootstrap.php";

$request = IncomingRequest::deduce();

var_dump($request);