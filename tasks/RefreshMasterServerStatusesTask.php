<?php

use app\Models\MasterServerInfo;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    foreach (MasterServerInfo::all() as $masterServer) {
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