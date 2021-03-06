<?php

namespace tests\Mock;

class MockJsonRequest extends MockModClientRequest
{
    public array $json;

    public function __construct(array $json)
    {
        parent::__construct();

        $this->json = $json;
    }

    public function getJson(): ?array
    {
        return $this->json;
    }
}