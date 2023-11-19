<?php

use app\Controllers\HomeController;
use app\Controllers\MasterServersController;
use app\Controllers\StatsController;
use app\HTTP\Request;
use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    define('FORCE_CACHE_GEN', true);

    // Homepage
    $homeController = new HomeController();
    $homeController->index(new Request());

    // Stats
    $statsController = new StatsController();
    $statsController->getStats(new Request());
    $statsController->getTopLevelsSubPage(new Request(), "custom-levels");
    $statsController->getTopLevelsSubPage(new Request(), "official-levels");

    // Master Server list
    $masterServersController = new MasterServersController();
    $masterServersController->getServerList(new Request());
});

$task
    ->description('Generate primary static pages (home, stats).')
    ->everyMinute();

return $schedule;