<?php

namespace app\Models\Joins;

use app\Models\HostedGamePlayer;
use app\Models\Player;

class HostedGamePlayerWithPlayerDetails extends HostedGamePlayer
{
    public string $userId;
    public string $userName;
    public ?string $skinColorId;
    public ?string $eyesId;

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return HostedGamePlayerWithPlayerDetails[]
     */
    public static function queryAllForHostedGame(int $hostedGameId): array
    {
        return HostedGamePlayerWithPlayerDetails::query()
            ->select('hgp.*, p.*, pav.skin_color_id, pav.eyes_id, p.id AS id')
            ->from('hosted_game_players hgp')
            ->where('hosted_game_id = ?', $hostedGameId)
            ->innerJoin('players p ON (p.user_id = hgp.user_id)')
            ->leftJoin('player_avatars pav ON (pav.player_id = p.id)')
            ->orderBy('sort_index ASC, hgp.id ASC')
            ->queryAllModels();
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function getUrlSafeUserId(): string
    {
        return Player::cleanUserIdForUrl($this->userId);
    }

    public function getProfileUrl(): string
    {
        return "/player/{$this->getUrlSafeUserId()}";
    }
}