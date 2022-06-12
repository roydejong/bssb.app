<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFriends extends AbstractMigration
{
    public function change(): void
    {
        $this->table('player_friends')
            ->addColumn('player_one_id', 'integer', ['signed' => false, 'comment' => 'User that sent the friend request'])
            ->addColumn('player_two_id', 'integer', ['signed' => false, 'comment' => 'User that received the friend request'])
            ->addColumn('is_pending', 'boolean', ['default' => true])
            ->addColumn('requested_at', 'datetime')
            ->addColumn('accepted_at', 'datetime', ['null' => true, 'default' => null])
            ->addForeignKey('player_one_id', 'players', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addForeignKey('player_two_id', 'players', 'id', ['update' => 'CASCADE', 'delete' => 'CASCADE'])
            ->addIndex(['player_one_id', 'player_two_id'], ['unique' => true])
            ->create();
    }
}
