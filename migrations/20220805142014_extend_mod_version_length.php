<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExtendModVersionLength extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_games')
            ->changeColumn('mod_version', 'string', ['length' => 64, 'null' => true, 'default' => null])
            ->update();
    }
}
