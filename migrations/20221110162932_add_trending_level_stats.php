<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTrendingLevelStats extends AbstractMigration
{
    public function change(): void
    {
        $this->table('level_records')
            ->addColumn('stat_play_count_alt', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('stat_play_count_day', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('stat_play_count_week', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('trend_factor', 'decimal', ['default' => 0.0, 'precision' => 9, 'scale' => 3, 'signed' => false])
            ->update();
    }
}
