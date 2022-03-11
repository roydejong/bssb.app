<?php

namespace app\Models;

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\BeatSaber\Enums\PlayerScoreRank;
use SoftwarePunt\Instarecord\Model;

class LevelHistoryPlayer extends Model
{
    /**
     * Record ID
     */
    public int $id;
    /**
     * ID of the LevelHistory record this is a part of
     * Refers to a distinct gameplay session
     */
    public int $levelHistoryId;
    /**
     * ID of the Player profile record these results are for
     */
    public int $playerId;
    /**
     * Indicates how the player ended the level
     * Will be set to NULL if the player is still playing, or if the lobby was aborted before level completion
     */
    public ?PlayerLevelEndReason $endReason;
    /**
     * Indicates what the player's gameplay state was when gameplay ended
     * Will be set to NULL if the player is still playing, or if the lobby was aborted before level completion
     */
    public ?PlayerLevelEndState $endState;
    /**
     * Level completion results: final score, without factoring in modifiers
     * Also known as "multiplied score" (as of 1.20)
     */
    public ?int $rawScore;
    /**
     * Level completion results: final score, factoring in modifiers
     */
    public ?int $modifiedScore;
    /**
     * Level completion results: the player's rank for the score they achieved, relative to the maximum possible score
     */
    public ?PlayerScoreRank $scoreRank;
    /**
     * Level completion results: amount of good block cuts
     */
    public ?int $goodCuts;
    /**
     * Level completion results: amount of bad block cuts
     */
    public ?int $badCuts;
    /**
     * Level completion results: amount of missed blocks
     */
    public ?int $missCount;
    /**
     * Level completion results: did player get full combo?
     */
    public ?bool $fullCombo;
    /**
     * Level completion results: highest combo achieved in the level
     */
    public ?int $maxCombo;
    /**
     * If the player received a results screen badge:
     * The raw localisation key for the badge, which can be used to identify which badge was awarded
     */
    public ?string $badgeKey;
    /**
     * If the player received a results screen badge:
     * The (localised) title for the badge as it was seen by the announcer
     */
    public ?string $badgeTitle;
    /**
     * If the player received a results screen badge:
     * The (localised) subtitle/detail text for the badge as it was seen by the announcer
     */
    public ?string $badgeSubtitle;
    /**
     * The calculated placement rank of the player (i.e. 1 = 1st place), relative to the match
     */
    public ?int $placement;

    // -----------------------------------------------------------------------------------------------------------------

    public function getHasFinished()
    {
        return $this->endState && $this->endState == PlayerLevelEndState::SongFinished;
    }
}