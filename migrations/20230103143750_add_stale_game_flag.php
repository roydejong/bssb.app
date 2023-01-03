<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddStaleGameFlag extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_games')
            ->addColumn('is_stale', 'boolean', ['default' => false, 'after' => 'last_update'])
            ->addIndex('is_stale')
            ->update();
    }
}
