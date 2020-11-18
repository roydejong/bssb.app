<?php

namespace Models;

use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;

class HostedGameTest extends TestCase
{
    public function testGetIsStale()
    {
        $game = new HostedGame();
        $game->lastUpdate = new \DateTime('now');
        $this->assertFalse($game->getIsStale(), "Newly updated games should NOT be marked as stale");

        $game = new HostedGame();
        $game->lastUpdate = new \DateTime('-3 minutes');
        $this->assertFalse($game->getIsStale(), "3-minute old games should NOT be marked as stale");

        $game = new HostedGame();
        $game->lastUpdate = new \DateTime('-10 minutes');
        $this->assertTrue($game->getIsStale(), "10-minute old games SHOULD be marked as stale");
    }

    public function testGetIsOfficial()
    {
        $game = new HostedGame();
        $game->masterServerHost = "cross-play.somesite.com";
        $this->assertFalse($game->getIsOfficial(), "Games with explicit custom master server are unofficial");

        $game = new HostedGame();
        $game->masterServerHost = null;
        $this->assertTrue($game->getIsOfficial(), "Games without master server should be official");

        $game = new HostedGame();
        $game->masterServerHost = "oculus.production.mp.beatsaber.com";
        $this->assertTrue($game->getIsOfficial(), "Games on default oculus server should be official");

        $game = new HostedGame();
        $game->masterServerHost = "steam.production.mp.beatsaber.com";
        $this->assertTrue($game->getIsOfficial(), "Games on default steam server should be official");

        $game = new HostedGame();
        $game->masterServerHost = "anything.mp.beatsaber.com";
        $this->assertTrue($game->getIsOfficial(), "Games on any *.mp server should be official");
    }
}
