<?php

namespace app\Models\Joins;

use app\BeatSaber\Enums\PlayerLevelEndState;
use app\Models\LevelHistoryPlayer;
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

    public function describeFailReason(): string
    {
        if ($this->endReason) {
            return $this->endReason->describe();
        }

        return match ($this->endState) {
            PlayerLevelEndState::SongFinished => "Song finished",
            PlayerLevelEndState::NotStarted => "Not started",
            PlayerLevelEndState::NotFinished => "Not finished",
            default => "Did not finish"
        };
    }
}