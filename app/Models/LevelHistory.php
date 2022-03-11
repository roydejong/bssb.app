<?php

namespace app\Models;

use app\Controllers\API\V1\AnnounceResultsController;
use app\Data\AnnounceProcessor;
use SoftwarePunt\Instarecord\Model;

class LevelHistory extends Model
{
    public int $id;
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
     * Characteristic for the level that was played.
     */
    public ?string $characteristic;
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
}