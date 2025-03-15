<?php

use app\Models\PlayerAvatar;
use PHPUnit\Framework\TestCase;

class PlayerAvatarTest extends TestCase
{
    public function testFillAvatarData_DefaultEmpty()
    {
        $avatar = new PlayerAvatar();
        $avatar->fillAvatarData([]);

        // Ensure all avatar parts are filled with some default value
        $this->assertNotEmpty($avatar->headTopId);
        $this->assertNotEmpty($avatar->headTopPrimaryColor);
        $this->assertNotEmpty($avatar->headTopSecondaryColor);
        $this->assertNotEmpty($avatar->glassesId);
        $this->assertNotEmpty($avatar->glassesColor);
        $this->assertNotEmpty($avatar->facialHairId);
        $this->assertNotEmpty($avatar->facialHairColor);
        $this->assertNotEmpty($avatar->handsId);
        $this->assertNotEmpty($avatar->handsColor);
        $this->assertNotEmpty($avatar->clothesId);
        $this->assertNotEmpty($avatar->clothesPrimaryColor);
        $this->assertNotEmpty($avatar->clothesSecondaryColor);
        $this->assertNotEmpty($avatar->clothesDetailColor);
        $this->assertNotEmpty($avatar->skinColorId);
        $this->assertNotEmpty($avatar->eyesId);
        $this->assertNotEmpty($avatar->mouthId);
    }

    public function testFillAvatarData_WithAvatarExtras()
    {
        $avatar = new PlayerAvatar();

        $avatar->fillAvatarData([
            'skinColorId' => 'Alien',
            'glassesId' => 'Alien', // game has a bug where skin color is sent under glasses
            'facialHairId' => '#Glasses01$Moustache02' // BeatSaberAvatarExtras packs facial hair with its custom parts
        ]);

        $this->assertSame('Alien', $avatar->skinColorId);
        $this->assertSame('Glasses01', $avatar->glassesId);
        $this->assertSame('Moustache02', $avatar->facialHairId);
    }

    public function testFillAvatarData_WithUnityHexColors()
    {
        $avatar = new PlayerAvatar();

        $avatar->fillAvatarData([
            'skinColorId' => 'Alien',
            'headTopPrimaryColor' => '#7C0033FF'
        ]);

        $this->assertSame('Alien', $avatar->skinColorId);
        $this->assertSame('#7C0033FF', $avatar->headTopPrimaryColor);
    }
}
