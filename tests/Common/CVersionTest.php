<?php

namespace Common;

use app\Common\CVersion;
use PHPUnit\Framework\TestCase;

class CVersionTest extends TestCase
{
    public function testConstructAndStringify()
    {
        $this->assertSame("0.0.0.1", (string)(new CVersion("0.0.0.1")));
        $this->assertSame("0.0.0.1", (string)(new CVersion("0.0.0.1.0")));
        $this->assertSame("1.2.3.4", (string)(new CVersion("1.2.3.4")));
        $this->assertSame("1.2.3", (string)(new CVersion("1.2.3")));
        $this->assertSame("1.2", (string)(new CVersion("1.2")));
        $this->assertSame("1", (string)(new CVersion("1")));
        $this->assertSame("0", (string)(new CVersion("")));
    }

    public function testEquals()
    {
        $this->assertTrue((new CVersion("1.2.3.4"))->equals(new CVersion("1.2.3.4")));
        $this->assertTrue((new CVersion("1.2.3.0"))->equals(new CVersion("1.2.3")));
    }

    public function testGreaterThan()
    {
        $this->assertTrue((new CVersion("1.0.0.0"))->greaterThan(new CVersion("0.0.0.1")));
        $this->assertTrue((new CVersion("1.2.0.0"))->greaterThan(new CVersion("1.0.0.0")));
        $this->assertTrue((new CVersion("1.2.3.0"))->greaterThan(new CVersion("1.2.0.0")));
        $this->assertTrue((new CVersion("1.2.3.1"))->greaterThan(new CVersion("1.2.3.0")));

        $this->assertFalse((new CVersion("1.0.0.0"))->greaterThan(new CVersion("1.0.0.1")));
        $this->assertFalse((new CVersion("1.0.0.0"))->greaterThan(new CVersion("1.0.1.1")));
        $this->assertFalse((new CVersion("1.0.0.0"))->greaterThan(new CVersion("1.1.1.1")));
    }
}
