<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChangelog extends AbstractMigration
{
    public function change(): void
    {
        $this->table('changelogs')
            ->addColumn('publish_date', 'datetime')
            ->addColumn('title', 'string')
            ->addColumn('text', 'text', ['default' => null, 'null' => true])
            ->addColumn('is_alert', 'boolean', ['default' => false])
            ->addColumn('tweet_id', 'string', ['default' => null, 'null' => true])
            ->create();

        if ($this->isMigratingUp()) {
            $this->table('changelogs')
                ->insert([
                    'publish_date' => '2022-08-30 17:30:00',
                    'title' => 'Beat Saber 1.24.1 released',
                    'text' => 'This version is compatible with 1.20 and up for multiplayer. PC users can safely update, Quest users should stay on 1.24.0 for now.',
                    'tweet_id' => '1565704928722653186'
                ])
                ->insert([
                    'publish_date' => (new DateTime('now'))->format('c'),
                    'title' => 'News & updates on BSSB',
                    'text' => 'We\'ll share mod, game and service updates here now to keep you up to date.',
                    'tweet_id' => '1565704303024766976'
                ])
                ->saveData();
        }
    }
}
