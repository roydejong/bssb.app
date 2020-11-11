<?php

namespace tests\HTTP;

use app\HTTP\IncomingRequest;
use app\HTTP\RequestRouter;
use PHPUnit\Framework\TestCase;

class RequestRouterTest extends TestCase
{
    public function testRegisterAndDispatch()
    {
        $router = new RequestRouter();

        $routeDidFire = false;

        $router->register('/object/$id/test', function (IncomingRequest $request, string $id) use (&$routeDidFire) {
            $this->assertInstanceOf("app\HTTP\IncomingRequest", $request);
            $this->assertSame("123", $id);

            $routeDidFire = true;

            return "hello";
        });

        $_SERVER = [
            'REQUEST_METHOD' => "GET",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/object/123/test"
        ];

        $request = IncomingRequest::deduce();
        $result = $router->dispatch($request);

        $this->assertTrue($routeDidFire);
        $this->assertSame("hello", $result);
    }

    public function testDispatch_404()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "GET",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/object/123/test"
        ];

        $request = IncomingRequest::deduce();

        $router = new RequestRouter();
        $result = $router->dispatch($request);

        $this->assertNull($result);
    }
}
