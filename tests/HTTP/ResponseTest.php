<?php

use app\HTTP\Response;
use configx\astro\Web\HttpResponse;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSend()
    {
        ob_start();

        $response = new Response(418, "hello", "application/bloop");
        $response->send();

        $output = ob_get_contents();

        $this->assertSame(418, http_response_code());
        $this->assertSame("hello", $output);

        if (extension_loaded('xdebug')) {
            $this->assertSame([
                "content-type: application/bloop"
            ], xdebug_get_headers());
        } else {
            $this->markTestIncomplete('xdebug not available');
        }

        ob_end_clean();
    }

    public function testParseFromCurl()
    {
        $response = Response::fromCurl(
            "HTTP/1.1 404 Not Quite\r\nHost: 404.org\r\nX-Blah: blah!\r\n\r\n",
            "but my body. my body is telling me yes"
        );

        $this->assertSame(404, $response->code);
        $this->assertSame("404.org", $response->headers["Host"]);
        $this->assertSame("blah!", $response->headers["X-Blah"]);
        $this->assertSame("but my body. my body is telling me yes", $response->body);
    }

    public function testInvalidParseFromCurl()
    {
        $this->expectExceptionMessage("invalid HTTP status line");

        Response::fromCurl("blah\r\nblah\r\n\r\n", "");
    }
}
