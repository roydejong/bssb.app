<?php

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\MasterServer;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\Controllers\API\V1\AnnounceController;
use app\Models\HostedGame;
use app\Models\LevelRecord;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockJsonRequest;

class AnnounceControllerTest extends TestCase
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
    // Tests actual

    private static ?HostedGame $fullAnnounceTestResult;

    public function testFullAnnounce()
    {
        $TEST_LEVEL_ID = "custom_level_6D4021498979AB7C07D430C488C24DE45EEDADB4";

        /**
         * @var $levelRecord LevelRecord|null
         */
        $levelRecord = LevelRecord::query()
            ->where('level_id = ?', $TEST_LEVEL_ID)
            ->querySingleModel();
        $prevLevelPlayCount = 0;
        if ($levelRecord) {
            $prevLevelPlayCount =  $levelRecord->statPlayCount;
        }

        // -------------------------------------------------------------------------------------------------------------
        // Create request

        $request = new MockJsonRequest([
            'ServerCode' => 'ABC12',
            'GameName' => 'My Game',
            'OwnerId' => 'unit_test_testFullAnnounce',
            'OwnerName' => 'My Name',
            'PlayerCount' => 3,
            'PlayerLimit' => 5,
            'IsModded' => true,
            'LobbyState' => MultiplayerLobbyState::GameRunning,
            'LevelId' => $TEST_LEVEL_ID,
            'SongName' => '"It\'s a me, Mario!" - Super Mario 64',
            'SongAuthor' => "GilvaSunner",
            'Difficulty' => LevelDifficulty::Easy,
            'Platform' => ModPlatformId::STEAM,
            'MasterServerHost' => "custom-server.com",
            'MasterServerPort' => 2328,
            'MpExVersion' => '1.2.3.4.5',
            'ServerType' => HostedGame::SERVER_TYPE_NORMAL_DEDICATED,
            'HostSecret' => 'abc1234',
            'Endpoint' => '127.0.0.1:2312',
            'ManagerId' => 'unit_test_testFullAnnounceMgr'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";
        $request->headers["user-agent"] = "ServerBrowser/4.2.0 (BeatSaber/6.9.42) (steam)";

        // -------------------------------------------------------------------------------------------------------------
        // Test basic response

        $response = (new AnnounceController())->announce($request);

        $this->assertSame(200, $response->code);
        $this->assertStringStartsWith("application/json", $response->headers["content-type"]);

        $responseJson = json_decode($response->body, true);

        $this->assertSame(true, $responseJson['success']);
        $this->assertIsString($announceKey = $responseJson['key']);

        // -------------------------------------------------------------------------------------------------------------
        // Test data written to db

        $announceId = HostedGame::hash2id($announceKey);
        $announce = HostedGame::fetch($announceId);

        $this->assertSame("ABC12", $announce->serverCode);
        $this->assertSame("My Game", $announce->gameName);
        $this->assertSame("unit_test_testFullAnnounce", $announce->ownerId);
        $this->assertSame("My Name", $announce->ownerName);
        $this->assertSame(3, $announce->playerCount);
        $this->assertSame(5, $announce->playerLimit);
        $this->assertSame(true, $announce->isModded);
        $this->assertSame(4, $announce->lobbyState);
        $this->assertSame("custom_level_6D4021498979AB7C07D430C488C24DE45EEDADB4", $announce->levelId);
        $this->assertSame('"It\'s a me, Mario!" - Super Mario 64', $announce->songName);
        $this->assertSame("GilvaSunner", $announce->songAuthor);
        $this->assertTrue($announce->isModded);
        $this->assertSame(0, $announce->difficulty);
        $this->assertSame("steam", $announce->platform);
        $this->assertSame("custom-server.com", $announce->masterServerHost);
        $this->assertSame(2328, $announce->masterServerPort);
        $this->assertNull($announce->endedAt);
        $this->assertSame('1.2.3', $announce->mpExVersion,
            'MpEx version should be parsed and, if needed, normalized to Major.Minor.Patch');
        $this->assertEquals("ServerBrowser", $announce->modName);
        $this->assertEquals(new CVersion("4.2.0"), $announce->modVersion);
        $this->assertEquals(new CVersion("6.9.42"), $announce->gameVersion);
        $this->assertSame(HostedGame::SERVER_TYPE_NORMAL_DEDICATED, $announce->serverType);
        $this->assertSame("abc1234", $announce->hostSecret);
        $this->assertEquals(new IPEndPoint("127.0.0.1", 2312), $announce->endpoint);
        $this->assertSame("unit_test_testFullAnnounceMgr", $announce->managerId);

        self::$fullAnnounceTestResult = $announce;

        // Test level record updates
        $levelRecord = LevelRecord::query()
            ->where('level_id = ?', $TEST_LEVEL_ID)
            ->querySingleModel();

        $this->assertNotNull($levelRecord, 'Level record should be updated or created');
        $this->assertGreaterThan($prevLevelPlayCount, $levelRecord->statPlayCount,
            'statPlayCount should be incremented on announce, when transitioning to GameRunning lobby state');
    }

    /**
     * @depends testFullAnnounce
     */
    public function testReplaceFullAnnounce()
    {
        // -------------------------------------------------------------------------------------------------------------
        // Create request

        $request = new MockJsonRequest([
            'ServerCode' => 'ABC12',
            'GameName' => 'My Game But Newer',
            'OwnerId' => 'unit_test_testFullAnnounce',
            'HostSecret' => 'abc1234',
            'OwnerName' => 'My Name',
            'PlayerCount' => 2,
            'PlayerLimit' => 5,
            'IsModded' => true,
            'LobbyState' => MultiplayerLobbyState::LobbySetup,
            'Difficulty' => LevelDifficulty::Easy,
            'Platform' => ModPlatformId::STEAM,
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM,
            'MasterServerPort' => 2328,
            'LevelId' => null
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        // -------------------------------------------------------------------------------------------------------------
        // Test updated object

        $response = (new AnnounceController())->announce($request);
        $responseJson = json_decode($response->body, true);

        $this->assertSame(true, $responseJson['success']);
        $this->assertSame(self::$fullAnnounceTestResult->getHashId(), $responseJson['key'],
            "The previously created announce should be replaced/updated, keeping its original key.");

        $updatedResult = HostedGame::fetch(self::$fullAnnounceTestResult->id);

        $this->assertSame("My Game But Newer", $updatedResult->gameName,
            "Game data should update when replacing the announce");
        $this->assertSame(self::$fullAnnounceTestResult->levelId, $updatedResult->levelId,
            "Extra data like level id should not be removed on update, even if NULL in update request");
        $this->assertNull($updatedResult->endedAt);
    }

    public static MockJsonRequest $minimalAnnounceRequest;
    private static ?HostedGame $minimalAnnounceTestResult;

    /**
     * @depends testFullAnnounce
     */
    public function testMinimalAnnounce()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testMinimalAnnounce',
            'HostSecret' => null
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        self::$minimalAnnounceRequest = $request;

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(200, $response->code);

        $responseJson = json_decode($response->body, true);
        $this->assertSame(true, $responseJson['success']);

        $announceKey = $responseJson['key'];
        $announceId = HostedGame::hash2id($announceKey);
        $announce = HostedGame::fetch($announceId);

        self::$minimalAnnounceTestResult = $announce;

        $this->assertSame("Untitled Beat Game", $announce->gameName);
        $this->assertSame("Unknown", $announce->ownerName);
        $this->assertSame(1, $announce->playerCount);
        $this->assertSame(5, $announce->playerLimit);
        $this->assertFalse($announce->isModded);
        $this->assertSame(MultiplayerLobbyState::None, $announce->lobbyState);
        $this->assertNull($announce->levelId);
        $this->assertNull($announce->songName);
        $this->assertNull($announce->songAuthor);
        $this->assertNull($announce->difficulty);
        $this->assertSame("unknown", $announce->platform);
        $this->assertNull($announce->masterServerHost);
        $this->assertNull($announce->masterServerPort);
        $this->assertNull($announce->endedAt);
        $this->assertEmpty($announce->fetchPlayers());
        $this->assertNull($announce->mpExVersion);
        $this->assertNull($announce->hostSecret);
        $this->assertNull($announce->endpoint);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testModernizedV1Announce()
    {
        // -------------------------------------------------------------------------------------------------------------
        // Send mock request

        $request = new MockJsonRequest([
            'ServerCode' => 'MODRN',
            'OwnerId' => 'unit_test_testModernizedV1Announce',
            'HostSecret' => "testModernizedV1Announce",
            'MasterServerEp' => 'server.host.com:1234',
            'Level' => [
                'Difficulty' => 3,
                'Characteristic' => 'Standard',
                'LevelId' => 'Sugar',
                'SongName' => 'Sugar',
                'SongAuthorName' => 'Maroon 5'
            ]
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        // -------------------------------------------------------------------------------------------------------------
        // Test created object

        $response = (new AnnounceController())->announce($request);
        $responseJson = json_decode($response->body, true);

        $this->assertSame(true, $responseJson['success']);

        $game = HostedGame::fetch(HostedGame::hash2id($responseJson['key']));

        $this->assertSame("server.host.com", $game->masterServerHost);
        $this->assertSame(1234, $game->masterServerPort);
        $this->assertSame("Sugar", $game->levelId);
        $this->assertSame("Sugar", $game->songName);
        $this->assertSame("Maroon 5", $game->songAuthor);
        $this->assertSame(3, $game->difficulty);
        $this->assertSame("Standard", $game->characteristic);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testReplaceMinimalAnnounce()
    {
        // -------------------------------------------------------------------------------------------------------------
        // Send mock request

        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testMinimalAnnounce',
            'HostSecret' => null,
            'GameName' => 'setting a name'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        // -------------------------------------------------------------------------------------------------------------
        // Test updated object

        $response = (new AnnounceController())->announce($request);
        $responseJson = json_decode($response->body, true);

        $this->assertSame(true, $responseJson['success']);
        $this->assertSame(self::$minimalAnnounceTestResult->getHashId(), $responseJson['key'],
            "The previously created announce should be replaced/updated, keeping its original id.");

        $updatedResult = HostedGame::fetch(self::$minimalAnnounceTestResult->id);

        $this->assertSame("setting a name", $updatedResult->gameName,
            "new data needs to be applied");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceAutomaticallyInfersPlatform()
    {
        $fnTestRequestPlatform = function (?string $masterServerHost, ?string $platform): ?string
        {
            $request = new MockJsonRequest([
                'ServerCode' => '12345',
                'OwnerId' => 'unit_test_testAnnounceAutomaticallyInfersPlatform',
                'MasterServerHost' => $masterServerHost,
                'Platform' => $platform
            ]);
            $request->method = "POST";
            $request->path = "/api/v1/announce";

            $response = (new AnnounceController())->announce($request);
            $this->assertSame(200, $response->code, "Sanity check: announce should return 200 OK");

            $json = json_decode($response->body, true);
            return HostedGame::fetch(HostedGame::hash2id($json['key']))->platform;
        };

        $this->assertSame("unknown", $fnTestRequestPlatform(null, null),
            "Announce with neither platform nor master server should result in unknown platform");
        $this->assertSame("steam", $fnTestRequestPlatform(null, "steam"),
            "Announce with no master server should simply apply platform value");
        $this->assertSame("oculus", $fnTestRequestPlatform("oculus.production.mp.beatsaber.com", "steam"),
            "Announce with no specific master server should automatically set platform value, regardless of platform in request (oculus)");
        $this->assertSame("steam", $fnTestRequestPlatform("steam.production.mp.beatsaber.com", null),
            "Announce with no specific master server should automatically set platform value, regardless of platform in request (steam)");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testMpExVersionInfersModded()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'IsModded' => false,
            'OwnerId' => 'unit_test_testMpExVersionInfersModded',
            'MpExVersion' => "1.2.3",
            "MasterServerHost" => 'custom-server.com'
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(200, $response->code, "Sanity check: announce should return 200 OK");

        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        $this->assertSame("1.2.3", $game->mpExVersion, "MpExVersion should be read and written");
        $this->assertSame(true, $game->isModded, "MpExVersion should automatically set modded flag");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Test validations / rejections

    /**
     * @depends testMinimalAnnounce
     */
    public function testLobbyLimitVanilla()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'IsModded' => false,
            'OwnerId' => 'unit_test_testLobbyLimitVanilla',
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(200, $response->code, "Sanity check: announce should return 200 OK");

        $json = json_decode($response->body, true);

        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));
        $this->assertSame(5, $game->playerLimit, "Vanilla lobbies should be capped at 5 players");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceRejectsNonModRequests()
    {
        $request = clone self::$minimalAnnounceRequest;
        unset($request->headers["x-bssb"]);
        unset($request->headers["user-agent"]);
        $this->assertFalse($request->getIsValidModClientRequest(),
            "Sanity check: test request should no longer be considered a valid mod client request");

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(400, $response->code);
    }

    public function testRejectsInvalidServerCodes()
    {
        $fnCreateRequest = function (string $serverCode): MockJsonRequest
        {
            $request = new MockJsonRequest([
                'ServerCode' => $serverCode,
                'OwnerId' => 'unit_test_testRejectsInvalidServerCodes'
            ]);
            $request->method = "POST";
            $request->path = "/api/v1/announce";
            return $request;
        };

        $this->assertSame(200, ((new AnnounceController())->announce($fnCreateRequest("12345")))->code,
            "5 digit server code should be accepted");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("1234")))->code,
            "4 digit server code should be rejected");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("123456")))->code,
            "6 digit server code should be rejected");
        $this->assertSame(400, ((new AnnounceController())->announce($fnCreateRequest("áéáóç")))->code,
            "non-alphanumeric server code should be rejected");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testRejectsServerMessageOwnerId()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['OwnerId'] = "SERVER_MESSAGE";

        $this->assertSame(400, ((new AnnounceController())->announce($request))->code,
            "SERVER_MESSAGE as OwnerId should be rejected");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceCleansLevelId()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['LevelId'] = 'custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C_71e5 (Moudoku ga Osou - Tootie)';
        $request->json['SongName'] = 'Moudoku ga Osou';
        $request->json['SongAuthor'] = 'Tootie';
        $request->json['OwnerId'] = 'unit_test_testAnnounceCleansLevelId';
        $request->json['IsModded'] = true;

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);

        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));
        $this->assertSame("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C", $game->levelId);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceHandlesEmptyNames()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['GameName'] = "  ";

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        $this->assertSame("Untitled Beat Game", $game->gameName,
            "Empty game names should be prevented");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testAnnounceDiscardsUninteresting()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['MasterServerHost'] = "localhost";

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        $this->assertNotNull($game->endedAt, "Uninteresting games should be marked as ended immediately");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testRejectsBeatDediGames()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['ServerType'] = HostedGame::SERVER_TYPE_BEATDEDI_CUSTOM;
        $request->json['Secret'] = 'bla';

        $this->assertSame(400, ((new AnnounceController())->announce($request))->code,
            "SERVER_TYPE_BEATDEDI_CUSTOM should be rejected for mod client requests");

        $request->json['ServerType'] = HostedGame::SERVER_TYPE_BEATDEDI_QUICKPLAY;

        $this->assertSame(400, ((new AnnounceController())->announce($request))->code,
            "SERVER_TYPE_BEATDEDI_QUICKPLAY should be rejected for mod client requests");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testRejectsQuickplayGamesWithoutHostSecret()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['ServerType'] = HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY;
        unset($request->json['HostSecret']);

        $this->assertSame(400, ((new AnnounceController())->announce($request))->code,
            "SERVER_TYPE_VANILLA_QUICKPLAY should be rejected if no host secret is set");

        $request->json['HostSecret'] = 'bla1234';

        $this->assertSame(200, ((new AnnounceController())->announce($request))->code,
            "SERVER_TYPE_VANILLA_QUICKPLAY should be succeed if host secret is set");
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testSetsQuickPlayName()
    {
        $request = clone self::$minimalAnnounceRequest;
        $request->json['ServerType'] = HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY;
        $request->json['HostSecret'] = 'bla1234';
        $request->json['Difficulty'] = LevelDifficulty::Hard;
        $request->json['GameName'] = '💩';

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        $this->assertSame("Official Quick Play - Hard", $game->gameName);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Player list sync

    /**
     * @depends testMinimalAnnounce
     */
    public function testPlayerListSync()
    {
        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Part 1: Starting a new game with a single host player

        $request = clone self::$minimalAnnounceRequest;
        $request->json["OwnerId"] = "unit_test_testPlayerListSync";

        $request->json['Players'] = [];
        $request->json['Players'][] = [
            'SortIndex' => -1,
            'UserId' => 'theServerHostWithoutAName',
            'UserName' => '',
            'IsHost' => true,
            'IsAnnouncer' => false,
            'Latency' => 0.1234
        ];
        $request->json['Players'][] = [
            'SortIndex' => 0,
            'UserId' => 'testPlayerListSync_0',
            'UserName' => 'Bob',
            'IsHost' => false,
            'IsAnnouncer' => true,
            'Latency' => 0.1234
        ];

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        $players = $game->fetchPlayers();

        $this->assertIsArray($players, 'fetchPlayers() should return an array');
        $this->assertCount(2, $players,
            'fetchPlayers() should contain 2 players after initial announce');

        $hostPlayer = $players[0];

        $this->assertSame(-1, $hostPlayer->sortIndex);
        $this->assertSame('theServerHostWithoutAName', $hostPlayer->userId);
        $this->assertSame('Dedicated Server', $hostPlayer->userName);
        $this->assertSame(true, $hostPlayer->isHost);
        $this->assertSame(false, $hostPlayer->isAnnouncer);
        $this->assertSame(0.1234, $hostPlayer->latency);

        $firstPlayer = $players[1];

        $this->assertSame(0, $firstPlayer->sortIndex);
        $this->assertSame('testPlayerListSync_0', $firstPlayer->userId);
        $this->assertSame('Bob', $firstPlayer->userName);
        $this->assertSame(false, $firstPlayer->isHost);
        $this->assertSame(true, $firstPlayer->isAnnouncer);
        $this->assertSame(0.1234, $firstPlayer->latency);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Part 2: Adding in new players in the next announce

        $request->json['Players'][] = [
            'SortIndex' => 0,
            'UserId' => 'testPlayerListSync_0',
            'UserName' => 'B0b with a zero',
            'IsHost' => false,
            'Latency' => 0.1234
        ];
        $request->json['Players'][] = [
            'SortIndex' => 1,
            'UserId' => 'testPlayerListSync_1',
            'UserName' => 'Bobby',
            'IsHost' => false,
            'Latency' => 0.1234
        ];
        $request->json['Players'][] = [
            'SortIndex' => 2,
            'UserId' => 'testPlayerListSync_2',
            'UserName' => 'Bobster',
            'IsHost' => false,
            'Latency' => 0.1234
        ];
        $request->json['Players'][] = [
            'SortIndex' => 3,
            'UserId' => 'testPlayerListSync_3',
            'UserName' => 'Bob-bee',
            'IsHost' => false,
            'Latency' => 0.1234
        ];
        $request->json['Players'][] = [
            'SortIndex' => 4,
            'UserId' => 'testPlayerListSync_4',
            'UserName' => 'Booba',
            'IsHost' => false,
            'Latency' => 0.1234
        ];
        (new AnnounceController())->announce($request);

        $players = $game->fetchPlayers();

        $this->assertCount(6, $players,
            'fetchPlayers() should contain 6 players after second announce');
        $this->assertSame('Dedicated Server', $players[0]->userName);
        $this->assertSame('B0b with a zero', $players[1]->userName);
        $this->assertSame('Bobby', $players[2]->userName);
        $this->assertSame('Bobster', $players[3]->userName);
        $this->assertSame('Bob-bee', $players[4]->userName);
        $this->assertSame('Booba', $players[5]->userName);

        // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        // Part 3: Updating and removing players

        $request->json['Players'] = [
            [
                'SortIndex' => 0,
                'UserId' => 'testPlayerListSync_0',
                'UserName' => 'Bob',
                'IsHost' => true,
                'Latency' => 0.1234
            ],
            [
                'SortIndex' => 1,
                'UserId' => 'testPlayerListSync_5',
                'UserName' => 'Sally',
                'IsHost' => false,
                'Latency' => 1234.5678
            ]
        ];

        (new AnnounceController())->announce($request);

        $players = $game->fetchPlayers();

        $connectedCount = 0;
        $disconnectedCount = 0;
        $connectedNames = [];

        foreach ($players as $player) {
            if ($player->isConnected) {
                $connectedCount++;
                $connectedNames[] = $player->userName;
            } else {
                $disconnectedCount++;
            }
        }

        $this->assertSame(2, $connectedCount,
            'fetchPlayers() should contain two connected players after third announce');
        $this->assertSame(4, $disconnectedCount,
            'fetchPlayers() should contain three disconnected players after third announce');
        $this->assertSame(['Bob', 'Sally'], $connectedNames);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testMasterServerBlacklist()
    {
        global $bssbConfig;
        $bssbConfig['master_server_blacklist'] = ["sekr.it"];

        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testMinimalAnnounce',
            'HostSecret' => null,
            'MasterServerHost' => "sekr.it"
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";

        $response = (new AnnounceController())->announce($request);
        $this->assertSame(403, $response->code);
    }

    /**
     * @depends testMinimalAnnounce
     */
    public function testOldOfficialPlayerHostCanBeModded()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testOldOfficialPlayerHostCanBeModded',
            'HostSecret' => null,
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM,
            'MpExVersion' => '1.2.3.4',
            'IsModded' => true,
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.16.2) (steam)";

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        // sanity checks
        $this->assertTrue($game->getIsOfficial());
        $this->assertFalse($game->gameVersion->greaterThanOrEquals(new CVersion("1.16.3")));
        $this->assertTrue($game->serverType == null || $game->serverType === HostedGame::SERVER_TYPE_PLAYER_HOST);

        $this->assertTrue($game->isModded, "An MpEx-modded P2P game (pre 1.16.3) CAN be modded");
    }

    /**
     * @depends testOldOfficialPlayerHostCanBeModded
     */
    public function testOfficialCantBeModded()
    {
        $request = new MockJsonRequest([
            'ServerCode' => '12345',
            'OwnerId' => 'unit_test_testOfficialCantBeModded',
            'HostSecret' => null,
            'MasterServerHost' => MasterServer::OFFICIAL_HOSTNAME_STEAM,
            'MpExVersion' => '1.2.3.4',
            'IsModded' => true
        ]);
        $request->method = "POST";
        $request->path = "/api/v1/announce";
        $request->headers["user-agent"] = "ServerBrowser/1.0.0 (BeatSaber/1.16.3) (steam)";

        $response = (new AnnounceController())->announce($request);
        $json = json_decode($response->body, true);
        $game = HostedGame::fetch(HostedGame::hash2id($json['key']));

        // sanity checks
        $this->assertTrue($game->getIsOfficial());
        $this->assertTrue($game->gameVersion->greaterThanOrEquals(new CVersion("1.16.3")));

        $this->assertFalse($game->isModded, "An MpEx-modded P2P game (pre 1.16.3) CANNOT be modded");
    }
}
