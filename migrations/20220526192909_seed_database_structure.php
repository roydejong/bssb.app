<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedDatabaseStructure extends AbstractMigration
{
    public function up(): void
    {
        // Seed initial database
        $sql = file_get_contents(__DIR__ . "/20220526192909_seed_database_structure.sql");
        $this->execute($sql);
    }

    public function down(): void
    {
        echo "No-op, database structure left intact" . PHP_EOL;
    }
}
