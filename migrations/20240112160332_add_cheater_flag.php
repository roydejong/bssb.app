<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCheaterFlag extends AbstractMigration
{
    public function change(): void
    {
        $this->table('players')
            ->addColumn('is_cheater', 'boolean', ['default' => false])
            ->update();
    }
}
