<?php

use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CString;
use app\Common\CVersion;
use app\Common\RemoteEndPoint;
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
        self::createSampleGame(3, "BoringUnknown", false, "un.known.host", ModPlatformId::UNKNOWN, 1, false, serverType: HostedGame::SERVER_TYPE_NORMAL_DEDICATED);
        self::createSampleGame(4, "ModdedSteam", true, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false, serverType: HostedGame::SERVER_TYPE_PLAYER_HOST);
        self::createSampleGame(5, "ModdedSteamCrossplayX", true, "beat.with.me", ModPlatformId::STEAM, 1, false, serverType: HostedGame::SERVER_TYPE_PLAYER_HOST);

        $oldSteam = self::createSampleGame(6, "OldSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        $oldSteam->lastUpdate = (clone $oldSteam->lastUpdate)->modify('-10 minutes');
        $oldSteam->isStale = true;
        $oldSteam->firstSeen = $oldSteam->lastUpdate;
        self::assertTrue($oldSteam->getIsStale(), "Setup sanity check: OldSteam game should be stale");
        $oldSteam->save();

        self::createSampleGame(7, "BoringSteamFull", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 5, false);
        self::createSampleGame(8, "BoringSteamInProgress", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true);

        $endedSteam = self::createSampleGame(9, "EndedSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        $endedSteam->endedAt = new \DateTime('now');
        $endedSteam->save();

        self::createSampleGame(10, "1.18.1", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true, customGameVersion: new CVersion("1.18.1"));

        self::createSampleGame(11, "1.19.1_Vanilla", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true, customGameVersion: new CVersion("1.19.1"), serverType: HostedGame::SERVER_TYPE_NORMAL_DEDICATED);
        self::createSampleGame(12, "1.19.1_QuickPlay_BadSecret", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true, customGameVersion: new CVersion("1.19.1"), serverType: HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY, hostSecret: "arn:aws:gamelift:us-west-2::gamesession/fleet-5bfdb18f-8f71-4bf4-9eaa-f78e7a1d41d7/eu-central-1/b6f03183-6a6c-4148-b7ef-dba793eb6fc1", ownerId: "arn:aws:gamelift:us-west-2::gamesession/fleet-5bfdb18f-8f71-4bf4-9eaa-f78e7a1d41d7/eu-central-1/b6f03183-6a6c-4148-b7ef-dba793eb6fc1");
        self::createSampleGame(13, "1.19.1_QuickPlay_GoodSecret", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true, customGameVersion: new CVersion("1.19.1"), serverType: HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY, hostSecret: "eHKfkIsz2UWazA/cet/AQQ");

        self::createSampleGame(0, "BadGameVersion", masterServer: "some.master.server", customGameVersion: new CVersion("1.2.3"));

        self::createSampleGame(null, "VanillaQuickPlay", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 3, false, hostSecret: "abc123", serverType: HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY, endpoint: new RemoteEndPoint("1.2.3.4", "1234"));

        self::createSampleGame(null, "DirectConnect", true, null, ModPlatformId::STEAM, 0, customGameVersion: new CVersion("1.23.0"), serverType: HostedGame::SERVER_TYPE_BEATDEDI_CUSTOM, endpoint: new RemoteEndPoint("1.2.3.4", "1234"));

        self::createSampleGame(null, "129GraphGame", true, null, ModPlatformId::STEAM, 0, customGameVersion: new CVersion("1.29.0"), serverType: HostedGame::SERVER_TYPE_BEATTOGETHER_DEDICATED, endpoint: new RemoteEndPoint("1.2.3.4", "1234"), graphUrl: "http://beat.some.url:1234");
    }

    public static function tearDownAfterClass(): void
    {
        self::$sampleGames = [];

        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%" OR manager_id LIKE "unit_test%"')
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
                                             ?string   $serverType = null, ?RemoteEndPoint $endpoint = null,
                                             ?string   $ownerId = null, ?string $graphUrl = null): HostedGame
    {
        $hg = new HostedGame();

        if ($number !== null)
            if ($number >= 0 && $number < 9)
                $hg->serverCode = "TEST{$number}";
            else
                $hg->serverCode = "TST{$number}";

        $hg->playerLimit = 5;
        $hg->playerCount = $playerCount !== null ? $playerCount : 5;
        $hg->ownerId = $ownerId ?? "unit_test_{$number}";
        $hg->isModded = $isModded;
        $hg->gameName = $name;
        $hg->firstSeen = new \DateTime('now');
        $hg->lastUpdate = $hg->firstSeen;

        if ($graphUrl) {
            $hg->masterGraphUrl = $graphUrl;
            $hg->masterServerHost = parse_url($graphUrl)['host'];
            $hg->masterServerPort = null;
            $hg->platform = ModPlatformId::STEAM;
        } else if ($masterServer) {
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

        $hg->managerId = "unit_test";

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
        $request->headers["user-agent"] = "ServerBrowser/1.1.0 (BeatSaber/1.12.2) (steam)";
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
        $this->assertNotContainsGameWithName("BoringUnknown", $lobbies,
            "When using mod version <0.2, custom master servers should be hidden");
        $this->assertContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "When using mod version <0.2, custom master servers should be hidden");
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

    public function testModernBrowserFiltersGameLiftQuickPlayServers()
    {
        $request = self::createBrowseRequest(['platform' => 'steam']);
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.19.1) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertNotContainsGameWithName("1.19.1_QuickPlay_BadSecret", $lobbies,
            "ServerBrowser should NOT return Vanilla Quick Play (official/GameLift) games on 1.19.1+ if they have a GameLift secret");
        $this->assertContainsGameWithName("1.19.1_QuickPlay_GoodSecret", $lobbies,
            "ServerBrowser SHOULD return Vanilla Quick Play (official/GameLift) games on 1.19.1+ if they have a distinct secret");
        $this->assertContainsGameWithName("1.19.1_Vanilla", $lobbies,
            "ServerBrowser SHOULD return Vanilla Dedicated (official) games on 1.19.1+");
    }

    public function testOlderBrowserFiltersDirectConnectServers()
    {
        $request = self::createBrowseRequest([]);
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.23.0) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertNotContainsGameWithName("DirectConnect", $lobbies,
            "ServerBrowser should NOT return Direct Connect games on mod < 1.1.0");
    }

    public function testNewerBrowserIncludesDirectConnectServers()
    {
        $request = self::createBrowseRequest([]);
        $request->headers["user-agent"] = "ServerBrowser/1.4.0 (BeatSaber/1.23.0) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertContainsGameWithName("DirectConnect", $lobbies,
            "ServerBrowser SHOULD return Direct Connect games on mod >= 1.1.0");
    }

    public function testModernClientRequiresGraphUrlServers()
    {
        $request = self::createBrowseRequest([]);
        $request->headers["user-agent"] = "ServerBrowser/2.0.0 (BeatSaber/1.29.0) (steam)";
        $lobbies = self::executeBrowseRequestAndGetGames($request);
        $this->assertContainsGameWithName("129GraphGame", $lobbies,
            "ServerBrowser SHOULD return 1.29 games with a graph URL");
        $this->assertNotContainsGameWithName("DirectConnect", $lobbies,
            "ServerBrowser SHOULD NOT return Direct Connect games for 1.29 (not yet supported... TODO)");
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "ServerBrowser SHOULD NOT return legacy master server games for 1.29");
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
        $this->assertSame("steam.production.mp.beatsaber.com", $vanillaQuickPlay['masterServerHost']);
        $this->assertSame(1234, $vanillaQuickPlay['masterServerPort']);
        $this->assertSame("steam.production.mp.beatsaber.com:1234", $vanillaQuickPlay['masterServerEp']);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // "includeLevel"

    /**
     * @depends testBrowseSimple
     */
    public function testIncludeLevelOn()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterFull" => true,
                "includeLevel" => 1
            ])
        );

        $this->assertContainsGameWithName("BoringSteamInProgress", $lobbies);

        $foundFilledGame = false;

        foreach ($lobbies as $lobby) {
            $this->assertArrayHasKey('level', $lobby);

            if (is_array($lobby['level'])) {
                $this->assertArrayHasKey('levelId', $lobby['level']);
                $this->assertArrayHasKey('songName', $lobby['level']);
                $this->assertArrayHasKey('songSubName', $lobby['level']);
                $this->assertArrayHasKey('songAuthorName', $lobby['level']);
                $this->assertArrayHasKey('levelAuthorName', $lobby['level']);
                $this->assertArrayHasKey('coverUrl', $lobby['level']);
                $foundFilledGame = true;
            }

            $this->assertArrayNotHasKey('beatsaverId', $lobby);
            $this->assertArrayNotHasKey('coverUrl', $lobby);
            $this->assertArrayNotHasKey('levelName', $lobby);
            $this->assertArrayNotHasKey('levelId', $lobby);
            $this->assertArrayNotHasKey('songName', $lobby);
            $this->assertArrayNotHasKey('songAuthor', $lobby);

            $this->assertArrayHasKey('characteristic', $lobby);
            $this->assertArrayHasKey('difficulty', $lobby);
        }

        $this->assertTrue($foundFilledGame, "Sanity check: at least one level entry should be filled during test");
    }

    /**
     * @depends testBrowseSimple
     */
    public function testIncludeLevelOff()
    {
        $lobbies = self::executeBrowseRequestAndGetGames(
            self::createBrowseRequest([
                "filterFull" => true
            ])
        );

        $this->assertContainsGameWithName("BoringSteamInProgress", $lobbies);

        foreach ($lobbies as $lobby) {
            $this->assertArrayNotHasKey('level', $lobby);

            $this->assertArrayHasKey('beatsaverId', $lobby);
            $this->assertArrayHasKey('coverUrl', $lobby);
            $this->assertArrayHasKey('levelName', $lobby);
            $this->assertArrayHasKey('levelId', $lobby);
            $this->assertArrayHasKey('songName', $lobby);
            $this->assertArrayHasKey('songAuthor', $lobby);

            $this->assertArrayHasKey('characteristic', $lobby);
            $this->assertArrayHasKey('difficulty', $lobby);
        }
    }
}
