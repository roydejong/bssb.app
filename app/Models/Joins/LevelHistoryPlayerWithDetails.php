<?php

namespace app\Models\Joins;

use app\BeatSaber\Enums\PlayerLevelEndState;
use app\BeatSaber\LevelDifficulty;
use app\Models\HostedGame;
use app\Models\LevelHistoryPlayer;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

class LevelHistoryPlayerWithDetails extends LevelHistoryPlayer implements IReadOnlyModel
{
    // level_histories
    public string $sessionGameId;
    public int $hostedGameId;
    public int $levelRecordId;
    public int $difficulty;
    public ?string $characteristic;
    public \DateTime $startedAt;
    public ?\DateTime $endedAt;
    public ?int $playedPlayerCount;
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

    // hosted_games
    public string $gameName;
    public \DateTime $firstSeen;

    /**
     * @return LevelHistoryPlayerWithDetails[]
     */
    public static function queryPlayerHistory(int $playerId, int $pageIndex = 0, int $pageSize = 16): array
    {
        return LevelHistoryPlayerWithDetails::query()
            ->select('lh.*, lhp.*, lr.*, hg.game_name, hg.first_seen, lhp.id AS id')
            ->from('level_history_players lhp')
            ->where('lhp.player_id = ?', $playerId)
            ->andWhere('lhp.end_state != ?', PlayerLevelEndState::NotStarted->value)
            ->innerJoin('level_histories lh ON (lh.id = lhp.level_history_id)')
            ->innerJoin('level_records lr ON (lr.id = lh.level_record_id)')
            ->innerJoin('hosted_games hg ON (hg.id = lh.hosted_game_id)')
            ->orderBy('lh.ended_at DESC')
            ->offset($pageIndex * $pageSize)
            ->limit($pageSize)
            ->queryAllModels();
    }

    public function getServerUrl(): string
    {
        return "/game/" . HostedGame::id2hash($this->hostedGameId);
    }

    public function describeDifficulty(): string
    {
        return LevelDifficulty::describe($this->difficulty);
    }
}