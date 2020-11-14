<?php

namespace tests;

use app\Common\CString;
use PHPUnit\Framework\TestCase;

class CStringTest extends TestCase
{
    public function testStartsWith()
    {
        $this->assertTrue(CString::startsWith("a", "a"));
        $this->assertTrue(CString::startsWith("abc", "a"));
        $this->assertTrue(CString::startsWith("a book", "a b"));

        $this->assertFalse(CString::startsWith("", ""));
        $this->assertFalse(CString::startsWith("abc", "c"));
        $this->assertFalse(CString::startsWith("", "longer"));
    }

    public function testEndsWith()
    {
        $this->assertTrue(CString::endsWith("a", "a"));
        $this->assertTrue(CString::endsWith("abc", "c"));
        $this->assertTrue(CString::endsWith("eat blah", "blah"));

        $this->assertFalse(CString::endsWith("", ""));
        $this->assertFalse(CString::endsWith("", "a"));
        $this->assertFalse(CString::endsWith("b", "a"));
        $this->assertFalse(CString::endsWith("", "longer"));
    }

    public function testContains()
    {
        $this->assertTrue(CString::contains("parent", "a"));
        $this->assertTrue(CString::contains("parent", "rent"));

        $this->assertFalse(CString::contains("parent", "P"));
        $this->assertFalse(CString::contains("parent", ""));
    }
}
