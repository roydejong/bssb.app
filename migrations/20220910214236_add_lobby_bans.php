<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLobbyBans extends AbstractMigration
{
    public function change(): void
    {
        $this->table('lobby_bans')
            ->addColumn('type', 'string')
            ->addColumn('value', 'string')
            ->addColumn('expires', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('comment', 'text', ['null' => true, 'default' => null])
            ->addColumn('created', 'datetime')
            ->create();
    }
}
