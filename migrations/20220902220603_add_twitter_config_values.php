<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTwitterConfigValues extends AbstractMigration
{
    public function change(): void
    {
        $this->table('system_config')
            ->addColumn('twitter_oauth_token', 'string', ['null' => true, 'default' => null])
            ->addColumn('twitter_oauth_token_secret', 'string', ['null' => true, 'default' => null])
            ->addColumn('twitter_user_id', 'string', ['null' => true, 'default' => null])
            ->update();
    }
}
