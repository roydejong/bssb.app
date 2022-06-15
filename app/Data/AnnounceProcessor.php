<?php

namespace app\Data;

use app\BeatSaber\GameplayModifiers;
use app\BeatSaber\LevelDifficulty;
use app\BeatSaber\LevelId;
use app\BeatSaber\MasterServer;
use app\BeatSaber\ModClientInfo;
use app\BeatSaber\ModPlatformId;
use app\BeatSaber\MultiplayerLobbyState;
use app\Common\CVersion;
use app\Common\IPEndPoint;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\LevelHistory;
use app\Models\LevelHistoryPlayer;
use app\Models\LevelRecord;
use app\Models\MasterServerInfo;

final class AnnounceProcessor
{
    private ModClientInfo $clientInfo;
    private array $data;
    private ?LevelRecord $tempLevelData;
    private ?GameplayModifiers $gameplayModifiers;
    private ?string $sessionGameId;
    private bool $legacyLevelStarted;
    private ?LevelHistory $serverLevel;
    private ?string $userMessage;

    // -----------------------------------------------------------------------------------------------------------------
    // Public API

    public function __construct(ModClientInfo $clientInfo, array $data)
    {
        $this->clientInfo = $clientInfo;
        $this->data = [];
        foreach ($data as $key => $value) {
            if (empty($key) || $value === null || $value === "")
                continue;

            $this->data[strtolower($key)] = $value;
        }
        $this->tempLevelData = null;
        $this->gameplayModifiers = null;
        $this->sessionGameId = null;
        $this->legacyLevelStarted = false;
        $this->userMessage = null;
    }

    public function process(): ?HostedGame
    {
        $game = $this->syncGameData();

        $this->syncLevelData($game);
        $this->syncPlayerData($game);
        $this->syncServerData($game);

        if ($game->getIsGameLiftServer() && $game->getIsQuickplay()) {
            $this->userMessage = "Other players can't join Official Quick Play lobbies right now, sorry";
        } else {
            $this->userMessage = null;
        }

        return $game;
    }

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Private API

    private function has(string $key): bool
    {
        return isset($this->data[strtolower($key)]);
    }

    private function get(string $key, mixed $defaultValue = null): mixed
    {
        return $this->data[strtolower($key)] ?? $defaultValue;
    }

    private function getString(string $key, ?string $defaultValue = null): ?string
    {
        $val = $this->get($key, $defaultValue);

        if ($val === $defaultValue)
            return $val;

        if (!is_string($val))
            $val = strval($val);

        $val = trim($val);

        if (empty($val))
            return $defaultValue;

        return $val;
    }

    private function getInt(string $key, int $defaultValue = 0): int
    {
        return intval($this->get($key, $defaultValue));
    }

    private function getIntNullable(string $key): ?int
    {
        $val = $this->get($key, null);

        if ($val === null)
            return null;

        return intval($val);
    }

    private function getBool(string $key): bool
    {
        $val = $this->get($key, null);
        return $val === true || $val === 1 || $val === "true" || $val === "1";
    }

    private function getCVersion(string $key): ?CVersion
    {
        $strVal = $this->getString($key, null);
        return $strVal ? new CVersion($strVal) : null;
    }

    private function getVersionText(string $key): ?string
    {
        return $this->getCVersion($key)?->toString(3);
    }

