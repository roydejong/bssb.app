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

    public function testGetIsUninteresting()
    {
        $game = new HostedGame();
        $game->masterServerHost = "localhost";
        $this->assertTrue($game->getIsUninteresting(), "Some specific host names are uninteresting");
        $game->masterServerHost = "127.0.0.1";
        $this->assertTrue($game->getIsUninteresting(), "Some specific host names are uninteresting");
        $game->masterServerHost = "any.other.host.really";
        $this->assertFalse($game->getIsUninteresting());
    }

    public function testSerializeConcealsOwnerId()
    {
        $game = new HostedGame();
        $game->ownerName = "test";
        $game->ownerId = "test";

        $sz = $game->jsonSerialize();

        $this->assertArrayHasKey("ownerName", $sz);
        $this->assertArrayNotHasKey("ownerId", $sz);
    }

    public function testIsPirate()
    {
        $game = new HostedGame();
        $game->ownerName = "test";
        $game->ownerId = "test";
        $this->assertFalse($game->getIsPirate());

        $game = new HostedGame();
        $game->ownerName = "CODEX";
        $game->ownerId = "mqsC892YHEEG91QeFPnNN1";
        $this->assertTrue($game->getIsPirate());
    }
}
