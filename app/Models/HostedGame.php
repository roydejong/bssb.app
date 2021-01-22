<?php

namespace app\Models;

use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\BSSB;
use Hashids\Hashids;
use Instasell\Instarecord\Model;

class HostedGame extends Model implements \JsonSerializable
{
    // -----------------------------------------------------------------------------------------------------------------
    // Consts

    const MAX_PLAYER_LIMIT_VANILLA = 5;
    const MAX_PLAYER_LIMIT_MODDED = 10;

    // -----------------------------------------------------------------------------------------------------------------
    // Columns

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
    public ?\DateTime $endedAt;

    // -----------------------------------------------------------------------------------------------------------------
    // Lifecycle

    /**
     * The amount of minutes before a game is considered "stale".
     *  → Stale games should not be shown on the site, should not be visible on the API, and should be archived.
     */
    public const STALE_GAME_AFTER_MINUTES = 5;

    public function getIsStale(): bool
    {
        return $this->lastUpdate < self::getStaleGameCutoff();
    }

    public static function getStaleGameCutoff(): \DateTime
    {
        $cutoffMinutes = self::STALE_GAME_AFTER_MINUTES;

        return (new \DateTime('now'))
            ->modify("-{$cutoffMinutes} minutes");
    }

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
        return LevelDifficulty::describe($this->difficulty);
    }

    public function describeState(): string
    {
        return MultiplayerLobbyState::describe($this->lobbyState);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Moderation

    public function getIsUninteresting(): bool
    {
        return $this->gameName === "testing, dont join";
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