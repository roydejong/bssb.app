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
        $this->assertSame("58EB1C803030D10EE71E91D4FE6C966B09AC341C",
            LevelId::getHashFromLevelId("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C_71e5 (Moudoku ga Osou - Tootie)"));
        $this->assertSame("CF5E32D6B7F30095F7198DA5894139C92336CAD7",
            LevelId::getHashFromLevelId("custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7_cf5e32d6b7f30095f7198da5894139c92336cad7"));

        $this->assertSame(null, LevelId::getHashFromLevelId("100Bills"));
        $this->assertSame(null, LevelId::getHashFromLevelId("custom_level_but_not_a_valid_hash"));
    }

    public function testCleanLevelHash()
    {
        $this->assertSame("100Bills",
            LevelId::cleanLevelHash("100Bills"));
        $this->assertSame("invalid_stays_invalid",
            LevelId::cleanLevelHash("invalid_stays_invalid"));
        $this->assertSame("custom_level_99AF58B669D084C2D6639E1E86D51E208D7A4D04",
            LevelId::cleanLevelHash("custom_level_99AF58B669D084C2D6639E1E86D51E208D7A4D04"));
        $this->assertSame("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C",
            LevelId::cleanLevelHash("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C_71e5 (Moudoku ga Osou - Tootie)"));
        $this->assertSame("custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7",
            LevelId::cleanLevelHash("custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7_cf5e32d6b7f30095f7198da5894139c92336cad7"));
        $this->assertSame("custom_level_6089D255B56DEA46C030AEB3A66BE8AA48029E7D",
            LevelId::cleanLevelHash("custom_level_6089D255B56DEA46C030AEB3A66BE8AA48029E7D_Ievan Polkka"));
        $this->assertSame("custom_level_9CA1E958CAC871EB1520751244D2BCED44768FCC",
            LevelId::cleanLevelHash("custom_level_9CA1E958CAC871EB1520751244D2BCED44768FCC_Savages"));
        $this->assertSame("custom_level_27FCBAB3FB731B16EABA14A5D039EEFFD7BD44C9",
            LevelId::cleanLevelHash("custom_level_27FCBAB3FB731B16EABA14A5D039EEFFD7BD44C9_1ef6 (Overkill - Krydar)"));
    }


    public function testIsCustomLevel()
    {
        $this->assertTrue(LevelId::isCustomLevel("custom_level_99AF58B669D084C2D6639E1E86D51E208D7A4D04"));
        $this->assertTrue(LevelId::isCustomLevel("custom_level_7E440420317E48395F740B114DA20B6698B220D2"));
        $this->assertTrue(LevelId::isCustomLevel("custom_level_58EB1C803030D10EE71E91D4FE6C966B09AC341C_71e5 (Moudoku ga Osou - Tootie)"));
        $this->assertTrue(LevelId::isCustomLevel("custom_level_CF5E32D6B7F30095F7198DA5894139C92336CAD7_cf5e32d6b7f30095f7198da5894139c92336cad7"));

        $this->assertFalse(LevelId::isCustomLevel("100Bills"));
        $this->assertFalse(LevelId::isCustomLevel("custom_level_but_not_a_valid_hash"));
    }
}
