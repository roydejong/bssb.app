<?php

use app\Models\LevelHistory;
use app\Models\LevelRecord;
use Crunz\Schedule;
use SoftwarePunt\Instarecord\Instarecord;

$schedule = new Schedule();

$task = $schedule->run(function () {
    require_once __DIR__ . "/../bootstrap.php";

    // Process each LevelRecord individually, in batched queries
    $batchSize = 1000;
    $reachedEnd = false;

    $maxDailyPlays = intval(LevelRecord::query()
        ->select("MAX(stat_play_count_day) AS max24h")
        ->orderBy('max24h DESC')
        ->querySingleValue());
    if ($maxDailyPlays < 1)
        $maxDailyPlays = 1;

    $maxWeeklyPlays = intval(LevelRecord::query()
        ->select("MAX(stat_play_count_week) AS max7d")
        ->orderBy('max7d DESC')
        ->querySingleValue());
    if ($maxWeeklyPlays < 1)
        $maxWeeklyPlays = 1;

    for ($batchIndex = 0; !$reachedEnd; $batchIndex += $batchSize) {
        /**
         * @var $levelRecords LevelRecord[]
         */
        $levelRecords = LevelRecord::query()
            ->offset($batchIndex)
            ->limit($batchSize)
            ->orderBy('id DESC')
            ->queryAllModels();

        if (count($levelRecords) < $batchSize)
            $reachedEnd = true;

        Instarecord::connection()->beginTransaction();

        foreach ($levelRecords as $levelRecord) {
            /**
             * For trend:
             *  - We only count actual level finishes with multiple players.
             *  - The biggest factor is how popular this level is right now (past 24h).
             *     - We want to reflect sudden surges, i.e. with MpEx's April 1st gag (never gonna give you up).
             *  - A secondary factor (50% weight) is how popular the level was in the past week.
             *  - An additional boost (50% weight) is applied for maps doing better than their daily average
             *      - Some older "challenge" maps get *a lot* of plays/repeats - they're really uninteresting.
             *
             * Remaining challenges:
             *  - We are skipping OST tracks because they get significantly more plays.
             *      - I'd actually like to show DLC because they are underrepresented, and newly released DLCs are
             *          interesting to highlight for trends.
             */

            $baseCountQuery = LevelHistory::query()
                ->select('COUNT(*)')
                ->andWhere('level_record_id = ?', $levelRecord->id)
                ->andWhere('played_player_count > 1')
                ->limit(1);

            $levelRecord->statPlayCountAlt = intval((clone $baseCountQuery)
                ->querySingleValue());
            if ($levelRecord->statPlayCountAlt <= 0)
                $levelRecord->statPlayCountAlt = 1;

            $sevenDaysAgo = new DateTime();
            $sevenDaysAgo->modify('-7 days');
            $levelRecord->statPlayCountWeek = intval((clone $baseCountQuery)
                ->andWhere('started_at >= ?', $sevenDaysAgo)
                ->querySingleValue());

            $twentyFourHoursAgo = new DateTime();
            $twentyFourHoursAgo->modify('-24 hours');
            $levelRecord->statPlayCountDay = intval((clone $baseCountQuery)
                ->andWhere('started_at >= ?', $twentyFourHoursAgo)
                ->querySingleValue());

            $ageBoost = 0.0;
            $firstPlayedValue = (clone $baseCountQuery)
                ->select('MIN(started_at)')
                ->querySingleValue();
            if ($firstPlayedValue) {
                $firstPlayedDt = new DateTime($firstPlayedValue);
                $daysSinceFirstPlay = $firstPlayedDt->diff(new DateTime('now'))->days;
                if ($daysSinceFirstPlay > 1) {
                    $dailyAvg = $levelRecord->statPlayCountAlt / $daysSinceFirstPlay;
                    $ageBoost = ($levelRecord->statPlayCountDay / $dailyAvg) * 0.5;
                    if ($ageBoost < 0) $ageBoost = 0;
                    if ($ageBoost > .5) $ageBoost = .5;
                }
            }

            $levelRecord->trendFactor =
                // how popular is this level today compared to all others? +(0.0 - 1.0)
                ($levelRecord->statPlayCountDay / $maxDailyPlays)
                // how popular is this level this week compared to all others? +(0.0 - 0.5)
                + (($levelRecord->statPlayCountWeek / $maxWeeklyPlays) * 0.5)
                // how popular is this level today compared to its daily average? +(0.0 - 0.5)
                + $ageBoost;

            $levelRecord->save();
        }

        Instarecord::connection()->commitTransaction();
    }
});

$task
    ->description('Calculates and updates per-level stats and trend factor.')
    ->everyTenMinutes();

return $schedule;