<?php

namespace tests\HTTP;

use app\HTTP\Response;
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
}
