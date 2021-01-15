<?php

namespace Frontend;

use app\Frontend\ResponseCache;
use app\HTTP\Response;
use PHPUnit\Framework\TestCase;

class ResponseCacheTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    public static function setUpBeforeClass(): void
    {
        self::tearDownAfterClass();
    }

    public static function tearDownAfterClass(): void
    {
        $expectedPath = DIR_BASE . "/storage/response_cache/test_file.res";

        if (file_exists($expectedPath)) {
            @unlink($expectedPath);
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tests actual

    public function testGetFilePath()
    {
        $rc = new ResponseCache("test_file", 999);
        $this->assertSame(DIR_BASE . "/storage/response_cache/test_file.res", $rc->getFilePath());
    }

    public function testGetIsAvailable_BeforeWrite()
    {
        $rc = new ResponseCache("test_file", 999);
        $this->assertFalse($rc->getIsAvailable(), "File does not exist yet; getIsAvailable() should be false");
    }

    /**
     * @depends testGetIsAvailable_BeforeWrite
     */
    public function testWriteAndRead()
    {
        $rc = new ResponseCache("test_file", 999);
        $this->assertTrue($rc->write("example_response"));
        $this->assertSame("example_response", $rc->read());
    }

    /**
     * @depends testWriteAndRead
     */
    public function testGetIsAvailable_AfterWrite_TTL()
    {
        $rc = new ResponseCache("test_file", 999);
        $this->assertTrue($rc->getIsAvailable(), "With TTL set to 999, test_file should not have expired yet");

        $rc = new ResponseCache("test_file", 0);
        $this->assertFalse($rc->getIsAvailable(), "With TTL set to 0, test_file should have expired already");

        $rc = new ResponseCache("test_file", null);
        $this->assertTrue($rc->getIsAvailable(), "With TTL set to NULL, modified time should not be checked");
    }

    /**
     * @depends testWriteAndRead
     */
    public function testWriteAndReadResponse()
    {
        $rc = new ResponseCache("test_file", 999);

        // Write
        $someResponse = new Response();
        $someResponse->body = "testWriteAndReadResponse_sample_value";
        $this->assertTrue($rc->writeResponse($someResponse));

        // Read
        $response = $rc->readAsResponse(418, "text/plain");
        $this->assertSame("testWriteAndReadResponse_sample_value", $response->body);
        $this->assertSame(418, $response->code);
        $this->assertSame("text/plain", $response->headers["content-type"]);
    }
}
