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
    public function testSyncOneMoreTimeCover()
    {
        $omtLevel = new LevelRecord();
        $omtLevel->levelId = "custom_level_3C01DA2A69BA6EB3C2EFD50EEB7C431F09C44C3B";

        if ($omtExistingRecord = $omtLevel->fetchExisting()) {
            $omtLevel = $omtExistingRecord;
            $omtLevel->coverUrl = null;
        }

        $this->assertNull($omtLevel->coverUrl, "Sanity check: coverUrl should initially be NULL for this test");

        $this->assertTrue($omtLevel->syncFromBeatSaver());
        $this->assertSame("https://bssb.app/static/bsassets/OneMoreTime.png", $omtLevel->coverUrl);
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
