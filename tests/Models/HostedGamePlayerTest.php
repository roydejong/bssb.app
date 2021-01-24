<?php

namespace Models;

use app\Models\HostedGamePlayer;
use PHPUnit\Framework\TestCase;

class HostedGamePlayerTest extends TestCase
{
    public function testDescribeLatency()
    {
        $this->assertSame(
            "1234ms",
            (new HostedGamePlayer(["latency" => 1.234]))->describeLatency()
        );
        $this->assertSame(
            "1ms",
            (new HostedGamePlayer(["latency" => 0.001]))->describeLatency()
        );
        $this->assertSame(
            "0ms",
            (new HostedGamePlayer(["latency" => 0]))->describeLatency()
        );
    }
}
