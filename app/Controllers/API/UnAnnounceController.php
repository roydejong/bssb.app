<?php

namespace app\Controllers\API;

use app\HTTP\IncomingRequest;

class UnAnnounceController
{
    public function unAnnounce(IncomingRequest $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}