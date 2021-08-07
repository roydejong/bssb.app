<?php

use app\Models\SystemConfig;
use PHPUnit\Framework\TestCase;

class SystemConfigTest extends TestCase
{
    public function testGetCleanServerMessage()
    {
        $this->assertSame(
            "Important yellow msg",
            (new SystemConfig(['serverMessage' => "<color=#ffff00>Important yellow msg"]))->getCleanServerMessage()
        );
        $this->assertSame(
            "Regular msg",
            (new SystemConfig(['serverMessage' => "Regular msg"]))->getCleanServerMessage()
        );
        $this->assertSame(
            null,
            (new SystemConfig())->getCleanServerMessage()
        );
    }
}
