<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class SeparateLobbyDifficulty extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_games')
            ->addColumn('level_difficulty', 'integer', ['length' => MysqlAdapter::INT_TINY,
                'after' => 'difficulty', 'null' => true, 'default' => null])
            ->update();

        if ($this->isMigratingUp()) {
            $this->execute("UPDATE hosted_games SET level_difficulty = difficulty WHERE level_difficulty IS NULL;");
        }
    }
}
