<?php

use app\Models\Player;
use app\Models\ProfileStats;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    $pendingStats = ProfileStats::queryAllPending();

    foreach ($pendingStats as $profileStats) {
        if ($player = Player::fetch($profileStats->playerId)) {
            $profileStats->recalculate($player);
        }
    }
});

$task
    ->description('Updates player profile stats for which an update was requested.')
    ->everyMinute();

return $schedule;