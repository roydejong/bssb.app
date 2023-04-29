<?php

use app\Common\RemoteEndPoint;
use PHPUnit\Framework\TestCase;

class RemoteEndPointTest extends TestCase
{
    public function testIpv4()
    {
        $ep = new RemoteEndPoint();
        $ep->host = "127.0.0.1";
        $ep->port = 1234;
        $this->assertSame("127.0.0.1:1234", $ep->dbSerialize());

        $ep = RemoteEndPoint::tryParse("127.0.0.1:1234");
        $this->assertSame("127.0.0.1", $ep->host);
        $this->assertSame(1234, $ep->port);

        $this->assertTrue($ep->getHostIsIpAddress());
        $this->assertFalse($ep->getHostIsDnsName());
        $this->assertTrue($ep->getIsIPv4());
        $this->assertFalse($ep->getIsIPv6());
    }

    public function testIpv6()
    {
        $ep = new RemoteEndPoint();
        $ep->host = "2001:db8:3333:4444:5555:6666:7777:8888";
        $ep->port = 1234;
        $this->assertSame("[2001:db8:3333:4444:5555:6666:7777:8888]:1234", $ep->dbSerialize());

        $ep = RemoteEndPoint::tryParse("[2001:db8:3333:4444:5555:6666:7777:8888]:1234");

        $this->assertSame("2001:db8:3333:4444:5555:6666:7777:8888", $ep->host);
        $this->assertSame(1234, $ep->port);

        $this->assertTrue($ep->getHostIsIpAddress());
        $this->assertFalse($ep->getHostIsDnsName());
        $this->assertTrue($ep->getIsIPv6());
        $this->assertFalse($ep->getIsIPv4());
    }

    public function testDnsName()
    {
        $ep = new RemoteEndPoint("google.com", 1337);

        $this->assertTrue($ep->getHostIsDnsName());
        $this->assertFalse($ep->getHostIsIpAddress());

        $this->assertTrue($ep->tryResolve(), "DNS resolve should succeed");

        $this->assertFalse($ep->getHostIsDnsName());
        $this->assertTrue($ep->getHostIsIpAddress());
        $this->assertTrue($ep->getIsIPv4() || $ep->getIsIPv6());
    }
}
