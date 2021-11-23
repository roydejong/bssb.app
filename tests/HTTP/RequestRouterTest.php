<?php

use app\HTTP\Request;
use app\HTTP\RequestRouter;
use PHPUnit\Framework\TestCase;

class RequestRouterTest extends TestCase
{
    public function testRegisterAndDispatch()
    {
        $router = new RequestRouter();
        $router->register('/object/$id/test', RequestRouterTest_Controller::class,
            'testMethod');

        $request = new Request();
        $request->method = "GET";
        $request->path = "/object/123/test";
        $request->host = "host.web";

        $this->assertNull(RequestRouterTest_Controller::$lastRequest, 'Sanity check');
        $this->assertNull(RequestRouterTest_Controller::$lastId, 'Sanity check');

        $result = $router->dispatch($request);

        $this->assertNotNull(RequestRouterTest_Controller::$lastRequest);
        $this->assertInstanceOf("app\HTTP\Request", RequestRouterTest_Controller::$lastRequest);
        $this->assertSame("123", RequestRouterTest_Controller::$lastId);

        $this->assertInstanceOf("app\HTTP\Response", $result);
        $this->assertSame(200, $result->code);
        $this->assertSame("hello", $result->body);
    }

    public function testDispatch_404()
    {
        $request = new Request();
        $request->method = "GET";
        $request->path = "/invalid-path";
        $request->host = "host.web";

        $router = new RequestRouter();
        $result = $router->dispatch($request);

        $this->assertInstanceOf("app\HTTP\Response", $result);
        $this->assertSame(404, $result->code);
    }

    public function testDispatch_missingHostHeader()
    {
        $request = new Request();
        $request->method = "GET";
        $request->path = "/invalid-path";
        $request->host = null;

        $router = new RequestRouter();
        $result = $router->dispatch($request);

        $this->assertInstanceOf("app\HTTP\Response", $result);
        $this->assertSame(400, $result->code);
    }
}

class RequestRouterTest_Controller
{
    public static ?Request $lastRequest = null;
    public static ?string $lastId = null;

    public function testMethod(Request $request, string $id): string
    {
        self::$lastRequest = $request;
        self::$lastId = $id;
        return "hello";
    }
}
