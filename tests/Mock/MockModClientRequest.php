<?php

namespace tests\Mock;

use app\HTTP\Request;

class MockModClientRequest extends Request
{
    public function __construct()
    {
        parent::__construct();

        $this->headers["content-type"] = "application/json";
        $this->headers["user-agent"] = "ServerBrowser/0.7.6 (BeatSaber/1.18.3) (steam)";
        $this->headers["x-bssb"] = "✔";
    }
}