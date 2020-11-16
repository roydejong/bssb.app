<?php

namespace app\Models;

use app\BeatSaber\ModPlatformId;
use app\BSSB;
use Hashids\Hashids;
use Instasell\Instarecord\Model;

class HostedGame extends Model implements \JsonSerializable
{
    public int $id;
    public string $serverCode;
    public string $gameName;
    public string $ownerId;
    public string $ownerName;
    public int $playerCount;
    public int $playerLimit;
    public bool $isModded;
    public \DateTime $firstSeen;
    public \DateTime $lastUpdate;
    public int $lobbyState;
    public ?string $levelId;
    public ?string $songName;
    public ?string $songAuthor;
    public ?int $difficulty;
    public string $platform = ModPlatformId::UNKNOWN;
    public ?string $masterServerHost;
    public ?int $masterServerPort;

    // -----------------------------------------------------------------------------------------------------------------
    // Data helpers

    public function getIsOfficial(): bool
    {
        return $this->masterServerHost === null
            || strpos($this->masterServerHost, ".mp.beatsaber.com") !== false;
    }

    public function describeMasterServer(): string
    {
        if ($this->getIsOfficial()) {
            return "Official";
        } else if ($this->masterServerHost) {
            return $this->masterServerHost;
        } else {
            return "Unknown";
        }
    }

    public function describeSong(): string
    {
        $parts = [];
        if ($this->songAuthor)
            $parts[] = $this->songAuthor;
        if ($this->songName)
            $parts[] = $this->songName;
        return implode(' - ', $parts);
    }

    public function describeDifficulty(): string
    {
        switch ($this->difficulty) {
            case null:
            default:
                return "None";
            case 0:
                return "Easy";
            case 1:
                return "Normal";
            case 2:
                return "Hard";
            case 3:
                return "Expert";
            case 4:
                return "Expert+";
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // URLs

    private static function getHashids(): Hashids
    {
        return BSSB::getHashids("HostedGame");
    }

    public static function hash2id(string $hash): ?int
    {
        $arr = self::getHashids()->decode($hash);
        return !empty($arr) ? intval($arr[0]) : null;
    }

    public static function id2hash(int $id): string
    {
        return self::getHashids()->encode($id);
    }

    public function getWebDetailUrl(): string
    {
        $hash = self::id2hash($this->id);
        return "/game/{$hash}";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Serialize

    public function jsonSerialize()
    {
        return $this->getPropertyValues();
    }
}