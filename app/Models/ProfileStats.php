<?php

namespace app\Models;

use app\BeatSaber\Enums\PlayerLevelEndState;
use SoftwarePunt\Instarecord\Model;

/**
 * Records the calculated statistics for a player.
 * This is used to populate the player profile page.
 *
 * A background job will calculate/update these statistics periodically if "needs_update" is set.
 */
class ProfileStats extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public int $playerId;
    public bool $needsUpdate;
    public int $hosts;
    public int $joins;
    public int $plays;
    public int $goodCuts;
    public int $badCuts;
    public int $missCount;
    public int $totalScore;
    public int $maxCombo;

    // -----------------------------------------------------------------------------------------------------------------
    // Table meta

    public function getTableName(): string
    {
        return "profile_stats";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Query util

    /**
     * Gets (and if necessary, automatically creates) a ProfileStats record for the given player.
     */
    public static function getOrCreateForPlayer(int $playerId): ProfileStats
    {
        // Ensure record exists
        ProfileStats::query()
            ->insertIgnore()
            ->values(['player_id' => $playerId, 'needs_update' => true])
            ->executeInsert();

        // Return instance
        /**
         * @var $result ProfileStats
         */
        $result = ProfileStats::query()
            ->where('player_id = ?', $playerId)
            ->querySingleModel();

        if (!$result)
            throw new \LogicException('Profile stats record should exist');

        return $result;
    }

    public static function flagUpdateNeeded(int $playerId): void
    {
        ProfileStats::query()
            ->insert()
            ->values(['player_id' => $playerId, 'needs_update' => true])
            ->onDuplicateKeyUpdate(['needs_update' => true])
            ->execute();
    }

    /**
     * Queries all ProfileStats that are pending an update (needs_update = true).
     *
     * @return ProfileStats[]
     */
    public static function queryAllPending(): array
    {
        return ProfileStats::query()
            ->where('needs_update = ?', true)
            ->orderBy('id ASC')
            ->queryAllModels();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data

    public function getMaxPotentialHitCount(): int
    {
        return $this->goodCuts + $this->badCuts + $this->missCount;
    }

    public function getHitAccuracy(): float
    {
        $max = $this->getMaxPotentialHitCount();
        if ($max === 0)
            return 0.0;
        return ($this->goodCuts / $max) * 100;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Update

    public function recalculate(Player $player): bool
    {
        // Host count
        $this->hosts = HostedGame::query()
            ->select('COUNT(id)')
            ->where('owner_id = ? OR manager_id = ?', $player->userId, $player->userId)
            ->querySingleValue() ?? 0;

        // Join count
        $this->joins = HostedGamePlayer::query()
            ->select('COUNT(id)')
            ->where('user_id = ? AND is_host = 0', $player->userId)
            ->querySingleValue() ?? 0;

        // Summary stats (plays, totalScore, goodCuts, badCuts, missCount, maxCombo)
        $sumStats = LevelHistoryPlayer::query()
            ->select('COUNT(id) AS plays, SUM(modified_score) AS totalScore, SUM(good_cuts) AS goodCuts, SUM(bad_cuts) AS badCuts, SUM(miss_count) AS missCount, MAX(max_combo) AS maxCombo')
            ->where('player_id = ?', $this->playerId)
            ->andWhere('end_state != ?', PlayerLevelEndState::NotStarted->value)
            ->limit(1)
            ->querySingleRow();
        foreach ($sumStats as $key => $value) {
            $this->$key = intval($value ?? 0);
        }

        // Overflows
        if ($this->totalScore > 18446744073709551615) {
            $this->totalScore = 18446744073709551615;
        }

        // Unflag dirty
        $this->needsUpdate = false;

        return $this->save();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function serialize(): array
    {
        $sz = $this->getPropertyValues();
        $sz['hitAcc'] = $this->getHitAccuracy();
        return $sz;
    }
}