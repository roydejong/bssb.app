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

    public function testToStringWithLimit()
    {
        $this->assertSame("1.2.3.4", (new CVersion("1.2.3.4.5"))->toString());
        $this->assertSame("1.2.3", (new CVersion("1.2.3.4.5"))->toString(3));
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

    public function testGreaterThanOrEquals()
    {
        $this->assertTrue((new CVersion("1.0.0.1"))->greaterThanOrEquals(new CVersion("1.0.0.0")));
        $this->assertTrue((new CVersion("1.0.0.0"))->greaterThanOrEquals(new CVersion("1.0.0.0")));

        $this->assertFalse((new CVersion("0.9.9.9"))->greaterThanOrEquals(new CVersion("1.0.0.0")));
    }

    public function testLessThan()
    {
        $this->assertTrue((new CVersion("0.9.9.9"))->lessThan(new CVersion("1.0.0.0")));

        $this->assertFalse((new CVersion("1.0.0.0"))->lessThan(new CVersion("1.0.0.0")));
    }

    public function testLessThanOrEquals()
    {
        $this->assertTrue((new CVersion("0.9.9.9"))->lessThanOrEquals(new CVersion("1.0.0.0")));
        $this->assertTrue((new CVersion("1.0.0.0"))->lessThanOrEquals(new CVersion("1.0.0.0")));

        $this->assertFalse((new CVersion("1.0.0.1"))->lessThanOrEquals(new CVersion("1.0.0.0")));
    }

    public function testMax()
    {
        $a = new CVersion("1.2.3");
        $b = new CVersion("3.2.1");

        $this->assertSame($b->toString(), CVersion::max($a, $b)->toString());
        $this->assertSame($b->toString(), CVersion::max($b, $a)->toString());
    }

    public function testMin()
    {
        $a = new CVersion("1.2.3");
        $b = new CVersion("3.2.1");

        $this->assertSame($a->toString(), CVersion::min($a, $b)->toString());
        $this->assertSame($a->toString(), CVersion::min($b, $a)->toString());
    }

    public function testVersionWithSuffix()
    {
        $this->assertSame("1.2.3+some-special-indicator",
            (new CVersion("1.2.3+some-special-indicator"))->toString());
    }

    public function testIgnoresBeatSaberVersionSuffix()
    {
        $this->assertSame("1.29.1",
            (new CVersion("1.29.1_4575554838"))->toString());
    }
}
