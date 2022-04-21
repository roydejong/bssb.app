<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class PlayerAvatar extends Model implements \JsonSerializable
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public int $playerId;
    public string $headTopId;
    public string $headTopPrimaryColor;
    public string $headTopSecondaryColor;
    public string $glassesId;
    public string $glassesColor;
    public string $facialHairId;
    public string $facialHairColor;
    public string $handsId;
    public string $handsColor;
    public string $clothesId;
    public string $clothesPrimaryColor;
    public string $clothesSecondaryColor;
    public string $clothesDetailColor;
    public string $skinColorId;
    public string $eyesId;
    public string $mouthId;

    // -----------------------------------------------------------------------------------------------------------------
    // Parse

    public function fillAvatarData(array $avatarData): void
    {
        $this->headTopId = $avatarData['headTopId'] ?? "None";
        $this->headTopPrimaryColor = $this->convertUnityColorToHex($avatarData['headTopPrimaryColor'] ?? null);
        $this->headTopSecondaryColor = $this->convertUnityColorToHex($avatarData['headTopSecondaryColor'] ?? null);
        $this->glassesId = $avatarData['glassesId'] ?? "None";
        $this->glassesColor = $this->convertUnityColorToHex($avatarData['glassesColor'] ?? null);
        $this->facialHairId = $avatarData['facialHairId'] ?? "None";
        $this->facialHairColor = $this->convertUnityColorToHex($avatarData['facialHairColor'] ?? null);
        $this->handsId = $avatarData['handsId'] ?? "BareHands";
        $this->handsColor = $this->convertUnityColorToHex($avatarData['handsColor'] ?? null);
        $this->clothesId = $avatarData['clothesId'] ?? "None";
        $this->clothesPrimaryColor = $this->convertUnityColorToHex($avatarData['clothesPrimaryColor'] ?? null);
        $this->clothesSecondaryColor = $this->convertUnityColorToHex($avatarData['clothesSecondaryColor'] ?? null);
        $this->clothesDetailColor = $this->convertUnityColorToHex($avatarData['clothesDetailColor'] ?? null);
        $this->skinColorId = $avatarData['skinColorId'] ?? "Alien";
        $this->eyesId = $avatarData['eyesId'] ?? "QuestionMark";
        $this->mouthId = $avatarData['mouthId'] ?? "None";
    }

    private function convertUnityColorToHex(?array $unityColorData): string
    {
        if (!$unityColorData) {
            return "#ffffffff";
        }
        $r = intval($unityColorData['r']) ?? 255;
        $g = intval($unityColorData['g']) ?? 255;
        $b = intval($unityColorData['b']) ?? 255;
        $a = intval($unityColorData['a']) ?? 255;
        return sprintf("#%02x%02x%02x%02x", $r, $g, $b, $a); // #0d00ff
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function jsonSerialize(): array
    {
        return $this->getPropertyValues();
    }
}