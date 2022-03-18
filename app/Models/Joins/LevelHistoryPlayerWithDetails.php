<?php

namespace app\Models\Joins;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\BeatSaber\LevelDifficulty;
use app\Models\HostedGame;
use app\Models\LevelHistoryPlayer;
use app\Models\Traits\HasBeatmapCharacteristic;
use app\Models\Traits\HasLevelHistoryData;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

class LevelHistoryPlayerWithDetails extends LevelHistoryPlayer implements IReadOnlyModel
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
            ->andWhere('lhp.end_reason NOT IN (?)', [PlayerLevelEndReason::ConnectedAfterLevelEnded->value,
                PlayerLevelEndReason::StartupFailed->value, PlayerLevelEndReason::WasInactive->value])
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

    public function describeFailReason(): string
    {
        if ($this->endReason) {
            return match ($this->endReason) {
                PlayerLevelEndReason::ConnectedAfterLevelEnded => "Connected after level ended",
                PlayerLevelEndReason::GivenUp => "Gave up",
                PlayerLevelEndReason::HostEndedLevel => "Host ended level",
                PlayerLevelEndReason::Quit => "Player quit",
                PlayerLevelEndReason::StartupFailed => "Startup failed",
                PlayerLevelEndReason::WasInactive => "Player was inactive",
                PlayerLevelEndReason::Failed => "Level failed",
                PlayerLevelEndReason::Cleared => "Level cleared"
            };
        }

        return match ($this->endState) {
            PlayerLevelEndState::SongFinished => "Song finished",
            PlayerLevelEndState::NotStarted => "Not started",
            PlayerLevelEndState::NotFinished => "Not finished",
            default => "Did not finish"
        };
    }
}