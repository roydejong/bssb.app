<?php

namespace tests\BeatSaber;

use app\BeatSaber\LevelId;
use PHPUnit\Framework\TestCase;

class LevelIdTest extends TestCase
{
    public function testGetHashFromLevelId()
    {
        $this->assertSame("99AF58B669D084C2D6639E1E86D51E208D7A4D04",
            LevelId::getHashFromLevelId("custom_level_99AF58B669D084C2D6639E1E86D51E208D7A4D04"));
        $this->assertSame("7E440420317E48395F740B114DA20B6698B220D2",
            LevelId::getHashFromLevelId("custom_level_7E440420317E48395F740B114DA20B6698B220D2"));

        $this->assertSame(null, LevelId::getHashFromLevelId("100Bills"));
        $this->assertSame(null, LevelId::getHashFromLevelId("custom_level_but_not_a_valid_hash"));
    }

    public function testIsCustomLevel()
    {
        $this->assertTrue(LevelId::isCustomLevel("custom_level_99AF58B669D084C2D6639E1E86D51E208D7A4D04"));
        $this->assertTrue(LevelId::isCustomLevel("custom_level_7E440420317E48395F740B114DA20B6698B220D2"));

        $this->assertFalse(LevelId::isCustomLevel("100Bills"));
        $this->assertFalse(LevelId::isCustomLevel("custom_level_but_not_a_valid_hash"));
    }
}
