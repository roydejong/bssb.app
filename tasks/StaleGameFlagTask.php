<?php

use app\Models\HostedGame;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    /**
     * @var $nonStaleGames HostedGame[]
     */
    $nonStaleGames = HostedGame::query()
        ->where('is_stale = 0')
        ->orderBy('id ASC')
        ->queryAllModels();

    foreach ($nonStaleGames as $staleGame) {
        if ($staleGame->getIsStale()) {
            $staleGame->isStale = true;
            $staleGame->save();
        }
    }
});

$task
    ->description('Marks hosted games that haven\'t received updates in a while as stale.')
    ->everyMinute();

return $schedule;