<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHostedGamePlayersUserIdIndex extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_game_players')
            ->addIndex('user_id')
            ->update();
    }
}
