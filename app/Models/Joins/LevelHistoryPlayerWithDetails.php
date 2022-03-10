<?php

namespace app\Models\Joins;

use app\Models\LevelHistoryPlayer;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

class LevelHistoryPlayerWithDetails extends LevelHistoryPlayer implements IReadOnlyModel
{
    // level_histories
    public int $hostedGameId;
    public int $levelRecordId;
    public string $sessionGameId;
    public \DateTime $startedAt;
    public ?\DateTime $endedAt;
    public ?int $finishedPlayerCount;

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

    /**
     * @return LevelHistoryPlayerWithDetails[]
     */
    public static function queryPlayerHistory(int $playerId, int $pageIndex = 0, int $pageSize = 16): array
    {
        return LevelHistoryPlayerWithDetails::query()
            ->select('lh.*, lhp.*, lr.*, lhp.id AS id')
            ->from('level_history_players lhp')
            ->where('lhp.player_id = ?', $playerId)
            ->innerJoin('level_histories lh ON (lh.id = lhp.level_history_id)')
            ->innerJoin('level_records lr ON (lr.id = lh.level_record_id)')
            ->orderBy('lh.ended_at DESC')
            ->offset($pageIndex * $pageSize)
            ->limit($pageSize)
            ->queryAllModels();
    }
}