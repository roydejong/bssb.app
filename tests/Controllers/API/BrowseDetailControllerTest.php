<?php

use app\Controllers\API\V1\BrowseDetailController;
use app\HTTP\Request;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockModClientRequest;

class BrowseDetailControllerTest extends TestCase
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

    public function testBrowseDetail_400()
    {
        $request = new Request();

        $response = (new BrowseDetailController())->browseDetail($request, "");

        $this->assertSame(400, $response->code);
    }

    public function testBrowseDetail_404()
    {
        $request = new MockModClientRequest();
        $request->path = "/api/v1/browse/invalid_key";

        $response = (new BrowseDetailController())->browseDetail($request, "invalid_key");

        $this->assertSame(404, $response->code);
    }

    public function testBrowseDetail_200()
    {
        $sampleGame = new HostedGame();
        $sampleGame->serverCode = "55555";
        $sampleGame->ownerId = "unit_test_testBrowseDetail_200";
        $sampleGame->hostSecret = "unit_test_testBrowseDetail_200";
        $sampleGame->firstSeen = new DateTime('now');
        $sampleGame->lastUpdate = $sampleGame->firstSeen;
        $sampleGame->save();

        $hashId = $sampleGame->getHashId();

        try {
            $request = new MockModClientRequest();
            $request->path = "/api/v1/browse/{$hashId}";

            $response = (new BrowseDetailController())->browseDetail($request, $hashId);

            $this->assertSame(200, $response->code);

            $responseJson = @json_decode($response->body, true);

            $this->assertSame($responseJson['key'], $hashId);
            $this->assertSame($responseJson['hostSecret'], 'unit_test_testBrowseDetail_200');
            $this->assertSame($responseJson['serverCode'], '55555');
            $this->assertIsArray($responseJson['players']);
        } finally {
            @$sampleGame->delete();
        }
    }
}
