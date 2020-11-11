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

        $request = new IncomingRequest();
        $request->method = "GET";
        $request->path = "/object/123/test";

        $result = $router->dispatch($request);

        $this->assertTrue($routeDidFire);

        $this->assertInstanceOf("app\HTTP\Response", $result);
        $this->assertSame(200, $result->code);
        $this->assertSame("hello", $result->body);
    }

    public function testDispatch_404()
    {
        $request = new IncomingRequest();
        $request->method = "GET";
        $request->path = "/invalid-path";

        $router = new RequestRouter();
        $result = $router->dispatch($request);

        $this->assertInstanceOf("app\HTTP\Response", $result);
        $this->assertSame(404, $result->code);
    }
}
