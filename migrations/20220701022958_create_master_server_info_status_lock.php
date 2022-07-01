<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMasterServerInfoStatusLock extends AbstractMigration
{
    public function change(): void
    {
        $this->table('master_server_info')
            ->addColumn('lock_status_url', 'boolean', ['after' => 'status_url', 'default' => false])
            ->update();
    }
}
