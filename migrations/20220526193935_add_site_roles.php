<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSiteRoles extends AbstractMigration
{
    public function change(): void
    {
        $this->table('site_roles')
            ->addColumn('name', 'string')
            ->addColumn('is_admin', 'boolean', ['default' => false])
            ->create();

        $this->table('players')
            ->addColumn('site_role_id', 'integer', ['null' => true, 'default' => null, 'after' => 'type'])
            ->addForeignKey('site_role_id', 'site_roles', 'id', ['update' => 'CASCADE', 'delete' => 'SET_NULL'])
            ->update();
    }
}
