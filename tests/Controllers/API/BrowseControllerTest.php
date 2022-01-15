<?php

use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CString;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\Controllers\API\V1\BrowseController;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\SystemConfig;
use PHPUnit\Framework\TestCase;

class BrowseControllerTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    public static function setUpBeforeClass(): void
    {
        self::tearDownAfterClass(); // reset

        self::createSampleGame(1, "BoringSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false, serverType: HostedGame::SERVER_TYPE_NORMAL_DEDICATED);
        self::createSampleGame(2, "BoringOculus", false, MasterServer::OFFICIAL_HOSTNAME_OCULUS, ModPlatformId::OCULUS, 1, false, serverType: HostedGame::SERVER_TYPE_NORMAL_DEDICATED);
        self::createSampleGame(3, "BoringUnknown", false, null, ModPlatformId::UNKNOWN, 1, false, serverType: HostedGame::SERVER_TYPE_NORMAL_DEDICATED);
        self::createSampleGame(4, "ModdedSteam", true, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false, serverType: HostedGame::SERVER_TYPE_PLAYER_HOST);
        self::createSampleGame(5, "ModdedSteamCrossplayX", true, "beat.with.me", ModPlatformId::STEAM, 1, false, serverType: HostedGame::SERVER_TYPE_PLAYER_HOST);

        $oldSteam = self::createSampleGame(6, "OldSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        $oldSteam->lastUpdate = (clone $oldSteam->lastUpdate)->modify('-10 minutes');
        $oldSteam->firstSeen = $oldSteam->lastUpdate;
        self::assertTrue($oldSteam->getIsStale(), "Setup sanity check: OldSteam game should be stale");
        $oldSteam->save();

        self::createSampleGame(7, "BoringSteamFull", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 5, false);
        self::createSampleGame(8, "BoringSteamInProgress", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true);

        $endedSteam = self::createSampleGame(9, "EndedSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        $endedSteam->endedAt = new \DateTime('now');
        $endedSteam->save();

        self::createSampleGame(10, "1.18.1", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true, customGameVersion: new CVersion("1.18.1"));

        self::createSampleGame(0, "BadGameVersion", customGameVersion: new CVersion("1.2.3"));

        self::createSampleGame(null, "VanillaQuickPlay", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 3, false, hostSecret: "abc123", serverType: HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY, endpoint: new IPEndPoint("1.2.3.4", "1234"));
    }

    public static function tearDownAfterClass(): void
    {
        self::$sampleGames = [];

        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%"')
            ->execute();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Helper code

    private static array $sampleGames;
    private static int $createdSampleGameCount = 0;

    private static function createSampleGame(?int      $number, string $name, bool $isModded = false,
                                             ?string   $masterServer = null, ?string $platform = null,
                                             ?int      $playerCount = null, bool $inProgress = false,
                                             ?CVersion $customGameVersion = null, ?string $hostSecret = null,
                                             ?string   $serverType = null, ?IPEndPoint $endpoint = null): HostedGame
    {
        $hg = new HostedGame();

        if ($number !== null)
            if ($number >= 0 && $number < 9)
                $hg->serverCode = "TEST{$number}";
            else
                $hg->serverCode = "TST{$number}";

        $hg->playerLimit = 5;
        $hg->playerCount = $playerCount !== null ? $playerCount : 5;
        $hg->ownerId = "unit_test_{$number}";
        $hg->isModded = $isModded;
        $hg->gameName = $name;
        $hg->firstSeen = new \DateTime('now');
        $hg->lastUpdate = $hg->firstSeen;

        if ($masterServer) {
            $hg->masterServerHost = $masterServer;
            $hg->masterServerPort = 1234;

            if (CString::startsWith($masterServer, "steam.")) {
                $hg->platform = ModPlatformId::STEAM;
            } else if (CString::startsWith($masterServer, "oculus.")) {
                $hg->platform = ModPlatformId::OCULUS;
            }
        } else {
            $hg->platform = ModPlatformId::UNKNOWN;
        }

        if ($platform) {
            $hg->platform = $platform;
        }

        if ($inProgress) {
            $hg->lobbyState = MultiplayerLobbyState::GameRunning;
            $hg->levelId = "custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7";
            $hg->songName = "Song";
            $hg->songAuthor = "Author";
        }

        $hg->gameVersion = $customGameVersion ? $customGameVersion : new CVersion("1.12.2");
        $hg->modVersion = new CVersion("0.2.0");

        $hg->serverType = $serverType;
        $hg->hostSecret = $hostSecret;
        $hg->endpoint = $endpoint;

        $hg->save();

        self::$createdSampleGameCount++;
        return $hg;
    }

    private static function createBrowseRequest(array $queryParams = []): Request
    {
        if (!isset($queryParams['limit'])) {
            $queryParams['limit'] = PHP_INT_MAX;
        }

        $request = new Request();
        $request->protocol = "https";
        $request->host = "test.wssl.app";
        $request->path = "/api/v1/browse";
        $request->method = "GET";
        $request->headers["user-agent"] = "ServerBrowser/0.7.0 (BeatSaber/1.12.2) (steam)";
        $request->headers["x-bssb"] = "1";
        $request->queryParams = $queryParams;
        return $request;
    }

    private static function executeBrowseRequestAndGetGames(Request $browseRequest): array
    {
        $response = (new BrowseController())->browse($browseRequest);

        self::assertInstanceOf("app\HTTP\Responses\JsonResponse", $response,
            "Sanity check failed: did not get a valid JSON response from browse()");

        $jsonResult = json_decode($response->body, true);

        self::assertArrayHasKey("Lobbies", $jsonResult,
            "Sanity check failed: did not get a Lobbies list in JSON response from browse()");

        return $jsonResult['Lobbies'];
    }

    private function assertContainsGameWithName(string $gameName, array $Lobbies, ?string $message = null)
    {
        $foundMatch = false;

        foreach ($Lobbies as $lobby)
            if ($lobby['gameName'] === $gameName)
                $foundMatch = true;

        $finalMessage = "Failed to assert that the game list contains a game named \"{$gameName}\"";
        if ($message) $finalMessage = $message . "\r\n{$finalMessage}";

        $this->assertTrue($foundMatch, $finalMessage);
    }

    private function assertNotContainsGameWithName(string $gameName, array $Lobbies, ?string $message = null)
    {
        $foundMatch = false;

        foreach ($Lobbies as $lobby)
            if ($lobby['gameName'] === $gameName)
                $foundMatch = true;

        $finalMessage = "Failed to assert that the game list does NOT contain a game named \"{$gameName}\"";
        if ($message) $finalMessage = $message . "\r\n{$finalMessage}";

        $this->assertFalse($foundMatch, $finalMessage);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Tests actual

    public function testRejectsNonModRequests()
    {
        $request = self::createBrowseRequest();
        $request->headers["user-agent"] = "Gogglebot";

        $response = (new BrowseController())
            ->browse($request);

        $this->assertInstanceOf("app\HTTP\Responses\BadRequestResponse", $response);
    }

    public function testBrowseSimple()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "limit" => PHP_INT_MAX
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies,
            "Browse without params: should see games on all platforms");
        $this->assertContainsGameWithName("BoringOculus", $lobbies,
            "Browse without params: should see games on all platforms");
        $this->assertContainsGameWithName("BoringUnknown", $lobbies,
            "Browse without params: should see games on all platforms, even unknown");
        $this->assertContainsGameWithName("ModdedSteam", $lobbies,
            "Browse without params: should see games on all platforms, even modded");
        $this->assertContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "Browse without params: should see games on all platforms, even modded cross-play");
        $this->assertNotContainsGameWithName("OldSteam", $lobbies,
            "Browse: should never see old games");
        $this->assertContainsGameWithName("BoringSteamFull", $lobbies,
            "Browse: should see full games by default");
        $this->assertNotContainsGameWithName("EndedSteam", $lobbies,
            "Browse: should see not see ended games at all");
    }

    public function testBrowsePagination()
    {
        $pageOne = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "offset" => 0,
                "limit" => ""
            ])
        );

        $this->assertSame(BrowseController::PAGE_SIZE, count($pageOne),
            "When limit is not explicitly specified, it should default to PAGE_SIZE");

        $pageTwo = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "offset" => BrowseController::PAGE_SIZE,
                "limit" => 999
            ])
        );

        $this->assertNotSame($pageOne, $pageTwo);

        // NB: -1 for OldSteam, -1 for EndedSteam, -1 for BadGameVersion, -1 for 1.18.1
        $expectedTotalItems = self::$createdSampleGameCount - 4;

        $this->assertGreaterThanOrEqual($expectedTotalItems, count($pageOne) + count($pageTwo),
            "Pages one and two should make up the sample game count together");

        $pageCustomLimitL = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "offset" => 0,
                "limit" => 3
            ])
        );
        $this->assertSame(3, count($pageCustomLimitL),
            "Custom page limits should work correctly (low)");

        $pageCustomLimitH = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "offset" => 0,
                "limit" => 999
            ])
        );
        $this->assertGreaterThanOrEqual($expectedTotalItems, count($pageCustomLimitH),
            "Custom page limits should work correctly (high)");
    }

    public function testBrowseSearch()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "query" => "x"
            ])
        );

        $this->assertNotContainsGameWithName("BoringSteam", $lobbies);
        $this->assertNotContainsGameWithName("BoringOculus", $lobbies);
        $this->assertNotContainsGameWithName("BoringUnknown", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertNotContainsGameWithName("BadGameVersion", $lobbies);
        $this->assertContainsGameWithName("ModdedSteamCrossplayX", $lobbies);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseVanillaHidesModdedGames()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "vanilla" => "1"
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies,
            "Browse with vanilla: should see boring games on all platforms");
        $this->assertContainsGameWithName("BoringOculus", $lobbies,
            "Browse with vanilla: should see boring games on all platforms");
        $this->assertContainsGameWithName("BoringUnknown", $lobbies,
            "Browse with vanilla: should see boring games on all platforms, even unknown");
        $this->assertNotContainsGameWithName("ModdedSteam", $lobbies,
            "Browse with vanilla: should NOT see modded games on any platform");
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "Browse with vanilla: should NOT see modded games on any platform");
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseSteamPlatformFiltering()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "platform" => "steam"
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringOculus", $lobbies, "Cross-play should be enabled");
        $this->assertContainsGameWithName("BoringUnknown", $lobbies,
            "When platform filtering, unknown games should still show as they COULD be compatible");
        $this->assertContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertContainsGameWithName("ModdedSteamCrossplayX", $lobbies);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseOculusPlatformFiltering()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "platform" => "oculus"
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies, "Cross-play should be enabled");
        $this->assertContainsGameWithName("BoringOculus", $lobbies);
        $this->assertContainsGameWithName("BoringUnknown", $lobbies,
            "When platform filtering, unknown games should still show as they COULD be compatible");
        $this->assertContainsGameWithName("ModdedSteam", $lobbies, "Cross-play should be enabled");
        $this->assertContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "When platform filtering, cross-play servers should never be excluded");
    }

    /**
     * @depends testBrowseSimple
     */
    public function testOldModVersionHidesCrossplayGames()
    {
        $request = self::createBrowseRequest();
        $request->headers["user-agent"] = "ServerBrowser/0.1.1.0 (BeatSaber/1.12.2)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringOculus", $lobbies);
        $this->assertContainsGameWithName("BoringUnknown", $lobbies);
        $this->assertContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "When using mod version <0.2, cross play servers should be hidden");
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseFilterFull()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterFull" => true
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringSteamInProgress", $lobbies);
        $this->assertNotContainsGameWithName("BoringSteamFull", $lobbies);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseFilterInProgress()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterInProgress" => true
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringSteamFull", $lobbies);
        $this->assertNotContainsGameWithName("BoringSteamInProgress", $lobbies);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testFilterModded()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterModded" => true
            ])
        );

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringSteamFull", $lobbies);
        $this->assertContainsGameWithName("BoringSteamInProgress", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testServerMessage()
    {
        $sysConfig = SystemConfig::fetchInstance();
        $sysConfig->serverMessage = "Test message!";

        $request = self::createBrowseRequest();

        $response = (new BrowseController())->browse($request);
        $responseJson = json_decode($response->body, true);

        $this->assertArrayHasKey("Message", $responseJson);
        $this->assertSame("Test message!", $responseJson["Message"]);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseApiHidesOwnerIds()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterModded" => true
            ])
        );
        $aLobby = $lobbies[0];

        $this->assertArrayHasKey("ownerName", $aLobby);
        $this->assertArrayNotHasKey("ownerId", $aLobby);
    }

    /**
     * @depends testBrowseSimple
     */
    public function testBrowseVersionFiltering()
    {
        $request = self::createBrowseRequest();
        $request->headers["user-agent"] = "ServerBrowser/0.2.0 (BeatSaber/1.2.3) (steam)";

        $lobbies = self::executeBrowseRequestAndGetGames($request);

        $this->assertCount(1, $lobbies, "We should only get one result for this game version");
        $this->assertContainsGameWithName("BadGameVersion", $lobbies, "We should only get the single matching game for this version");
    }

    /**
     * @depends testBrowseVersionFiltering
     */
    public function testBrowseVersionFiltering_Aliased()
    {
        $request = self::createBrowseRequest();
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.18.0) (steam)";

        $lobbies = self::executeBrowseRequestAndGetGames($request);

        $this->assertContainsGameWithName("1.18.1", $lobbies,
            "We should get 1.18.1 result for 1.18.0 request (alias)");
    }

    /**
     * @depends testBrowseSimple
     */
    public function testOldVersionFiltersQuickPlay()
    {
        $request = self::createBrowseRequest(['platform' => 'steam']);
        $request->headers["user-agent"] = "ServerBrowser/0.6.0 (BeatSaber/1.12.2) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertNotContainsGameWithName("VanillaQuickPlay", $lobbies,
            "ServerBrowser 0.6.0 should NOT return Quick Play games");

        $request = self::createBrowseRequest(['platform' => 'steam']);
        $request->headers["user-agent"] = "ServerBrowser/0.7.0 (BeatSaber/1.12.2) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertContainsGameWithName("VanillaQuickPlay", $lobbies,
            "ServerBrowser 0.7.0 SHOULD return Quick Play games");
    }

    public function testQuestBrowserFiltersOfficialServers()
    {
        $request = self::createBrowseRequest(['platform' => 'steam']);
        $request->headers["user-agent"] = "ServerBrowserQuest/1.0.0 (BeatSaber/1.12.2) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertNotContainsGameWithName("VanillaQuickPlay", $lobbies,
            "ServerBrowserQuest should NOT return Vanilla Quick Play (official) games");
        $this->assertNotContainsGameWithName("BoringOculus", $lobbies,
            "ServerBrowserQuest should NOT return Vanilla Dedicated (official) games");
        $this->assertContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "ServerBrowserQuest SHOULD return Player Hosted Cross-play games for the current version");
    }

    public function testExplicitServerTypeFilter()
    {
        $request = self::createBrowseRequest([
            'platform' => 'steam',
            'filterServerType' => HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY
        ]);
        $request->headers["user-agent"] = "ServerBrowser/0.7.0 (BeatSaber/1.12.2) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);

        $this->assertContainsGameWithName("VanillaQuickPlay", $lobbies);
    }

    /**
     * @depends testExplicitServerTypeFilter
     */
    public function testEndpointSerialization()
    {
        $request = self::createBrowseRequest([
            'platform' => 'steam',
            'filterServerType' => HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY
        ]);
        $request->headers["user-agent"] = "ServerBrowser/0.7.0 (BeatSaber/1.12.2) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);

        $vanillaQuickPlay = reset($lobbies);
        $this->assertSame("1.2.3.4:1234", $vanillaQuickPlay['endpoint']);
    }
}
