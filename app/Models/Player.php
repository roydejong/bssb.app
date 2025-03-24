<?php

namespace app\Models;

use app\AWS\GameSessionArn;
use app\AWS\GameSessionArnParser;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerUserId;
use app\Frontend\View;
use app\Models\Enums\PlayerType;
use SoftwarePunt\Instarecord\Model;

/**
 * Permanent record of a player that has been seen by the server browser.
 * This is used to populate a history of played levels.
 */
class Player extends Model
{
    public const UserIdBeatTogether = "ziuMSceapEuNN7wRGQXrZg";
    public const UserIdPrefixBeatUpServer = "beatupserver:";
    public const UserIdPrefixBeatNet = "beatnet:";
    public const UserIdPrefixGameLift = "arn:aws:gamelift";

    public int $id;
    public string $userId;
    public string $userName;
    public PlayerType $type;
    public ?int $siteRoleId = null;
    public ?string $platformType;
    public ?string $platformUserId;
    public \DateTime $firstSeen;
    public \DateTime $lastSeen;
    public ?string $profileBio;
    public bool $showSteam = true;
    public bool $showScoreSaber = true;
    public bool $showHistory = true;
    public bool $isCheater = false;

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

        if ($serverPlayer->isHost && $serverPlayer->sortIndex === -1) {
            if ($playerRecord->userId === self::UserIdBeatTogether) {
                // User ID matches the fixed value for all BeatTogether-based servers
                $playerRecord->type = PlayerType::DedicatedServerBeatTogether;
            } else if (str_starts_with($playerRecord->userId, self::UserIdPrefixBeatUpServer)) {
                $playerRecord->type = PlayerType::DedicatedServerBeatUpServer;
            } else if (str_starts_with($playerRecord->userId, self::UserIdPrefixBeatNet)) {
                $playerRecord->type = PlayerType::DedicatedServerBeatNetServer;
            } else if (str_starts_with($playerRecord->userId, self::UserIdPrefixGameLift)) {
                // User ID matches the fixed value for all Official GameLift servers
                $playerRecord->type = PlayerType::DedicatedServerGameLift;
            } else {
                // No specific identifying marks, but host + negative index indicates dedicated server
                $playerRecord->type = PlayerType::DedicatedServer;
            }
        } else if ($playerRecord->type === PlayerType::PlayerObserved && $serverPlayer->isAnnouncer) {
            // If player has announced, upgrade type from "observed" to "mod user"
            $playerRecord->type = PlayerType::PlayerModUser;
        }
        
        if (!$playerRecord->getIsDedicatedServer()) {
            // Update name, but only if it's not a dedicated server
            $playerRecord->userName = $serverPlayer->userName;
        }

        $playerRecord->lastSeen = $now;
        $playerRecord->save();

        return $playerRecord;
    }

    public static function fromSteamId(string $steamId, ?string $steamUserName = null): Player
    {
        $hashedUserId = MultiplayerUserId::hash("Steam", $steamId);

        $player = Player::query()
            ->where('(user_id = ?) OR (platform_type = ? AND platform_user_id = ?)',
                $hashedUserId, ModPlatformId::STEAM, $steamId)
            ->querySingleModel();

        $now = new \DateTime('now');

        if ($player === null) {
            $player = new Player();
            $player->userId = $hashedUserId;
            $player->userName = "Steam User";
            $player->firstSeen = $now;
        }

        if ($steamUserName && $player->userName === "Steam User") {
            $player->userName = $steamUserName;
        }

        $player->type = PlayerType::PlayerModUser;
        $player->platformType = ModPlatformId::STEAM;
        $player->platformUserId = $steamId;
        $player->lastSeen = $now;
        $player->save();

        return $player;
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
        return ($this->type === PlayerType::DedicatedServer
            || $this->type === PlayerType::DedicatedServerGameLift
            || $this->type === PlayerType::DedicatedServerBeatTogether
            || $this->type === PlayerType::DedicatedServerBeatUpServer
            || $this->type === PlayerType::DedicatedServerBeatNetServer);
    }

    public function describeType(bool $shorten = false): string
    {
        if ($siteRole = $this->getSiteRole())
            return $siteRole->name;

        if ($this->type === PlayerType::DedicatedServerGameLift) {
            if (!$shorten && $gameLiftRegion = $this->tryGetGameLiftRegion()) {
                return "GameLift Server ({$gameLiftRegion})";
            } else {
                return "GameLift Server";
            }
        }

        $result = match ($this->type) {
            PlayerType::PlayerObserved => "Player",
            PlayerType::PlayerModUser => $shorten ? "Player" : "Player with Server Browser",
            PlayerType::DedicatedServer => "Dedicated Server",
            PlayerType::DedicatedServerBeatTogether => "BeatTogether Server",
            PlayerType::DedicatedServerBeatUpServer => "BeatUpServer",
            PlayerType::DedicatedServerBeatNetServer => "BeatNet",
            default => "Unknown",
        };
        if ($shorten) {
            $result = strtolower($result);
        }
        return $result;
    }


    // -----------------------------------------------------------------------------------------------------------------
    // Gamelift

    public function getIsGameLiftServer(): bool
    {
        return str_starts_with($this->userId, "arn:aws:gamelift:");
    }

    public function tryGetGameLiftInfo(): ?GameSessionArn
    {
        if (!$this->getIsGameLiftServer())
            return null;

        return GameSessionArnParser::tryParse($this->userId);
    }

    public function tryGetGameLiftRegion(): ?string
    {
        return $this->tryGetGameLiftInfo()?->fleetRegion;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // URLs

    public static function cleanUserIdForUrl(string $userId): string
    {
        $userId = str_replace("/", "_", $userId);
        return urlencode($userId);
    }

    public static function restoreUserIdFromUrl(string $urlUserId): string
    {
        $urlUserId = str_replace("_", "/", $urlUserId);
        return urldecode($urlUserId);
    }

    public function getUrlSafeUserId(): string
    {
        return self::cleanUserIdForUrl($this->userId);
    }

    public function getWebDetailUrl(): string
    {
        return "/player/{$this->getUrlSafeUserId()}";
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Face

    public function renderFaceHtml(string $size = 'sm'): string
    {
        $avatarData = $this->fetchAvatar();

        $skinColorId = $avatarData?->skinColorId ?? "Alien";
        $eyesId = $avatarData?->eyesId ?? "QuestionMark";

        $view = new View("bits/face.twig", true);
        $view->set('size', $size);
        $view->set('skinColorId', $skinColorId);
        $view->set('eyesId', $eyesId);
        $view->set('isDedicatedServer', $this->getIsDedicatedServer());
        $view->set('isCheater', $this->isCheater);
        return $view->render();
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Role

    public function getSiteRole(): ?SiteRole
    {
        if (!$this->siteRoleId)
            return null;

        return SiteRole::fetchCached($this->siteRoleId);
    }

    public function getIsSiteAdmin(): bool
    {
        return $this->getSiteRole()?->isAdmin ?? false;
    }
}