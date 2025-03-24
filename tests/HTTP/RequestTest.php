<?php

use app\HTTP\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testDeduce()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "PATCH",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/hello.txt?param=123",
            'HTTP_USER_AGENT' => "Mozilla/5.0",
            'HTTPS' => 1
        ];
        $_GET = [
            'param' => "123"
        ];
        $result = Request::deduce();

        $this->assertSame("PATCH", $result->method);
        $this->assertSame("test.bssb.app", $result->host);
        $this->assertSame("/hello.txt", $result->path);
        $this->assertSame("123", $result->queryParams['param']);
        $this->assertSame("Mozilla/5.0", $result->headers['user-agent']);
        $this->assertSame("https", $result->protocol);
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
        $result = Request::deduce();

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
        $result = (Request::deduce())->getJson();

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
        $result = (Request::deduce())->getModClientInfo();

        $this->assertInstanceOf("app\BeatSaber\ModClientInfo", $result);
        $this->assertSame("ServerBrowser", $result->modName);
    }

    public function testGetModClientInfoQuest()
    {
        $_SERVER = [
            'REQUEST_METHOD' => "GET",
            'HTTP_HOST' => "test.bssb.app",
            'REQUEST_URI' => "/",
            'HTTP_USER_AGENT' => "ServerBrowserQuest/1.0.0.0 (BeatSaber/1.17.0)"
        ];
        $result = (Request::deduce())->getModClientInfo();

        $this->assertInstanceOf("app\BeatSaber\ModClientInfo", $result);
        $this->assertSame("ServerBrowserQuest", $result->modName);
    }

    public function testGetIsValidModClientRequest()
    {
        $request1 = new Request();
        $request1->method = "GET";
        $request1->path = "/";

        $this->assertFalse($request1->getIsValidModClientRequest(),
            "request1 should be invalid: missing X-BSSB and valid User-Agent");
        $this->assertFalse($request1->getIsValidClientRequest());

        $request2 = new Request();
        $request2->method = "GET";
        $request2->path = "/";
        $request2->headers["user-agent"] = "ServerBrowser/0.1.1.0 (BeatSaber/1.12.2)";

        $this->assertFalse($request2->getIsValidModClientRequest(),
            "request2 should be invalid: missing X-BSSB");
        $this->assertFalse($request2->getIsValidClientRequest());

        $request3 = new Request();
        $request3->method = "GET";
        $request3->path = "/";
        $request3->headers["user-agent"] = "ServerBrowser/0.1.1.0 (BeatSaber/1.12.2)";
        $request3->headers["x-bssb"] = "✔";

        $this->assertTrue($request3->getIsValidModClientRequest(),
            "request3 should be valid: have valid user agent and X-BSSB");
        $this->assertTrue($request3->getIsValidClientRequest());

        $request4 = new Request();
        $request4->method = "GET";
        $request4->path = "/";
        $request4->headers["user-agent"] = "ServerBrowserQuest/0.1.1.0 (BeatSaber/1.12.2)";
        $request4->headers["x-bssb"] = "✔";

        $this->assertTrue($request4->getIsValidModClientRequest(),
            "request4 should be valid: have valid quest user agent and X-BSSB");
        $this->assertTrue($request4->getIsValidClientRequest());
    }

    public function testGetIsValidBeatNetRequest()
    {
        $request1 = new Request();
        $request1->method = "GET";
        $request1->path = "/";

        $this->assertFalse($request1->getIsValidBeatNetRequest(),
            "request1 should be invalid: missing X-BSSB and valid User-Agent");
        $this->assertFalse($request1->getIsValidClientRequest());

        $request2 = new Request();
        $request2->method = "GET";
        $request2->path = "/";
        $request2->headers["user-agent"] = "BeatNet/0.1.0.0 (BeatSaber/1.16.1) (dedi)";

        $this->assertFalse($request2->getIsValidBeatNetRequest(),
            "request2 should be invalid: missing X-BSSB");
        $this->assertFalse($request2->getIsValidClientRequest());

        $request3 = new Request();
        $request3->method = "GET";
        $request3->path = "/";
        $request3->headers["user-agent"] = "BeatNet/0.1.0.0 (BeatSaber/1.16.1) (dedi)";
        $request3->headers["x-bssb"] = "✔";

        $this->assertTrue($request3->getIsValidBeatNetRequest(),
            "request3 should be valid: have valid user agent and X-BSSB");
        $this->assertTrue($request3->getIsValidClientRequest());
    }
}
