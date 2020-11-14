<?php

namespace BeatSaber;

use app\BeatSaber\MasterServer;
use PHPUnit\Framework\TestCase;

class MasterServerTest extends TestCase
{
    public function testGetHostnameIsOfficial()
    {
        $this->assertTrue(MasterServer::getHostnameIsOfficial(null));
        $this->assertTrue(MasterServer::getHostnameIsOfficial(""));
        $this->assertTrue(MasterServer::getHostnameIsOfficial("oculus.production.mp.beatsaber.com"));
        $this->assertTrue(MasterServer::getHostnameIsOfficial("steam.staging.mp.beatsaber.com"));

        $this->assertFalse(MasterServer::getHostnameIsOfficial("crossplay.mp.beatfaker.com"));
    }
}
