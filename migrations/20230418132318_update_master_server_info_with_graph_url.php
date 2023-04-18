<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateMasterServerInfoWithGraphUrl extends AbstractMigration
{
    public function up(): void
    {
        $this->table('master_server_info')
            ->addColumn('graph_url', 'string', ['default' => null, 'null' => true, 'after' => 'id'])
            ->changeColumn('host', 'string', ['default' => null, 'null' => true, 'after' => 'graph_url'])
            ->changeColumn('port', 'integer', ['default' => null, 'null' => true, 'after' => 'host'])
            ->addIndex('graph_url', ['unique' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('master_server_info')
            ->removeIndex('graph_url')
            ->removeColumn('graph_url')
            ->changeColumn('host', 'string', ['null' => false, 'after' => 'id'])
            ->changeColumn('port', 'integer', ['default' => 2328, 'null' => false, 'after' => 'host'])
            ->update();
    }
}

