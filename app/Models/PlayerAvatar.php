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

        // Process BeatSaberAvatarExtras
        if (str_starts_with($this->facialHairId, '#')) {
            $extraParts = explode('$', substr($this->facialHairId, 1));

            if (count($extraParts) >= 2) {
                $this->glassesId = $extraParts[0] ?? null;
                $this->facialHairId = $extraParts[1] ?? null;
            }
        }
    }

    private function convertUnityColorToHex(array|string|null $unityColorData): string
    {
        if (empty($unityColorData)) {
            return "#ffffffff";
        }

        if (is_string($unityColorData) && preg_match('/^#[0-9a-fA-F]{8}$/', $unityColorData)) {
            return $unityColorData;
        }

        $r = 255;
        $g = 255;
        $b = 255;
        $a = 255;

        foreach ($unityColorData as $key => $value)
        {
            switch (strtolower($key))
            {
                case 'r':
                    $r = intval($value);
                    break;
                case 'g':
                    $g = intval($value);
                    break;
                case 'b':
                    $b = intval($value);
                    break;
                case 'a':
                    $a = intval($value);
                    break;
            }
        }

        return sprintf("#%02x%02x%02x%02x", $r, $g, $b, $a); // #0d00ff
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function jsonSerialize(): array
    {
        return $this->getPropertyValues();
    }
}