<?php

namespace Controllers\API;

use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
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

        self::createSampleGame(1, "BoringSteam", false, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1);
        self::createSampleGame(2, "BoringOculus", false, MasterServer::OFFICIAL_HOSTNAME_OCULUS, ModPlatformId::OCULUS, 1);
        self::createSampleGame(3, "BoringUnknown", false, null, ModPlatformId::UNKNOWN, 1);
        self::createSampleGame(4, "ModdedSteam", true, MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1);
        self::createSampleGame(5, "ModdedSteamCrossplayX", true, "beat.with.me", ModPlatformId::STEAM, 1);

        $oldSteam = self::createSampleGame(6, "OldSteam", false,
            MasterServer::OFFICIAL_HOSTNAME_STEAM, ModPlatformId::STEAM, 1);
        $oldSteam->lastUpdate = (clone $oldSteam->lastUpdate)->modify('-10 minutes');
        $oldSteam->firstSeen = $oldSteam->lastUpdate;
        $oldSteam->save();

        self::assertTrue($oldSteam->getIsStale(), "Setup sanity check: OldSteam game should be stale");
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

    private static function createSampleGame(int $number, string $name, bool $isModded, ?string $masterServer, ?string $platform, ?int $playerCount): HostedGame
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

        $hg->save();
        return $hg;
    }

    private static function createBrowseRequest(array $queryParams = [])
    {
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
        $response = (new BrowseController())
            ->browse(self::createBrowseRequest());

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

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
    }

    public function testBrowseSearch()
    {
        $response = (new BrowseController())
            ->browse(self::createBrowseRequest([
                "query" => "x"
            ]));

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

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
        $response = (new BrowseController())
            ->browse(self::createBrowseRequest([
                "vanilla" => "1"
            ]));

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

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
        $response = (new BrowseController())
            ->browse(self::createBrowseRequest([
                "platform" => "steam"
            ]));

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

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
        $response = (new BrowseController())
            ->browse(self::createBrowseRequest([
                "platform" => "oculus"
            ]));

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

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

        $response = (new BrowseController())->browse($request);

        $this->assertInstanceOf("app\HTTP\Responses\JsonResponse", $response);

        $jsonResult = json_decode($response->body, true);
        $lobbies = $jsonResult['Lobbies'];

        $this->assertContainsGameWithName("BoringSteam", $lobbies);
        $this->assertContainsGameWithName("BoringOculus", $lobbies);
        $this->assertContainsGameWithName("BoringUnknown", $lobbies);
        $this->assertContainsGameWithName("ModdedSteam", $lobbies);
        $this->assertNotContainsGameWithName("ModdedSteamCrossplayX", $lobbies,
            "When using mod version <0.2, cross play servers should be hidden");
    }
}
