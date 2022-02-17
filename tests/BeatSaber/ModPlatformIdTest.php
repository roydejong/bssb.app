<?php

namespace tests\BeatSaber;

use app\BeatSaber\ModPlatformId;
use PHPUnit\Framework\TestCase;

class ModPlatformIdTest extends TestCase
{
    public function testNormalize()
    {
        $this->assertSame("unknown", ModPlatformId::normalize(null));
        $this->assertSame("unknown", ModPlatformId::normalize(""));
        $this->assertSame("someinput", ModPlatformId::normalize("SomeInput"));
        $this->assertSame("steam", ModPlatformId::normalize("Steam"));
        $this->assertSame("oculus", ModPlatformId::normalize("Oculus"));
        $this->assertSame("oculus", ModPlatformId::normalize("OculusPC"));
        $this->assertSame("oculus", ModPlatformId::normalize("OculusQuest"));
    }
}
