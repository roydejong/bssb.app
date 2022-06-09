<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHideMasterServerBool extends AbstractMigration
{
    public function change(): void
    {
        $this->table('master_server_info')
            ->addColumn('hide', 'boolean', ['default' => false])
            ->update();
    }
}
