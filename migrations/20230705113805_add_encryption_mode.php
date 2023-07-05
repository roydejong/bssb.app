<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEncryptionMode extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hosted_games')
            ->addColumn('encryption_mode', 'string', ['null' => true, 'default' => null])
            ->update();
    }
}
