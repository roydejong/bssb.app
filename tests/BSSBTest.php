<?php

use app\BSSB;
use PHPUnit\Framework\TestCase;

class BSSBTest extends TestCase
{
    public function testGetHashids()
    {
        $oneA = BSSB::getHashids("one");
        $oneB = BSSB::getHashids("one");

        $two = BSSB::getHashids("two");

        $this->assertSame($oneA, $oneB, "Hashids instance should be cached");
        $this->assertNotSame($oneA, $two, "Hashids instance should be unique per \$key");
    }
}
