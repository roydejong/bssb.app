<?php

use app\Models\MasterServerInfo;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    $lastSeenCutoff = new DateTime('now');
    $lastSeenCutoff->modify('- 1 month');

    $masterServers = MasterServerInfo::query()
        ->where('last_seen >= ?', $lastSeenCutoff)
        ->orderBy('last_seen DESC, id DESC')
        ->queryAllModels();

    foreach ($masterServers as $masterServer) {
        /**
         * @var $masterServer MasterServerInfo
         */
        $masterServer->refreshStatus();
    }
});

$task
    ->description('Refresh master server statuses (IP resolve, GeoIP, status URL fetch).')
    ->everyMinute();

return $schedule;