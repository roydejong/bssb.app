<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class AddCalculatedProfileStats extends AbstractMigration
{
    public function change(): void
    {
        $this->table('profile_stats')
            ->addColumn('player_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('needs_update', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('hosts', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('joins', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('plays', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('good_cuts', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('bad_cuts', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('miss_count', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('total_score', 'integer', ['signed' => false, 'null' => false, 'length' => MysqlAdapter::INT_BIG])
            ->addColumn('max_combo', 'integer', ['signed' => false, 'null' => false])
            ->addForeignKey('player_id', 'players', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['player_id'], ['unique' => true])
            ->create();
    }
}
