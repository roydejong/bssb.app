<?php

use app\Common\IPEndPoint;
use PHPUnit\Framework\TestCase;

class IPEndPointTest extends TestCase
{
    public function testSerializationIPv4()
    {
        $ipep = new IPEndPoint();
        $ipep->host = "127.0.0.1";
        $ipep->port = 1234;
        $this->assertSame("127.0.0.1:1234", $ipep->dbSerialize());

        $ipep = IPEndPoint::tryParse("127.0.0.1:1234");
        $this->assertSame("127.0.0.1", $ipep->host);
        $this->assertSame(1234, $ipep->port);
    }

    public function testSerializationIPv6()
    {
        $ipep = new IPEndPoint();
        $ipep->host = "2001:db8:3333:4444:5555:6666:7777:8888";
        $ipep->port = 1234;
        $this->assertSame("[2001:db8:3333:4444:5555:6666:7777:8888]:1234", $ipep->dbSerialize());

        $ipep = IPEndPoint::tryParse("[2001:db8:3333:4444:5555:6666:7777:8888]:1234");
        $this->assertSame("2001:db8:3333:4444:5555:6666:7777:8888", $ipep->host);
        $this->assertSame(1234, $ipep->port);
    }
}
