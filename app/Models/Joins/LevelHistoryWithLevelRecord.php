<?php

namespace app\Models\Joins;

use app\Models\LevelHistory;
use app\Models\Traits\HasLevelHistoryData;

class LevelHistoryWithLevelRecord extends LevelHistory
{
    // level_histories
    use HasLevelHistoryData;

    // level_records
    public string $levelId;
    public ?string $hash;
    public ?string $beatsaverId;
    public ?string $coverUrl;
    public string $name;
    public string $songName;
    public ?string $songSubName;
    public ?string $songAuthor;
    public ?string $levelAuthor;
    public ?int $duration;
    public ?string $description;
    public int $statPlayCount;

    // -----------------------------------------------------------------------------------------------------------------
    // Querying

    public static function queryHistoryForGame(int $hostedGameId, int $historyCount = 5)
    {
        return LevelHistoryWithLevelRecord::query()
            ->select('lh.*, lr.*, lh.id as id')
            ->from('level_histories lh')
            ->where('hosted_game_id = ?', $hostedGameId)
            ->innerJoin('level_records lr ON (lr.id = level_record_id)')
            ->orderBy('started_at DESC, lh.id DESC')
            ->limit($historyCount)
            ->queryAllModels();
    }
}