<?php

use app\Models\Enums\PlayerType;
use app\Models\LevelHistoryPlayer;
use app\Models\Player;
use PHPUnit\Framework\TestCase;

class LevelHistoryPlayerTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        Player::query()
            ->where('user_id = ?', "LevelHistoryPlayerTest")
            ->delete()
            ->execute();
    }

    private function internalAssertCheatDetected(int $finalScore, int $goodCuts, int $badCuts, int $missCount, int $maxCombo): bool
    {
        $testPlayerUserId = "LevelHistoryPlayerTest";

        Player::query()
            ->insertIgnore()
            ->values([
                'user_id' => $testPlayerUserId,
                'user_name' => $testPlayerUserId,
                'type' => PlayerType::PlayerModUser->value,
                'first_seen' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
                'last_seen' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
            ])
            ->execute();

        $player = Player::query()
            ->where('user_id = ?', $testPlayerUserId)
            ->querySingleModel();

        $lhp = new LevelHistoryPlayer();
        $lhp->playerId = $player->id;
        $lhp->multipliedScore = $finalScore;
        $lhp->modifiedScore = $finalScore;
        $lhp->goodCuts = $goodCuts;
        $lhp->badCuts = $badCuts;
        $lhp->missCount = $missCount;
        $lhp->maxCombo = $maxCombo;
        $lhp->fullCombo = $maxCombo > 0 && $badCuts === 0 && $missCount === 0;

        return $lhp->sniffTestCheating();
    }

    private function assertCheatDetected(int $finalScore, int $goodCuts, int $badCuts, int $missCount, int $maxCombo)
    {
        $this->assertTrue($this->internalAssertCheatDetected($finalScore, $goodCuts, $badCuts, $missCount, $maxCombo),
            "Score was not detected as a cheat");
    }

    private function assertCheatNotDetected(int $finalScore, int $goodCuts, int $badCuts, int $missCount, int $maxCombo)
    {
        $this->assertFalse($this->internalAssertCheatDetected($finalScore, $goodCuts, $badCuts, $missCount, $maxCombo),
            "Score was detected as a cheat");
    }

    public function testSniffTestCheating()
    {
        // https://bssb.app/results/610b733c-921a-4f75-b042-c977a6478dd0 [final score cheat]
        $this->assertCheatDetected(173758256, 837, 13, 95, 136);
        // https://bssb.app/results/a5df9330-7558-4aee-9d86-140b2df79f2d [final score cheat]
        $this->assertCheatDetected(468531008, 469, 0, 0, 36);
        // https://bssb.app/results/0594df93-2df7-4e32-8a40-bc505dc1e2ca [goofy final score cheat]
        $this->assertCheatDetected(1034963968, 1036, 0, 0, 12);

        // https://bssb.app/results/050888d6-a4db-4710-9e20-20782e358756 [dumb level that I guess is not cheating]
        $this->assertCheatNotDetected(71031400, 80080, 0, 0, 80080);
        // https://bssb.app/results/08c1e08a-2bc4-4462-9ea5-a906fcfb2db6 [Shrek 2 entire movie]
        $this->assertCheatNotDetected(9863606, 14389, 156, 71, 1050);
        // https://bssb.app/results/82aeae72-da53-450a-a6c8-7dbdcd07e01b [DADADADA]
        $this->assertCheatNotDetected(22415368, 30604, 0, 2, 30604);

    }
}