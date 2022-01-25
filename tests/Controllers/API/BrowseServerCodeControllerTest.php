<?php

use app\Controllers\API\V1\BrowseServerCodeController;
use app\HTTP\Request;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockModClientRequest;

class BrowseServerCodeControllerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%"')
            ->execute();
    }

    public static function tearDownAfterClass(): void
    {
        HostedGame::query()
            ->delete()
            ->where('owner_id LIKE "unit_test_%"')
            ->execute();
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function testBrowseServerCode_400()
    {
        $request = new Request();
        $response = (new BrowseServerCodeController())->browseServerCode($request, "");
        $this->assertSame(400, $response->code);

        $request = new Request();
        $response = (new BrowseServerCodeController())->browseServerCode($request, "invalid");
        $this->assertSame(400, $response->code);

        $request = new Request();
        $response = (new BrowseServerCodeController())->browseServerCode($request, "KÍNDÄ");
        $this->assertSame(400, $response->code);
    }

    public function testBrowseServerCode_200_NoResults()
    {
        $request = new Request();
        $response = (new BrowseServerCodeController())->browseServerCode($request, "XXXXX");
        $this->assertSame(200, $response->code);

        $responseData = json_decode($response->body, true);

        $this->assertSame("XXXXX", $responseData['serverCode']);
        $this->assertEmpty($responseData['results']);
    }

    /**
     * @depends testBrowseServerCode_200_NoResults
     */
    public function testBrowseServerCode_200()
    {
        $sampleGameA = new HostedGame();
        $sampleGameA->serverCode = "XXXXX";
        $sampleGameA->ownerId = "unit_test_servercode_a";
        $sampleGameA->hostSecret = "unit_test_servercode_a";
        $sampleGameA->masterServerHost = "master.beattogether.systems";
        $sampleGameA->firstSeen = new DateTime('now');
        $sampleGameA->lastUpdate = $sampleGameA->firstSeen;
        $sampleGameA->save();

        $sampleGameB = new HostedGame();
        $sampleGameB->serverCode = "XXXXX";
        $sampleGameB->ownerId = "unit_test_servercode_b";
        $sampleGameB->hostSecret = "unit_test_servercode_b";
        $sampleGameB->masterServerHost = "steam.production.mp.beatsaber.com";
        $sampleGameB->firstSeen = new DateTime('now');
        $sampleGameB->lastUpdate = $sampleGameB->firstSeen;
        $sampleGameB->save();

        try {
            $request = new MockModClientRequest();
            $request->path = "/api/v1/browse/code/XXXXX";

            $response = (new BrowseServerCodeController())->browseServerCode($request, "XXXXX");

            $this->assertSame(200, $response->code);

            $responseJson = @json_decode($response->body, true);

            $this->assertSame($responseJson['serverCode'], "XXXXX");
            $this->assertIsArray($responseJson['results']);
            $this->assertCount(2, $responseJson['results']);

            $foundBtGame = false;
            $foundSteamGame = false;

            foreach ($responseJson['results'] as $result) {
                $this->assertArrayHasKey("serverCode", $result);
                $this->assertArrayHasKey("masterServerHost", $result);
                $this->assertArrayHasKey("masterServerPort", $result);

                if ($result['masterServerHost'] === "master.beattogether.systems")
                    $foundBtGame = true;
                else if ($result['masterServerHost'] === "steam.production.mp.beatsaber.com")
                    $foundSteamGame = true;
            }

            $this->assertTrue($foundBtGame);
            $this->assertTrue($foundSteamGame);
        } finally {
            @$sampleGameA->delete();
        }
    }
}
