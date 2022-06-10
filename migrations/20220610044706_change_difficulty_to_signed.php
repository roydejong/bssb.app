<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeDifficultyToSigned extends AbstractMigration
{
    public function up(): void
    {
        $this->table('hosted_games')
            ->changeColumn('difficulty', 'tinyinteger', ['signed' => true, 'null' => true, 'default' => null])
            ->update();
    }
}
