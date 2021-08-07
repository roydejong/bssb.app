<?php

use app\BeatSaber\MasterServer;
use app\BeatSaber\MultiplayerLobbyState;
use app\Controllers\API\UnAnnounceController;
use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;

class UnAnnounceControllerTest extends TestCase
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
            ->where('owner_id LIKE "unit_test_%"')
            ->execute();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Helpers

    private function createUnAnnounceRequest(?string $ownerId, ?string $serverId = null): Request
    {
        $request = new Request();
        $request->queryParams["ownerId"] = $ownerId;
        $request->queryParams["serverId"] = $serverId;
        $request->method = "POST";
        $request->path = "/api/v1/unannounce";
        $request->headers["x-bssb"] = "1";
        $request->headers["user-agent"] = "ServerBrowser/0.2.0 (BeatSaber/1.12.2) (steam)";
        return $request;
    }

    private function sendUnAnnounceRequest(?string $ownerId, ?string $serverId = null): Response
    {
        $request = self::createUnAnnounceRequest($ownerId, $serverId);

        $controller = new UnAnnounceController();
        return $controller->unAnnounce($request);
    }

    private static function createSampleGame(string $nameAndCode, string $ownerId): HostedGame
    {
        $hg = new HostedGame();
        $hg->serverCode = $nameAndCode;
        $hg->gameName = $nameAndCode;
        $hg->ownerId = $ownerId;
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
        return $hg;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tests actual

    public function testUnAnnounceSimple()
    {
        $ownerId = "unit_test_testUnAnnounceSimple";

        $testGameBefore = self::createSampleGame("SIMPL", $ownerId);
        $this->assertNull($testGameBefore->endedAt);

        $response = $this->sendUnAnnounceRequest($ownerId, null);
        $this->assertSame(200, $response->code);

        $responseJson = json_decode($response->body, true);
        $this->assertSame("ok", $responseJson["result"]);

        $testGameAfter = HostedGame::fetch($testGameBefore->id);
        $this->assertNotNull($testGameAfter->endedAt);
    }

    /**
     * @depends testUnAnnounceSimple
     */
    public function testUnAnnounceMultiple()
    {
        $ownerId = "unit_test_testUnAnnounceMultiple";

        $testGameBefore1 = self::createSampleGame("MULT1", $ownerId);
        $testGameBefore2 = self::createSampleGame("MULT2", $ownerId);

        $this->sendUnAnnounceRequest($ownerId, null);

        $testGameAfter1 = HostedGame::fetch($testGameBefore1->id);
        $testGameAfter2 = HostedGame::fetch($testGameBefore2->id);

        $this->assertNotNull($testGameAfter1->endedAt);
        $this->assertNotNull($testGameAfter2->endedAt);
    }

    /**
     * @depends testUnAnnounceSimple
     */
    public function testUnAnnounceNoOp()
    {
        $response = $this->sendUnAnnounceRequest("invalid", "invalid");

        $this->assertSame(200, $response->code);

        $responseJson = json_decode($response->body, true);
        $this->assertSame("noop", $responseJson["result"]);
    }

    /**
     * @depends testUnAnnounceNoOp
     */
    public function testUnAnnounceBadMethod()
    {
        $request = self::createUnAnnounceRequest("invalid", "invalid");
        $request->method = "GET";

        $controller = new UnAnnounceController();
        $response = $controller->unAnnounce($request);

        $this->assertSame(400, $response->code);
    }

    /**
     * @depends testUnAnnounceNoOp
     */
    public function testUnAnnounceBadClient()
    {
        $request = self::createUnAnnounceRequest("invalid", "invalid");
        $request->headers["user-agent"] = "😁";

        $controller = new UnAnnounceController();
        $response = $controller->unAnnounce($request);

        $this->assertSame(400, $response->code);
    }

    /**
     * @depends testUnAnnounceNoOp
     */
    public function testUnAnnounceBadOwnerId()
    {
        $request = self::createUnAnnounceRequest("SERVER_MESSAGE");

        $controller = new UnAnnounceController();
        $response = $controller->unAnnounce($request);

        $this->assertSame(400, $response->code);
    }
}
