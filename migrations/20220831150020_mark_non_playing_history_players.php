<?php
declare(strict_types=1);

use app\BeatSaber\Enums\PlayerLevelEndReason;
use app\BeatSaber\Enums\PlayerLevelEndState;
use app\Models\LevelHistory;
use app\Models\LevelHistoryPlayer;
use Phinx\Migration\AbstractMigration;

final class MarkNonPlayingHistoryPlayers extends AbstractMigration
{
    public function change(): void
    {
        $finishedGames = LevelHistory::query()
            ->where('started_at IS NOT NULL')
            ->andWhere('ended_at IS NOT NULL')
            ->queryAllModels();

        foreach ($finishedGames as $finishedGame) {
            LevelHistoryPlayer::query()
                ->update()
                ->set([
                    'end_reason' => PlayerLevelEndReason::WasInactive->value,
                    'end_state' => PlayerLevelEndState::NotStarted->value
                ])
                ->where('level_history_id = ?', $finishedGame->id)
                ->andWhere('end_reason IS NULL')
                ->andWhere('end_state IS NULL')
                ->execute();
        }
    }
}
