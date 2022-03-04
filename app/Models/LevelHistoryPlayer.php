<?php

namespace app\Models;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use SoftwarePunt\Instarecord\Model;

class LevelHistoryPlayer extends Model
{
    public int $id;
    public int $levelHistoryId;
    public int $playerId;
    public ?PlayerLevelEndReason $endReason;
    public ?PlayerLevelEndState $endState;
    public ?int $rawScore;
    public ?int $modifiedScore;
    public ?int $rank;
    public ?int $goodCuts;
    public ?int $badCuts;
    public ?int $missCount;
    public ?bool $fullCombo;
    public ?int $maxCombo;
    public ?string $badgeKey;
    public ?string $badgeTitle;
    public ?string $badgeSubtitle;
}