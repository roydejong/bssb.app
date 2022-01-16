<?php

use app\BeatSaber\MasterServer;
use app\BeatSaber\MultiplayerLobbyState;
use app\Controllers\API\V2\UnAnnounceControllerV2;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockJsonRequest;

class UnAnnounceControllerV2Test extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    public static function setUpBeforeClass(): void
    {
        self::tearDownAfterClass();
    }

    public static function tearDownAfterClass(): void
    {
        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%" OR server_code = "SIMPL"')
            ->execute();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Helpers

    private function createUnAnnounceRequest(?string $selfUserId, ?string $hostUserId, ?string $hostSecret): Request
    {
        $request = new MockJsonRequest([
            "SelfUserId" => $selfUserId,
            "HostUserId" => $hostUserId,
            "HostSecret" => $hostSecret
        ]);

        $request->method = "POST";
        $request->path = "/api/v2/unannounce";

        $request->headers["x-bssb"] = "1";
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.19.0) (steam)";
        $request->headers["content-type"] = "application/json";

        return $request;
    }

    private function sendUnAnnounceRequest(?string $selfUserId, ?string $hostUserId, ?string $hostSecret): Response
    {
        $request = self::createUnAnnounceRequest($selfUserId, $hostUserId, $hostSecret);

        $controller = new UnAnnounceControllerV2();
        return $controller->unAnnounce($request);
    }

    private static function createSampleGame(string $nameAndCode, ?string $selfUserId, ?string $hostUserId,
                                             ?string $hostSecret): HostedGame
    {
        $hg = new HostedGame();
        $hg->serverCode = $nameAndCode;
        $hg->gameName = $nameAndCode;
        $hg->ownerId = $hostUserId;
        $hg->hostSecret = $hostSecret;
        $hg->playerLimit = 3;
        $hg->playerCount = 5;
        $hg->isModded = true;
        $hg->firstSeen = new \DateTime('now');
        $hg->lastUpdate = $hg->firstSeen;
        $hg->masterServerHost = MasterServer::OFFICIAL_HOSTNAME_STEAM;
        $hg->masterServerPort = 1234;
        $hg->platform = "steam";
        $hg->lobbyState = MultiplayerLobbyState::GameRunning;
        $hg->levelId = "custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7";
        $hg->songName = "Song";
        $hg->songAuthor = "Author";
        $hg->save();

        $hgp = new HostedGamePlayer();
        $hgp->hostedGameId = $hg->id;
        $hgp->userId = $selfUserId;
        $hgp->userName = $selfUserId;
        $hgp->isAnnouncer = true;
        $hgp->isConnected = true;
        $hgp->latency = 123;
        $hgp->save();

        return $hg;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tests actual

    public function testUnAnnounceSimple()
    {
        $selfId = "tuas_self";
        $hostId = "tuas_host";
        $hostSecret = "tuas_secret";

        $sampleGame = $this->createSampleGame("SIMPL", $selfId, $hostId, $hostSecret);
        $this->assertNull($sampleGame->endedAt);

        $response = $this->sendUnAnnounceRequest($selfId, $hostId, $hostSecret);
        $this->assertSame(200, $response->code, "Simple unannounce should respond 200 OK");

        $responseJson = json_decode($response->body, true);
        $this->assertIsArray($responseJson, "Simple unannounce should respond with valid JSON payload");
        $this->assertSame("ok", $responseJson["result"], "Simple unannounce should succeed");

        $sampleGamev2 = HostedGame::fetch($sampleGame->id); // reload
        $this->assertNotNull($sampleGamev2->endedAt, "Post-unAnnounce, game should be ended");
    }
}
