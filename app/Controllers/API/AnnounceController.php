<?php

namespace app\Controllers\API;

use app\HTTP\IncomingRequest;

class AnnounceController
{
    public function announce(IncomingRequest $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}