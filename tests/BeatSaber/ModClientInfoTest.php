<?php

namespace tests\BeatSaber;

use app\BeatSaber\ModClientInfo;
use app\BeatSaber\ModPlatformId;
use PHPUnit\Framework\TestCase;

class ModClientInfoTest extends TestCase
{
    public function testFromUserAgent_v011()
    {
        $input = "ServerBrowser/0.1.1.0 (BeatSaber/1.12.2)";
        $result = ModClientInfo::fromUserAgent($input);

        $this->assertSame("ServerBrowser", $result->modName);
        $this->assertSame("0.1.1.0", (string)$result->assemblyVersion);
        $this->assertSame("1.12.2", (string)$result->beatSaberVersion);
        $this->assertSame(ModPlatformId::UNKNOWN, $result->platformId);
    }

    public function testFromUserAgent_v0200()
    {
        $input = "ServerBrowser/0.2.0 (BeatSaber/1.12.2) (steam)";
        $result = ModClientInfo::fromUserAgent($input);

        $this->assertSame("ServerBrowser", $result->modName);
        $this->assertSame("0.2.0", (string)$result->assemblyVersion);
        $this->assertSame("1.12.2", (string)$result->beatSaberVersion);
        $this->assertSame(ModPlatformId::STEAM, $result->platformId);
    }

    public function testFromUserAgentBeatNet()
    {
        $input = "BeatNet/1.0.0 (BeatSaber/1.16.1) (dedi)";
        $result = ModClientInfo::fromUserAgent($input);

        $this->assertSame("BeatNet", $result->modName);
        $this->assertSame("1.0.0", (string)$result->assemblyVersion);
        $this->assertSame("1.16.1", (string)$result->beatSaberVersion);
        $this->assertSame(ModPlatformId::DEDI, $result->platformId);
    }

    public function testFromUserAgent_invalid_blank()
    {
        $input = "";
        $result = ModClientInfo::fromUserAgent($input);

        $this->assertSame(null, $result->modName);
        $this->assertSame(null, $result->assemblyVersion);
        $this->assertSame(null, $result->beatSaberVersion);
        $this->assertSame(ModPlatformId::UNKNOWN, $result->platformId);
    }
}
