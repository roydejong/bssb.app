<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class FixDefaultValuesForProfileStats extends AbstractMigration
{
    public function change(): void
    {
        $this->table('profile_stats')
            ->changeColumn('hosts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('joins', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('plays', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('good_cuts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('bad_cuts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('miss_count', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->changeColumn('total_score', 'integer', ['signed' => false, 'null' => false, 'length' => MysqlAdapter::INT_BIG, 'default' => 0])
            ->changeColumn('max_combo', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->update();
    }
}
