<?php

namespace Models;

use app\Models\LevelRecord;
use PHPUnit\Framework\TestCase;

class LevelRecordTest extends TestCase
{
    public function testSyncNativeCover()
    {
        $testLevel = new LevelRecord();
        $testLevel->levelId = "Ugh";

        if ($origLevel = $testLevel->fetchExisting()) {
            @$origLevel->delete();
        }

        $testLevel->songName = "testSyncNativeCover";
        $testLevel->hash = "testSyncNativeCover";

        try {
            $testLevel->syncNativeCover();

            $this->assertSame("https://bssb.app/static/bsassets/Ugh.png", $testLevel->coverUrl);
        } finally {
            @$testLevel->delete();
        }
    }

    public function testIncrementPlayCount()
    {
        $testLevel = new LevelRecord();
        $testLevel->levelId = "testIncrementPlayCount";

        if ($prevTestRecord = $testLevel->fetchExisting()) {
            $prevTestRecord->delete();
        }

        try {
            // Create test record
            $this->assertTrue($testLevel->save(), "Stub level record should be created succesfully");
            $this->assertSame(0, $testLevel->statPlayCount, "New created level record should have zero plays");

            // Test increment
            $this->assertTrue($testLevel->incrementPlayStat());
            $this->assertTrue($testLevel->incrementPlayStat());
            $this->assertTrue($testLevel->incrementPlayStat());

            // Read again
            $testLevel = LevelRecord::fetch($testLevel->id);
            $this->assertSame(3, $testLevel->statPlayCount, "Play count stat should be updated");
        } finally {
            if (isset($testLevel->id)) {
                $testLevel->delete();
            }
        }
    }
}
