<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMasterServerInfo extends AbstractMigration
{
    public function change(): void
    {
        $this->table('master_server_info')
            ->addColumn('host', 'string')
            ->addColumn('port', 'integer', ['default' => 2328])
            ->addColumn('status_url', 'string', ['null' => true, 'default' => null])
            ->addColumn('resolved_ip', 'string', ['null' => true, 'default' => null])
            ->addColumn('geoip_country', 'string', ['null' => true, 'default' => null])
            ->addColumn('geoip_text', 'string', ['null' => true, 'default' => null])
            ->addColumn('nice_name', 'string', ['null' => true, 'default' => null])
            ->addColumn('is_official', 'boolean', ['default' => false])
            ->addColumn('first_seen', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_seen', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('last_status_json', 'json', ['null' => true, 'default' => null])
            ->addColumn('last_updated', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['host', 'port'], ['unique' => true])
            ->create();
    }
}
