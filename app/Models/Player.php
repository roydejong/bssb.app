<?php

namespace app\Models;

use app\Models\Enums\PlayerType;
use SoftwarePunt\Instarecord\Model;

/**
 * Permanent record of a player that has been seen by the server browser.
 * This is used to populate a history of played levels.
 */
class Player extends Model
{
    public const BeatTogetherUserId = "ziuMSceapEuNN7wRGQXrZg";

    public int $id;
    public string $userId;
    public string $userName;
    public PlayerType $type;
    public ?string $platformType;
    public ?string $platformUserId;
    public \DateTime $firstSeen;
    public \DateTime $lastSeen;

    // -----------------------------------------------------------------------------------------------------------------
    // Create/update

    /**
     * Update or create a player record based on a HostedGamePlayer, then return it.
     */
    public static function fromServerPlayer(HostedGamePlayer $serverPlayer): Player
    {
        $playerRecord = Player::query()
            ->where('user_id = ?', $serverPlayer->userId)
            ->querySingleModel();

        $now = new \DateTime('now');

        if ($playerRecord === null) {
            $playerRecord = new Player();
            $playerRecord->userId = $serverPlayer->userId;
            $playerRecord->userName = $serverPlayer->userName;
            $playerRecord->type = PlayerType::PlayerObserved;
            $playerRecord->platformType = null;
            $playerRecord->platformUserId = null;
            $playerRecord->firstSeen = $now;
        }

        if ($serverPlayer->isHost && !$serverPlayer->isAnnouncer && empty($serverPlayer->userName)) {
            // This is a dedicated server bot, try to figure out what kind
            if ($playerRecord->userId === self::BeatTogetherUserId) {
                $playerRecord->type = PlayerType::DedicatedServerBeatTogether;
            } else if (str_starts_with($playerRecord->userId, "arn:aws:gamelift")) {
                $playerRecord->type = PlayerType::DedicatedServerGameLift;
            } else {
                $playerRecord->type = PlayerType::DedicatedServer;
            }
        } else if ($playerRecord->type === PlayerType::PlayerObserved && $serverPlayer->isAnnouncer) {
            // If player has announced, upgrade type from "observed" to "mod user"
            $playerRecord->type = PlayerType::PlayerModUser;
        }

        $playerRecord->lastSeen = $now;
        $playerRecord->save();

        return $playerRecord;
    }

    public function fetchAvatar(): ?PlayerAvatar
    {
        return PlayerAvatar::query()
            ->where('player_id = ?', $this->id)
            ->querySingleModel();
    }

    public function syncAvatarData(array $avatarData): ?PlayerAvatar
    {
        if (!$this->id)
            throw new \RuntimeException("Cannot sync avatar data before saving player model");

        $avatar = $this->fetchAvatar();

        if (!$avatar) {
            $avatar = new PlayerAvatar();
            $avatar->playerId = $this->id;
        }

        $avatar->fillAvatarData($avatarData);
        $avatar->save();

        return $avatar;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data

    public function getDisplayName(): string
    {
        if ($this->userName)
            return $this->userName;

        if ($this->getIsDedicatedServer())
            return "Dedicated Server";

        return "Unnamed Player";
    }

    public function getIsDedicatedServer(): bool
    {
        return ($this->type === PlayerType::DedicatedServer || $this->type === PlayerType::DedicatedServerGameLift
            || $this->type === PlayerType::DedicatedServerBeatTogether);
    }

    public function describeType(bool $shorten = false): string
    {
        $result = match ($this->type) {
            PlayerType::PlayerObserved => "Player",
            PlayerType::PlayerModUser => $shorten ? "Player" : "Player with Server Browser",
            PlayerType::DedicatedServer => "Dedicated Server",
            PlayerType::DedicatedServerGameLift => "GameLift Server",
            PlayerType::DedicatedServerBeatTogether => "BeatTogether Server",
            default => "Unknown",
        };
        if ($shorten) {
            $result = strtolower($result);
        }
        return $result;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // URLs

    public static function cleanUserIdForUrl(string $userId): string
    {
        return str_replace("/", "_", $userId);
    }

    public static function restoreUserIdFromUrl(string $urlUserId): string
    {
        return str_replace("_", "/", $urlUserId);
    }

    public function getUrlSafeUserId(): string
    {
        return self::cleanUserIdForUrl($this->userId);
    }

    public function getWebDetailUrl(): string
    {
        return "/player/{$this->getUrlSafeUserId()}";
    }
}