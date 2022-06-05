<?php

use app\Models\Enums\PlayerType;
use app\Models\HostedGame;
use app\Models\HostedGamePlayer;
use app\Models\Player;

require_once "./bootstrap.php";

function xlog(string $txt): void {
    $nowFormatted = (new DateTime('now'))->format('Y-m-d H:i:s');
    echo "[$nowFormatted] {$txt}" . PHP_EOL;
}

function createOrUpdatePlayer(string $userId, string $userName, DateTime $firstSeen, DateTime $lastSeen, PlayerType $playerType, ?string $platformType, ?string $platformUserId): Player {
    $playerRecord = Player::query()
        ->where('user_id = ?', $userId)
        ->querySingleModel();

    $creating = false;

    if ($playerRecord === null) {
        $playerRecord = new Player();
        $playerRecord->userId = $userId;
        $playerRecord->userName = $userName;

        $creating = true;
    }

    if ($userId === Player::BeatTogetherUserId) {
        $playerRecord->type = PlayerType::DedicatedServerBeatTogether;
    } else if (str_starts_with($userId, "arn:aws:gamelift")) {
        $playerRecord->type = PlayerType::DedicatedServerGameLift;
    } else {
        if (!isset($playerRecord->type) || $playerRecord->type !== PlayerType::PlayerModUser) {
            $playerRecord->type = $playerType;
        }
    }

    if ($platformType && $platformType !== "unknown")
        $playerRecord->platformType = $platformType;

    if ($platformUserId)
        $playerRecord->platformUserId = $platformUserId;

    if (!isset($playerRecord->firstSeen) || $playerRecord->firstSeen > $firstSeen)
        $playerRecord->firstSeen = $firstSeen;

    if (!isset($playerRecord->lastSeen) || $playerRecord->lastSeen < $lastSeen)
        $playerRecord->lastSeen = $lastSeen;

    $playerRecord->save();

    if ($creating)
        xlog("Created player record #{$playerRecord->id} (userId={$userId}, userName={$userName}, type={$playerType->value})");
    else
        xlog("Updated player record #{$playerRecord->id} (userId={$userId}, userName={$userName}, type={$playerType->value})");

    return $playerRecord;
}

xlog("Player data migration script starting");

// Load all hosted games
xlog("Loading hosted game data...");

$hostedGames = HostedGame::query()
    ->select('id, owner_id, owner_name, first_seen, last_update, server_type, platform')
    ->orderBy('id ASC')
    ->queryAllRows();
$hostedGamesCount = count($hostedGames);

xlog("Processing {$hostedGamesCount} hosted games...");

foreach ($hostedGames as $gameRow) {
    $gameId = intval($gameRow['id']);
    $hostUserId = $gameRow['owner_id'];
    $hostUserName = $gameRow['owner_name'];
    $firstSeen = new DateTime($gameRow['first_seen']);
    $lastUpdate = new DateTime($gameRow['last_update']);
    $serverType = $gameRow['server_type'];
    $platform = $gameRow['platform'];

    $isPeerToPeer = $serverType === null || $serverType === HostedGame::SERVER_TYPE_PLAYER_HOST;
    $hostType = $isPeerToPeer ? PlayerType::PlayerModUser : PlayerType::DedicatedServer;

    // Seed host record
    createOrUpdatePlayer($hostUserId, $hostUserName, $firstSeen, $lastUpdate, $hostType, $platform, null);

    // Iterate linked hosted game players if any
    $hostedGamePlayers = HostedGamePlayer::query()
        ->select('user_id, user_name, is_host, is_announcer')
        ->where('hosted_game_id = ?', $gameId)
        ->orderBy('id ASC')
        ->queryAllRows();
    $hgpCount = count($hostedGamePlayers);

    if ($hgpCount > 0) {
        xlog("Processing {$hgpCount} linked player(s) for gameId={$gameId}...");

        foreach ($hostedGamePlayers as $playerRow) {
            $userId = $playerRow['user_id'];
            $userName = $playerRow['user_name'];
            $isHost = $playerRow['is_host'] == 1;
            $isAnnouncer = $playerRow['is_announcer'] == 1;

            if ($userId === $hostUserId)
                // Same as game host, no extra data to gain here
                continue;

            if ($isHost && !$isPeerToPeer)
                // Is non-p2p host, must be dedicated server - this is a fallback only
                $playerType = PlayerType::DedicatedServer;
            else if ($isAnnouncer)
                // Has announced to us, is mod user
                $playerType = PlayerType::PlayerModUser;
            else
                // Just a player observed in a game
                $playerType = PlayerType::PlayerObserved;

            // Seed player record
            createOrUpdatePlayer($userId, $userName, $lastUpdate, $lastUpdate, $playerType, null, null);
        }
    }

    unset($hostedGamePlayers);
}

xlog("All done!");
exit(0);