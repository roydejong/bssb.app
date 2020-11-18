<?php

namespace tests\Mock;

use app\HTTP\Request;

class MockModClientRequest extends Request
{
    public function __construct()
    {
        parent::__construct();

        $this->headers["content-type"] = "application/json";
        $this->headers["user-agent"] = "ServerBrowser/0.2.0 (BeatSaber/1.12.2) (steam)";
        $this->headers["x-bssb"] = "✔";
    }
}