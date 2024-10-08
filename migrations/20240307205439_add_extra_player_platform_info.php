<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddExtraPlayerPlatformInfo extends AbstractMigration
{
    public function change(): void
    {
        $this->table('players')
            ->addColumn('platform_authed', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('platform_avatar_url', 'string', ['null' => true, 'default' => null])
            ->addColumn('platform_ownership_confirmed', 'boolean', ['null' => false, 'default' => false])
            ->update();
    }
}
