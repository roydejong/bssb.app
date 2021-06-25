<?php

namespace Controllers\API;

use app\Controllers\API\StatusController;
use app\HTTP\Request;
use PHPUnit\Framework\TestCase;
use SoftwarePunt\Instarecord\Instarecord;

class StatusControllerTest extends TestCase
{
    public function testGetStatusOk()
    {
        $controller = new StatusController();

        $request = new Request();
        $request->method = "GET";
        $request->path = "/api/v1/status";

        $response = $controller->getStatus($request);

        $this->assertSame(200, $response->code);
        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);
        $this->assertStringContainsString('"status":"ok"', $response->body);
    }

    /**
     * @depends testGetStatusOk
     * @runInSeparateProcess
     */
    public function testGetStatusDbError()
    {
        $dbConfig = Instarecord::config();
        $dbConfig->unix_socket = "/invalid/";
        $dbConfig->host = "/invalid/";

        $controller = new StatusController();

        $request = new Request();
        $request->method = "GET";
        $request->path = "/api/v1/status";

        $response = $controller->getStatus($request);

        $this->assertSame(200, $response->code);
        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);
        $this->assertStringContainsString('"status":"db_error"', $response->body);
    }
}
