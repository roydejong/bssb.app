<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLevelRecordBeatSaverVersioning extends AbstractMigration
{
    public function up(): void
    {
        // Remove the unique index on beatsaver_id
        $this->table('level_records')
            ->removeIndex('beatsaver_id')
            ->update();

        // Add version column
        $this->table('level_records')
            ->addColumn('beatsaver_version_dt', 'datetime', ['null' => true, 'default' => null, 'after' => 'beatsaver_id'])
            ->update();

        // Add new index (basic index for beatsaver lookups + unique pair index for integrity)
        $this->table('level_records')
            ->addIndex(['beatsaver_id'])
            ->addIndex(['beatsaver_id', 'beatsaver_version_dt'], ['unique' => true])
            ->update();
    }

    public function down()
    {
        echo "down() is not supported for this migration; no-op..." . PHP_EOL;
    }
}
