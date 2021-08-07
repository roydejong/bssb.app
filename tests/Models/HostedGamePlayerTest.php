<?php

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

    public function testIsPirate()
    {
        $player = new HostedGamePlayer();
        $player->userId = "test";
        $player->userName = "test";
        $this->assertFalse($player->getIsPirate());

        $player = new HostedGamePlayer();
        $player->userId = "mqsC892YHEEG91QeFPnNN1";
        $player->userName = "CODEX";
        $this->assertTrue($player->getIsPirate());

        $player = new HostedGamePlayer();
        $player->userId = "o+DPXUXcX7WwkWcHWYzub/";
        $player->userName = "ALI213";
        $this->assertTrue($player->getIsPirate());
    }

    public function testIsBot()
    {
        $player = new HostedGamePlayer();
        $player->userId = "mqsC892YHEEG91QeFPnNN1";
        $player->userName = "CODEX";
        $this->assertFalse($player->getIsBot());

        $player = new HostedGamePlayer();
        $player->userId = "BeatDedi/abcdef";
        $player->userName = "BeatDedi";
        $this->assertTrue($player->getIsBot());

        $player = new HostedGamePlayer();
        $player->userId = "DEVBOT/2";
        $player->userName = "DEVBOT/2";
        $this->assertTrue($player->getIsBot());

        $player = new HostedGamePlayer();
        $player->userId = "BottyMcBot/RM4KPY295PX";
        $player->userName = "BottyMcBotFace";
        $this->assertTrue($player->getIsBot());
    }
}
