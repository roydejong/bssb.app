<?php

namespace app\Controllers\API;

use app\HTTP\IncomingRequest;

class BrowseController
{
    public function browse(IncomingRequest $request)
    {
        die("hi {$request->method} {$request->path}");
    }
}