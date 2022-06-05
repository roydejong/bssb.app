<?php

namespace tests;

use app\BeatSaber\Enums\MultiplayerAvailabilityStatus;
use app\Common\CVersion;
use app\External\MasterServerStatus;
use PHPUnit\Framework\TestCase;

class MasterServerStatusTest extends TestCase
{
    public function testFromJson_BeatTogetherSample()
    {
        $sample = '{"minimumAppVersion":"1.17.1","status":0,"maintenanceStartTime":0,"maintenanceEndTime":0,"userMessage":{"localizedMessages":[]}}';
        $parsed = MasterServerStatus::fromJson($sample);

        $this->assertNotNull($parsed, "MasterServerStatus parse from valid JSON should succeed");
        $this->assertEquals(new CVersion("1.17.1"), $parsed->minimumAppVersion);
        $this->assertEquals(MultiplayerAvailabilityStatus::Online, $parsed->status);
        $this->assertNull($parsed->maintenanceStartTime);
        $this->assertNull($parsed->maintenanceEndTime);
        $this->assertFalse($parsed->useGamelift);
    }

    public function testFromJson_OculusSample()
    {
        $sample = '{
   "data": [
      {
         "minimum_app_version": "1.22.0",
         "status": 0,
         "maintenance_start_time": 0,
         "maintenance_end_time": 0,
         "use_gamelift": true
      }
   ]
}';
        $parsed = MasterServerStatus::fromJson($sample);

        $this->assertNotNull($parsed, "MasterServerStatus parse from valid JSON should succeed");
        $this->assertEquals(new CVersion("1.22.0"), $parsed->minimumAppVersion);
        $this->assertEquals(MultiplayerAvailabilityStatus::Online, $parsed->status);
        $this->assertNull($parsed->maintenanceStartTime);
        $this->assertNull($parsed->maintenanceEndTime);
        $this->assertTrue($parsed->useGamelift);
    }
}
