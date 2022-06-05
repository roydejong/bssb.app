<?php

use app\BeatSaber\MultiplayerUserId;
use PHPUnit\Framework\TestCase;

class MultiplayerUserIdTest extends TestCase
{
    public function testHash()
    {
        $this->assertSame("w2OaHOPC6azajdWI8d1rpq",
            MultiplayerUserId::hash("Steam", "76561198002398493"));
    }
}