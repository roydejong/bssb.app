<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHiddenChangelogFlag extends AbstractMigration
{
    public function change(): void
    {
        $this->table('changelogs')
            ->addColumn('is_hidden', 'boolean', ['default' => false])
            ->update();
    }
}
