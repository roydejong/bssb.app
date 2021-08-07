<?php

use app\Controllers\GameDetailController;
use app\Models\HostedGame;
use PHPUnit\Framework\TestCase;
use tests\Mock\MockModClientRequest;

class GameDetailControllerTest extends TestCase
{
    public function testGetGameDetail_400()
    {
        $controller = new GameDetailController();
        $response = $controller->getGameDetail(new MockModClientRequest(), "invalid_hash");

        $this->assertSame(400, $response->code);
    }

    public function testGetGameDetail_404()
    {
        $controller = new GameDetailController();
        $response = $controller->getGameDetail(new MockModClientRequest(), HostedGame::id2hash(PHP_INT_MAX));

        $this->assertSame(404, $response->code);
    }

    public function testGetGameDetail_200()
    {
        $hostedGame = new HostedGame();
        $hostedGame->gameName = "Sample Game";
        $hostedGame->serverCode = "55555";
        $hostedGame->ownerId = "abc123";
        $hostedGame->ownerName = "Some Owner";
        $hostedGame->firstSeen = new DateTime('now');
        $hostedGame->lastUpdate = $hostedGame->firstSeen;
        $hostedGame->save();

        try {
            $controller = new GameDetailController();
            $response = $controller->getGameDetail(new MockModClientRequest(), HostedGame::id2hash($hostedGame->id));

            $this->assertSame(200, $response->code);
        } finally {
            $hostedGame->delete();
        }
    }
}