    private function getEndpoint(string $key): ?IPEndPoint
    {
        $strVal = $this->getString($key);
        return $strVal ? IPEndPoint::tryParse($strVal) : null;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Game data sync

    private function syncGameData(): HostedGame
    {
        $ownerId = $this->getString('OwnerId');
        $ownerName = $this->getString('OwnerName', "");
        $hostSecret = $this->getString('HostSecret');
        $serverCode = $this->getString('ServerCode') ?? "";
        $serverType = $this->getString('ServerType');
        $managerId = $this->getString('ManagerId');
        $playerCount = $this->getInt('PlayerCount');
        $playerLimit = $this->getInt('PlayerLimit');
        $isModded = $this->getBool('IsModded');
        $lobbyState = $this->getInt('lobbyState', MultiplayerLobbyState::None);
        $modPlatform = ModPlatformId::normalize($this->getString('Platform'));
        $endpoint = $this->getEndpoint('Endpoint');
        $gameName = $this->getString('GameName', "Untitled Beat Game");

        if (empty($ownerId))
            throw new AnnounceException("Announce must always include OwnerId and ServerCode");

        /**
         * @var $game HostedGame|null
         */

        if ($hostSecret) {
            // Default: identify lobbies uniquely by Host ID + Host Secret
            $game = HostedGame::query()
                ->where('owner_id = ?', $ownerId)
                ->andWhere('host_secret = ?', $hostSecret)
                ->querySingleModel();
        } else {
            // Fallback, for backwards compatibility with p2p games: identify by Host ID + an explicitly missing secret
            $game = HostedGame::query()
                ->where('owner_id = ?', $ownerId)
                ->andWhere('host_secret IS NULL')
                ->querySingleModel();
        }

        if (!$game) {
            // Game not found: create new base record
            $game = new HostedGame();
            $game->ownerId = $ownerId;
            $game->hostSecret = $hostSecret;
        }

        $wasInLobby = $game->getIsInLobby();

        // Set or update core game data
        $game->ownerName = $ownerName;
        $game->serverCode = $serverCode;
        $game->serverType = $serverType;
        $game->managerId = $managerId;
        $game->playerCount = $this->clampPlayerCount($playerCount, false);
        $game->playerLimit = $this->clampPlayerLimit($playerLimit);

        if (empty($game->lobbyState) || $lobbyState !== MultiplayerLobbyState::None)
            $game->lobbyState = $lobbyState;

        $game->platform = $modPlatform;
        $game->endpoint = $endpoint;

        $this->setMasterServerData($game);
        $this->setClientInfo($game);
        $this->setModInfo($game);
        $this->setLevelInfo($game);

        $game->isModded = ($isModded || $game->mpCoreVersion || $game->mpExVersion)
            && $this->validateGameCanBeModded($game);

        $this->setGameName($game, $gameName);

        // With all data set, check validations
        if (!$this->validateMasterServer($game->masterServerHost))
            throw new AnnounceException("Announce rejected: master server is blacklisted");

        if (!$this->validateServerCode($game->serverCode, $game->getIsQuickplay()))
            throw new AnnounceException("Announce rejected: must include valid ServerCode");

        if (!$this->validateHostSecret($game->hostSecret, $game->getIsQuickplay()))
            throw new AnnounceException("Announce rejected: must include valid HostSecret");

        // Infer "level started" event from current state
        $this->legacyLevelStarted = $wasInLobby && $game->getIsPlayingLevel();

        // Insert or update game record
        $now = new \DateTime('now');

        if (empty($game->firstSeen))
            $game->firstSeen = $now;

        $game->lastUpdate = $now;

        if ($game->getIsUninteresting())
            $game->endedAt = $now; // force end; we don't want to show this game
        else
            $game->endedAt = null;

        if ($game->save())
            return $game;
        else
            throw new AnnounceException("Internal write error");
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Level data sync

    private function syncLevelData(HostedGame $game): void
    {
        $this->serverLevel = null;

        if (!$this->tempLevelData || !$game->getIsPlayingLevel(mustBeInGameplayScene: true))
            return;

        $this->tempLevelData = LevelRecord::syncFromAnnounce($this->tempLevelData->levelId,
            $this->tempLevelData->songName, $this->tempLevelData->songAuthor);

        if ($this->legacyLevelStarted) {
            // Legacy "level start" detection (state change)
            $this->tempLevelData->incrementPlayStat();
        }

        if ($this->sessionGameId) {
            // Modern level history
            $this->serverLevel = LevelHistory::query()
                ->where('session_game_id = ?', $this->sessionGameId)
                ->querySingleModel();

            $now = new \DateTime('now');

            if (!$this->serverLevel) {
                $this->serverLevel = new LevelHistory();
                $this->serverLevel->sessionGameId = $this->sessionGameId;
                $this->serverLevel->hostedGameId = $game->id;
                $this->serverLevel->startedAt = $now;
                $this->serverLevel->endedAt = null;
            }

            if ($this->serverLevel->hostedGameId === $game->id && $this->serverLevel->endedAt === null) {
                $this->serverLevel->levelRecordId = $this->tempLevelData->id;
                $this->serverLevel->difficulty = $game->difficulty;
                $this->serverLevel->characteristic = $game->characteristic;
                $this->serverLevel->modifiers = $this->gameplayModifiers;
            }

            $this->serverLevel->save();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Player data sync

    /**
     * @throws \SoftwarePunt\Instarecord\Database\DatabaseException
     * @throws \SoftwarePunt\Instarecord\Database\QueryBuilderException
     */
    public function syncPlayerData(HostedGame $game): void
    {
        $announcePlayers = $this->get('Players');

        if (empty($announcePlayers) || !is_array($announcePlayers))
            // Players were not included in announce, consider this a no-op
            return;

        // Index existing player data by sort index (player slot)
        $playersPerIndex = [];
        $pendingPlayerIndexes = [];

        /**
         * @var $dbPlayers HostedGamePlayer[]
         */
        $dbPlayers = HostedGamePlayer::query()
            ->where('hosted_game_id = ?', $game->id)
            ->queryAllModels();

        foreach ($dbPlayers as $dbPlayer) {
            $playersPerIndex[$dbPlayer->sortIndex] = $dbPlayer;
            $pendingPlayerIndexes[$dbPlayer->sortIndex] = true;
        }

        // Iterate announced players; add or replace player records
        foreach ($announcePlayers as $player) {
            // BssbPlayer
            $userId = $player['UserId'] ?? null;
            $userName = $player['UserName'] ?? null;
            $platformType = $player['PlatformType'] ?? null;
            $platformUserId = $player['PlatformUserId'] ?? null;
            $avatarData = $player['AvatarData'] ?? null;
            // BssbServerPlayer
            $sortIndex = intval($player['SortIndex'] ?? -999);
            $isMe = intval($player['IsMe'] ?? 0) === 1;
            $isHost = intval($player['IsHost'] ?? 0) === 1;
            $isAnnouncer = $isMe || intval($player['IsAnnouncer'] ?? 0) === 1;
            $latency = floatval($player['Latency'] ?? 0);

            if ($sortIndex === -1) {
                // -1 slot is used for dedicated servers only
                if (!$isHost) {
                    continue;
                }
                if (!$userName) {
                    $userName = "Dedicated Server";
                }
            }

            if ($sortIndex < -1 || !$userId) {
                // Invalid entry, missing minimum data
                continue;
            }

            $dbPlayer = $playersPerIndex[$sortIndex] ?? new HostedGamePlayer();
            $dbPlayer->hostedGameId = $game->id;
            $dbPlayer->sortIndex = $sortIndex;
            $dbPlayer->userId = $userId;
            $dbPlayer->userName = $userName;
            $dbPlayer->isHost = $isHost;
            $dbPlayer->isAnnouncer = $isAnnouncer;
            $dbPlayer->latency = $latency;
            $dbPlayer->isConnected = true;
            $dbPlayer->save();

            $playersPerIndex[$sortIndex] = $dbPlayer;
            unset($pendingPlayerIndexes[$sortIndex]);

            // Sync profile data
            $playerProfile = $dbPlayer->syncProfileData($platformType, $platformUserId, $avatarData);

            // Sync history data
            if ($this->serverLevel && $dbPlayer->isConnected && $dbPlayer->sortIndex >= 0) {
                $historyPlayer = LevelHistoryPlayer::query()
                    ->where('level_history_id = ?', $this->serverLevel->id)
                    ->andWhere('player_id = ?', $playerProfile->id)
                    ->querySingleModel();

                if (!$historyPlayer) {
                    $historyPlayer = new LevelHistoryPlayer();
                    $historyPlayer->levelHistoryId = $this->serverLevel->id;
                    $historyPlayer->playerId = $playerProfile->id;
                    $historyPlayer->save();
                }
            }
        }

        // Mark disconnected players
        if (!empty($pendingPlayerIndexes)) {
            HostedGamePlayer::query()
                ->update()
                ->set('is_connected = 0')
                ->where('hosted_game_id = ?', $game->id)
                ->andWhere('sort_index IN (?)', array_keys($pendingPlayerIndexes))
                ->execute();
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Server data sync

    public function syncServerData(HostedGame $game): void
    {
        MasterServerInfo::syncFromGame($game);
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Setter helpers

    private function setGameName(HostedGame &$game, string $preferredGameName): void
    {
        if ($game->serverType === HostedGame::SERVER_TYPE_NORMAL_QUICKPLAY) {
            $difficultyName = LevelDifficulty::describe($game->difficulty);
            if ($game->getIsOfficial()) {
                $game->gameName = "Official Quick Play - {$difficultyName}";
            } else {
                $game->gameName = "Unofficial Quick Play - {$difficultyName}";
            }
        } else if ($game->serverType === HostedGame::SERVER_TYPE_BEATTOGETHER_QUICKPLAY) {
            $difficultyName = LevelDifficulty::describe($game->difficulty);
            $game->gameName = "BeatTogether Quick Play - {$difficultyName}";
        } else {
            $game->gameName = $preferredGameName;
        }
    }

    private function setMasterServerData(HostedGame &$game): void
    {
        $masterServerEp = $this->getString('MasterServerEp');

        if ($masterServerEp) {
            // Modern announce format: MasterServerEp
            $epParts = explode(':', $masterServerEp, 2);
            $game->masterServerHost = $epParts[0] ?? null;
            $game->masterServerPort = isset($epParts[1]) ? intval($epParts[1]) : null;
        } else {
            // Legacy announce format: separate fields
            $game->masterServerHost = $this->getString('MasterServerHost');
            $game->masterServerPort = $this->getInt('MasterServerPort', 2328);
        }

        // Automatically provide platform type if we can infer it from master server
        // Only relevant to non-GameLift official servers servers (< 1.19.1)
        if ($game->masterServerHost) {
            if ($game->masterServerHost === MasterServer::OFFICIAL_HOSTNAME_OCULUS) {
                $game->platform = ModPlatformId::OCULUS;
            } else if ($game->masterServerHost === MasterServer::OFFICIAL_HOSTNAME_STEAM) {
                $game->platform = ModPlatformId::STEAM;
            }
        }

        // If GameLift host, set master server info manually for display in stats
        if ($game->getIsGameLiftServer() && empty($game->masterServerHost)) {
            $game->masterServerHost = "graph.oculus.com";
        }

        // Status URL (as of BSSB 1.0+)
        $game->masterStatusUrl = $this->getString('MasterStatusUrl');
    }

    private function setClientInfo(HostedGame &$game): void
    {
        $game->modName = $this->clientInfo->modName;
        $game->modVersion = $this->clientInfo->assemblyVersion;
        $game->gameVersion = $this->clientInfo->beatSaberVersion;
    }

    private function setModInfo(HostedGame &$game): void
    {
        if (!$game->getIsOfficial() || $game->getIsPeerToPeer()) {
            $game->mpCoreVersion = $this->getVersionText('MpCoreVersion');
            $game->mpExVersion = $this->getVersionText('MpExVersion');
        } else {
            $game->mpCoreVersion = null;
            $game->mpExVersion = null;
        }
    }

    private function setLevelInfo(HostedGame &$game): void
    {
        $levelData = $this->get('Level');

        if ($levelData && is_array($levelData)) {
            // Modern announce format: Level object
            $levelId = $levelData['LevelId'] ?? null;
            $songName = $levelData['SongName'] ?? null;
            $songSubName = $levelData['SongSubName'] ?? null;
            $songAuthorName = $levelData['SongAuthorName'] ?? null;
            $levelAuthorName = $levelData['LevelAuthorName'] ?? null;
            $difficulty = isset($levelData['Difficulty']) ? intval($levelData['Difficulty']) : null;
            $characteristic = $levelData['Characteristic'] ?? null;
            $sessionGameId = $levelData['SessionGameId'] ?? null;

            if (isset($levelData['Modifiers']) && is_array($levelData['Modifiers'])) {
                $gameplayModifiers = GameplayModifiers::fromArray($levelData['Modifiers']);
            } else {
                $gameplayModifiers = null;
            }
        } else {
            // Legacy announce format: separate fields
            $levelId = $this->getString('LevelId');
            $songName = $this->getString('SongName');
            $songSubName = null; // not supported yet <v1
            $songAuthorName = $this->getString('SongAuthor');
            $levelAuthorName = null; // not supported yet <v1
            $difficulty = $this->getIntNullable('Difficulty');
            $characteristic = null; // not supported yet <v1
            $sessionGameId = null; // not supported yet <v1
            $gameplayModifiers = null; // not supported yet <v1
        }

        if ($difficulty === null && $game->getIsQuickplay()) {
            // Quick Play lobbies announced with "null" difficulty are actually "All" difficulty
            $difficulty = LevelDifficulty::All;
        }

        if ($levelId) {
            $levelId = LevelId::cleanLevelHash($levelId);

            $game->levelId = $levelId;
            $game->songName = $songName;
            $game->songAuthor = $songAuthorName;
            $game->characteristic = $characteristic;

            $this->tempLevelData = new LevelRecord();
            $this->tempLevelData->levelId = $levelId;
            $this->tempLevelData->songName = $songName;
            $this->tempLevelData->songSubName = $songSubName;
            $this->tempLevelData->songAuthor = $songAuthorName;
            $this->tempLevelData->levelAuthor = $levelAuthorName;

            $this->gameplayModifiers = $gameplayModifiers;
            $this->sessionGameId = $sessionGameId;
        } else {
            $this->tempLevelData = null;
            $this->gameplayModifiers = null;
            $this->sessionGameId = null;
        }

        if ($levelId || $game->getIsQuickplay()) {
            $game->difficulty = $difficulty;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Format helpers

    private function clampPlayerCount(int $playerCount, bool $serverCanBeEmpty = false): int
    {
        if ($playerCount < 0)
            $playerCount = 0;

        if (!$serverCanBeEmpty && $playerCount === 0)
            $playerCount = 1;

        if ($playerCount > 128)
            $playerCount = 128;

        return $playerCount;
    }

    private function clampPlayerLimit(int $playerLimit): int
    {
        if ($playerLimit <= 0)
            $playerLimit = 5;

        if ($playerLimit > 128)
            $playerLimit = 128;

        return $playerLimit;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Validation helpers

    private function validateServerCode(?string $serverCode, bool $isQuickPlay): bool
    {
        if ($isQuickPlay && empty($serverCode))
            // Official servers have with empty server codes for Quick Play
            // BeatTogether Quick Play servers do have a server code anyway which is fine
            return true;

        // Normally speaking, we want a 5-character alphanumeric server code
        // Exception: BeatUpServer may use < 5 length server codes
        return !empty($serverCode) && strlen($serverCode) <= 5 && ctype_alnum($serverCode);
    }

    private function validateMasterServer(?string $masterServerHost): bool
    {
        global $bssbConfig;

        if ($masterServerHost && in_array($masterServerHost, $bssbConfig['master_server_blacklist'])) {
            // This master server is blacklisted
            return false;
        }

        return true;
    }

    private function validateGameCanBeModded(HostedGame $game): bool
    {
        $isOfficial = $game->getIsOfficial();

        if ($isOfficial && $game->getIsQuickplay()) {
            // Official + Quickplay has always used dedicated servers
            // Official dedicated cannot be modded anymore
            return false;
        }

        $supportsP2P = $this->clientInfo->beatSaberVersion->lessThan(new CVersion("1.16.3"));

        if (!$supportsP2P && $game->getIsOfficial()) {
            // As of 1.16.3, P2P servers are deprecated; all official games are on dedicated servers
            // Official dedicated cannot be modded anymore
            return false;
        }

        // Newer games on modded multiplayer servers and older P2P games can be modded
        return true;
    }

    private function validateHostSecret(?string $hostSecret, bool $isQuickPlay): bool
    {
        if ($isQuickPlay && empty($hostSecret))
            // Quick Play servers *always* require a host secret
            return false;

        $isModernClient = $this->clientInfo->beatSaberVersion->greaterThanOrEquals(new CVersion("1.19.1"));

        if ($isModernClient && empty($hostSecret))
            // Modern servers (arbitrarily as of 1.19.1) are all dedicated and should have a host secret
            return false;

        // Legacy fallback: older P2P servers can be without host secrets; older versions of the browser never send it
        return true;
    }
}