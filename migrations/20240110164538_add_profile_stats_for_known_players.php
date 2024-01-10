<?php
declare(strict_types=1);

use app\Models\LevelHistoryPlayer;
use app\Models\ProfileStats;
use Phinx\Migration\AbstractMigration;

final class AddProfileStatsForKnownPlayers extends AbstractMigration
{
    public function up(): void
    {
        $knownPlayerIds = LevelHistoryPlayer::query()
            ->select('DISTINCT player_id')
            ->orderBy('id DESC')
            ->querySingleValueArray();

        echo "Flagging " . count($knownPlayerIds) . " players for profile stats update...\n";

        foreach ($knownPlayerIds as $playerId) {
            ProfileStats::flagUpdateNeeded($playerId);
        }
    }

    public function down()
    {
        // no-op
    }
}
