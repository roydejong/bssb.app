<?php

use app\Models\Player;
use app\Models\ProfileStats;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    $startTime = microtime(true);
    $pendingStats = ProfileStats::queryAllPending();

    foreach ($pendingStats as $profileStats) {
        if ($player = Player::fetch($profileStats->playerId)) {
            try {
                echo "Recalculating stats for player {$player->id}...\n";
                $profileStats->recalculate($player);
            } catch (Exception $ex) {
                echo " >>> Error recalculating stats for player {$player->id}: {$ex->getMessage()}\n";
            }
        }
        $runTime = microtime(true) - $startTime;
        if ($runTime >= 59) {
            // Don't run for more than a minute
            break;
        }
    }
});

$task
    ->description('Updates player profile stats for which an update was requested.')
    ->everyMinute();

return $schedule;