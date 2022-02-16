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
        $this->headTopId = $avatarData['headTopId'] ?? null;
        $this->headTopPrimaryColor = $this->convertUnityColorToHex($avatarData['headTopPrimaryColor']);
        $this->headTopSecondaryColor = $this->convertUnityColorToHex($avatarData['headTopSecondaryColor']);
        $this->glassesId = $avatarData['glassesId'] ?? null;
        $this->glassesColor = $this->convertUnityColorToHex($avatarData['glassesColor']);
        $this->facialHairId = $avatarData['facialHairId'] ?? null;
        $this->facialHairColor = $this->convertUnityColorToHex($avatarData['facialHairColor']);
        $this->handsId = $avatarData['handsId'] ?? null;
        $this->handsColor = $this->convertUnityColorToHex($avatarData['handsColor']);
        $this->clothesId = $avatarData['clothesId'] ?? null;
        $this->clothesPrimaryColor = $this->convertUnityColorToHex($avatarData['clothesPrimaryColor']);
        $this->clothesSecondaryColor = $this->convertUnityColorToHex($avatarData['clothesSecondaryColor']);
        $this->clothesDetailColor = $this->convertUnityColorToHex($avatarData['clothesDetailColor']);
        $this->skinColorId = $avatarData['skinColorId'] ?? null;
        $this->eyesId = $avatarData['eyesId'] ?? null;
        $this->mouthId = $avatarData['mouthId'] ?? null;

        if (empty($this->headTopId)) $this->headTopId = "None";
        if (empty($this->glassesId) || $this->glassesId === "Default") $this->glassesId = "None";
        if (empty($this->facialHairId)) $this->facialHairId = "None";
        if (empty($this->handsId)) $this->handsId = "BareHands";
        if (empty($this->clothesId)) $this->clothesId = "Tracksuit";
        if (empty($this->clothesPrimaryColor)) $this->clothesPrimaryColor = "#fff";
        if (empty($this->clothesSecondaryColor)) $this->clothesSecondaryColor = "#fff";
        if (empty($this->clothesDetailColor)) $this->clothesDetailColor = "#fff";
        if (empty($this->skinColorId)) $this->skinColorId = "Zombie";
        if (empty($this->eyesId)) $this->eyesId = "QuestionMark";
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