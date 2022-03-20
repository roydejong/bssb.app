<?php

namespace app\Models\Traits;

use app\BeatSaber\Enums\BeatmapCharacteristic;
use app\BeatSaber\GameplayModifiers;
use app\BeatSaber\LevelDifficulty;
use app\Models\HostedGame;
use app\Models\LevelRecord;

trait HasLevelHistoryData
{
    /**
     * The server-assigned GUID for the specific level play.
     */
    public string $sessionGameId;
    public int $hostedGameId;
    public int $levelRecordId;
    /**
     * Difficulty for the level that was played.
     */
    public int $difficulty;
    /**
     * The raw name of the beatmap characteristic that was played.
     */
    public ?string $characteristic;
    /**
     * Gameplay modifiers data for this level play.
     */
    public ?GameplayModifiers $modifiers;
    /**
     * When the start of the level was reported (regular announce)
     *
     * @see AnnounceProcessor::syncLevelData()
     */
    public \DateTime $startedAt;
    /**
     * When the end of the level was reported (results announce)
     * May be NULL if the level did not yet, or ended improperly
     *
     * @see AnnounceResultsController::announceResults()
     */
    public ?\DateTime $endedAt;
    /**
     * The calculated amount of players that started the level and were included in results
     * This is only set once level has ended
     *
     * @see AnnounceResultsController::announceResults()
     */
    public ?int $playedPlayerCount;
    /**
     * The calculated amount of players that cleared the level and submitted valid results
     * This is only set once level has ended
     *
     * @see AnnounceResultsController::announceResults()
     */
    public ?int $finishedPlayerCount;

    // -----------------------------------------------------------------------------------------------------------------
    // Difficulty

    public function describeDifficulty(): string
    {
        return LevelDifficulty::describe($this->difficulty);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Characteristic

    public function tryParseCharacteristic(): ?BeatmapCharacteristic
    {
        if (!$this->characteristic)
            return null;

        return BeatmapCharacteristic::tryFrom($this->characteristic);
    }

    public function describeCharacteristic(): string
    {
        if (!$this->characteristic)
            return "Unknown";

        return self::tryParseCharacteristic()?->describe() ?? $this->characteristic;
    }

    public function getCharacteristicIcon(): ?string
    {
        if (!$this->characteristic)
            return null;

        return self::tryParseCharacteristic()?->getIconUrl();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Modifiers

    public function getModifierTags(): array
    {
        $results = [];

        if ($this->modifiers) {
            foreach ($this->modifiers->getModifiers() as $modifier) {
                $results[] = [
                    'key' => $modifier->name,
                    'text' => $modifier->describe(),
                    'icon' => $modifier->getIconUrl()
                ];
            }
        }

        return $results;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Relationships

    public function getDetailUrl(): string
    {
        return "/results/" . $this->sessionGameId;
    }

    public function fetchHostedGame(): ?HostedGame
    {
        return HostedGame::fetch($this->hostedGameId);
    }

    public function getServerUrl(): string
    {
        return "/game/" . HostedGame::id2hash($this->hostedGameId);
    }

    public function fetchLevelRecord(): ?LevelRecord
    {
        return LevelRecord::fetch($this->levelRecordId);
    }
}