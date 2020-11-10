<?php

namespace tests\HTTP;

use app\HTTP\IncomingRequest;
use PHPUnit\Framework\TestCase;

class IncomingRequestTest extends TestCase
{
    public function testDeduce()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "PATCH",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/hello.txt?param=123",
            'HTTP_USER_AGENT' => "Mozilla/5.0"
        ];
        $_GET = [
            'param' => "123"
        ];
        $result = IncomingRequest::deduce();

        $this->assertSame("PATCH", $result->method);
        $this->assertSame("test.bssb.app", $result->host);
        $this->assertSame("/hello.txt", $result->path);
        $this->assertSame("123", $result->queryParams['param']);
        $this->assertSame("Mozilla/5.0", $result->headers['user-agent']);
        $this->assertNull($result->getJson());
    }

    public function testGetIsJsonRequest()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "POST",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/action",
            'HTTP_CONTENT_TYPE' => "application/json; charset=utf-8"
        ];
        $result = IncomingRequest::deduce();

        $this->assertTrue($result->getIsJsonRequest());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetJson()
    {
        define('PHP_INPUT_URI', __DIR__ . "/sample_input.json");

        $_SERVER = [
            'REQUEST_METHOD' => "POST",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/action",
            'HTTP_CONTENT_TYPE' => "application/json; charset=utf-8"
        ];
        $result = (IncomingRequest::deduce())->getJson();

        $this->assertIsArray($result);
        $this->assertSame("XXXXX", $result['ServerCode']);
    }

    public function testGetModClientInfo()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "GET",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/",
            'HTTP_USER_AGENT' => "ServerBrowser/0.1.1.0 (BeatSaber/1.12.2)"
        ];
        $result = (IncomingRequest::deduce())->getModClientInfo();

        $this->assertInstanceOf("app\BeatSaber\ModClientInfo", $result);
        $this->assertSame("ServerBrowser", $result->modName);
    }
}
