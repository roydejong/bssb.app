<?php

namespace app\Models\Joins;

use app\Models\LevelHistory;
use app\Models\Traits\HasLevelHistoryData;

class LevelHistoryWithDetails extends LevelHistory
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

    // hosted_games
    public string $gameName;
    public \DateTime $firstSeen;

    public static function queryHostedGameHistory(int $hostedGameId, int $pageIndex = 0, int $pageSize = 16): array
    {
        return LevelHistoryPlayerWithDetails::query()
            ->select('lh.*, lr.*, hg.game_name, hg.first_seen, lh.id AS id')
            ->from('level_histories lh')
            ->innerJoin('level_records lr ON (lr.id = lh.level_record_id)')
            ->innerJoin('hosted_games hg ON (hg.id = lh.hosted_game_id)')
            ->where('lh.hosted_game_id = ?', $hostedGameId)
            ->orderBy('lh.ended_at DESC')
            ->offset($pageIndex * $pageSize)
            ->limit($pageSize)
            ->queryAllModels();
    }
}