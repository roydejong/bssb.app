<?php

namespace app\Models\Joins;

use app\Models\Enums\PlayerType;
use app\Models\Player;
use SoftwarePunt\Instarecord\Database\QueryPaginator;
use SoftwarePunt\Instarecord\Models\IReadOnlyModel;

/**
 * Join of Player, PlayerAvatar, PlayerFriend.
 */
class PlayerRelationshipJoin extends Player implements IReadOnlyModel
{
    public ?string $skinColorId = null;
    public ?string $eyesId = null;
    public ?int $friendshipId = null;
    public bool $hasFriendship = false;
    public bool $isFriendshipPending = false;
    public ?int $friendRequesterId = null;

    public static function querySearch(Player $basePlayer, ?string $searchText): QueryPaginator
    {
        // TODO Proper indexed text search

        $query = self::query()
            ->select('p.*, pa.skin_color_id, pa.eyes_id, pf.id AS friendship_id, (pf.id > 0) AS has_friendship, pf.is_pending AS is_friendship_pending, pf.player_one_id AS friend_requester_id')
            ->from('players p')
            ->leftJoin('player_avatars pa ON (pa.player_id = p.id)')
            ->leftJoin('player_friends pf ON ((pf.player_one_id = ? OR pf.player_two_id = ?) AND (pf.player_one_id = p.id OR pf.player_two_id = p.id))',
                $basePlayer->id, $basePlayer->id)
            ->andWhere('type IN (?)', [PlayerType::PlayerModUser->value,
                PlayerType::PlayerObserved->value])
            ->andWhere('p.id != ?', $basePlayer->id)
            ->orderBy('has_friendship DESC, pa.id > 0 DESC, p.type = "player_mod_user" DESC, user_name ASC');

        if ($searchText) {
            $query->andWhere('user_name LIKE ?', "%{$searchText}%");
        }

        return $query->paginate();
    }

    public static function queryFriendships(Player $basePlayer): QueryPaginator
    {
        $query = self::query()
            ->select('p.*, pa.skin_color_id, pa.eyes_id, pf.id AS friendship_id, (pf.id > 0) AS has_friendship, pf.is_pending AS is_friendship_pending, pf.player_one_id AS friend_requester_id')
            ->from('player_friends pf')
            ->innerJoin('players p ON ((pf.player_one_id = ? AND p.id = pf.player_two_id) OR (pf.player_two_id = ? AND p.id = pf.player_one_id))',
                $basePlayer->id, $basePlayer->id)
            ->where('pf.player_one_id = ? OR pf.player_two_id = ?',
                $basePlayer->id, $basePlayer->id)
            ->andWhere('pf.is_pending = 0 OR pf.player_two_id = ?',
                $basePlayer->id)
            ->leftJoin('player_avatars pa ON (pa.player_id = p.id)')
            ->orderBy('pa.id > 0 DESC, p.type = "player_mod_user" DESC, user_name ASC');

        return $query->paginate();
    }
}