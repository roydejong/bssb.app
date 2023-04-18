<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddGraphUrlsToHostedGames extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_games')
            ->addColumn('master_graph_url', 'string', ['null' => true, 'default' => null, 'after' => 'platform'])
            ->update();
    }
}
