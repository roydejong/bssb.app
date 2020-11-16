<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class RedirectResponse extends Response
{
    public function __construct(string $location, int $code = 302)
    {
        parent::__construct($code);

        $this->headers['location'] = $location;
    }
}