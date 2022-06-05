<?php

namespace app\Models\Joins;

use app\Models\LevelHistoryPlayer;
use app\Models\Player;

class LevelHistoryPlayerWithPlayerDetails extends LevelHistoryPlayer
{
    public string $userId;
    public string $userName;
    public ?string $skinColorId;
    public ?string $eyesId;

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return LevelHistoryPlayerWithPlayerDetails[]
     */
    public static function fetchForLevelHistory(int $levelHistoryId): array
    {
        return LevelHistoryPlayerWithPlayerDetails::query()
            ->select('lhp.*, p.user_id, p.user_name, pav.skin_color_id, pav.eyes_id')
            ->from('level_history_players lhp')
            ->where('level_history_id = ?', $levelHistoryId)
            ->innerJoin('players p ON (p.id = lhp.player_id)')
            ->leftJoin('player_avatars pav ON (pav.player_id = p.id)')
            ->orderBy('placement IS NOT NULL DESC, placement ASC')
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