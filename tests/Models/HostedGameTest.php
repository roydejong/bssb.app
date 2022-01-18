<?php

use app\BeatSaber\MasterServer;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CVersion;
use app\Common\IPEndPoint;
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

    public function getIsDirectConnect()
    {
        $game = new HostedGame();
        $game->masterServerHost = null;
        $game->endpoint = null;
        $this->assertFalse($game->getIsDirectConnect(),
            "Games without endpoint should not be recognized as direct connect");

        $game->endpoint = new IPEndPoint("host.com", 1234);
        $this->assertTrue($game->getIsDirectConnect(),
            "Games with endpoint, but no master server, should be recognized as direct connect");

        $game->masterServerHost = "host.com";
        $this->assertFalse($game->getIsDirectConnect(),
            "Games with master server should not be recognized as direct connect");
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
        $game->masterServerHost = null;
        $game->endpoint = new IPEndPoint("host.com", 1234);
        $this->assertTrue($game->getIsDirectConnect());
        $this->assertFalse($game->getIsOfficial(),
            "Games without master server, with endpoint, are direct connect and should not be marked official");

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

        $game = new HostedGame();
        $game->ownerName = "ALI213";
        $game->ownerId = "o+DPXUXcX7WwkWcHWYzub/";
        $this->assertTrue($game->getIsPirate());
    }

    public function testGetAdjustedState_AdjustsUpwards()
    {
        $game = new HostedGame();
        $game->gameVersion = new CVersion("1.16.2");
        $game->lobbyState = 4; // Error on <= 1.16.2, GameRunning on >= 1.16.3

        $this->assertSame(MultiplayerLobbyState::Error, $game->getAdjustedState(),
            "getAdjustedState() should translate 1.16.2 states upwards for neutral observer");

        $this->assertSame(MultiplayerLobbyState::Error, $game->getAdjustedState(new CVersion("1.16.3")),
            "getAdjustedState() should translate 1.16.2 states upwards for 1.16.3 observer");

        $this->assertSame(4, $game->getAdjustedState(new CVersion("1.16.2")),
            "getAdjustedState() should NOT translate 1.16.2 states for 1.16.2 observers");
    }

    public function testGetAdjustedState_AdjustsDownwards()
    {
        $game = new HostedGame();
        $game->gameVersion = new CVersion("1.16.3");
        $game->lobbyState = 4; // Error on <= 1.16.2, GameRunning on >= 1.16.3

        $this->assertSame(MultiplayerLobbyState::GameRunning, $game->getAdjustedState(),
            "getAdjustedState() should NOT translate 1.16.3 states for neutral observer");

        $this->assertSame(MultiplayerLobbyState::GameRunning, $game->getAdjustedState(new CVersion("1.16.3")),
            "getAdjustedState() should NOT translate 1.16.3 states for 1.16.3 observer");

        $this->assertSame(3, $game->getAdjustedState(new CVersion("1.16.2")),
            "getAdjustedState() should translate 1.16.3 states downwards for 1.16.2 observer");
    }

    public function testDescribeServerType_OfficialUnofficial()
    {
        $game = new HostedGame();

        $game->masterServerHost = "partycube.pg69.edu";
        $game->serverType = HostedGame::SERVER_TYPE_NORMAL_DEDICATED;
        $this->assertSame("Unofficial Dedicated", $game->describeServerType());

        $game->masterServerHost = MasterServer::OFFICIAL_HOSTNAME_STEAM;
        $game->serverType = HostedGame::SERVER_TYPE_NORMAL_DEDICATED;
        $this->assertSame("Official Dedicated", $game->describeServerType());

        $game->masterServerHost = "partycube.pg69.edu";
        $game->serverType = HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY;
        $this->assertSame("Unofficial Quickplay", $game->describeServerType());

        $game->masterServerHost = MasterServer::OFFICIAL_HOSTNAME_PS4;
        $game->serverType = HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY;
        $this->assertSame("Official Quickplay", $game->describeServerType());
    }
}
