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
}
