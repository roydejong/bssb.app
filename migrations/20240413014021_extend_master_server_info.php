<?php
declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ExtendMasterServerInfo extends AbstractMigration
{
    public function change(): void
    {
        $this->table('master_server_info')
            ->addColumn('use_ssl', 'boolean', ['after' => 'graph_url', 'null' => false, 'default' => false])
            ->addColumn('description', 'text', ['after' => 'nice_name', 'null' => true, 'default' => null, 'length' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('image_url', 'string', ['after' => 'description', 'null' => true, 'default' => null])
            ->addColumn('max_players', 'integer', ['after' => 'image_url', 'null' => true, 'default' => null])
            ->update();
    }
}
