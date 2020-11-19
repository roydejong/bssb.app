<?php

namespace Controllers\API;

use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CString;
use app\Controllers\API\BrowseController;
use app\HTTP\Request;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;

class BrowseControllerTest extends TestCase
{
    // -----------------------------------------------------------------------------------------------------------------
    // Setup

    public static function setUpBeforeClass(): void
    {
        self::tearDownAfterClass(); // reset

        self::createSampleGame(1, "BoringSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        self::createSampleGame(2, "BoringOculus", false, MasterServer::OFFICIAL_HOSTNAME_OCULUS, ModPlatformId::OCULUS, 1, false);
        self::createSampleGame(3, "BoringUnknown", false, null, ModPlatformId::UNKNOWN, 1, false);
        self::createSampleGame(4, "ModdedSteam", true, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        self::createSampleGame(5, "ModdedSteamCrossplayX", true, "beat.with.me", ModPlatformId::STEAM, 1, false);

        $oldSteam = self::createSampleGame(6, "OldSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, false);
        $oldSteam->lastUpdate = (clone $oldSteam->lastUpdate)->modify('-10 minutes');
        $oldSteam->firstSeen = $oldSteam->lastUpdate;
        self::assertTrue($oldSteam->getIsStale(), "Setup sanity check: OldSteam game should be stale");
        $oldSteam->save();

        self::createSampleGame(7, "BoringSteamFull", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 5, false);
        self::createSampleGame(8, "BoringSteamInProgress", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1, true);
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

    private static function createSampleGame(int $number, string $name, bool $isModded, ?string $masterServer, ?string $platform, ?int $playerCount, bool $inProgress = false): HostedGame
    {
        $hg = new HostedGame();
        $hg->serverCode = "TEST{$number}";
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
        $request->headers["user-agent"] = "ServerBrowser/0.2.0 (BeatSaber/1.12.2) (steam)";
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
        $this->assertSame(self::$createdSampleGameCount, (count($pageOne) + count($pageTwo)),
            "Pages one and two should make up all the sample games together");

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
        $this->assertSame(self::$createdSampleGameCount, count($pageCustomLimitH),
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
        $this->assertNotContainsGameWithName("BoringOculus", $lobbies);
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

        $this->assertNotContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringOculus", $lobbies);
        $this->assertContainsGameWithName("BoringUnknown", $lobbies,
            "When platform filtering, unknown games should still show as they COULD be compatible");
        $this->assertNotContainsGameWithName("ModdedSteam", $lobbies);
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
}
