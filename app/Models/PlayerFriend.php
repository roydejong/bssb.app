<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class PlayerFriend extends Model
{
    public int $id;
    public int $playerOneId;
    public int $playerTwoId;
    public bool $isPending = true;
    public \DateTime $requestedAt;
    public ?\DateTime $acceptedAt = null;

    // -----------------------------------------------------------------------------------------------------------------

    public function getCanAccept(Player $player): bool
    {
        if (!$this->isPending)
            return false;

        return $this->playerTwoId === $player->id;
    }

    public function getCanDelete(Player $player): bool
    {
        return $this->playerOneId === $player->id || $this->playerTwoId === $player->id;
    }

    public function tryAccept(Player $acceptingPlayer): bool
    {
        if (!$this->getCanAccept($acceptingPlayer))
            return false;

        $this->acceptedAt = new \DateTime('now');
        $this->isPending = false;
        return $this->save();
    }

    public function tryDelete(Player $deletingPlayer): bool
    {
        if (!$this->getCanDelete($deletingPlayer))
            return false;

        $this->delete();
        return true;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public static function fetchRelationship(Player $basePlayer, Player $otherPlayer): ?PlayerFriend
    {
        return PlayerFriend::query()
            ->where('(player_one_id = ? OR player_two_id = ?) AND (player_one_id = ? OR player_two_id = ?)',
                $basePlayer->id, $basePlayer->id, $otherPlayer->id, $otherPlayer->id)
            ->querySingleModel();
    }

    public static function sendFriendRequest(Player $basePlayer, Player $otherPlayer): ?PlayerFriend
    {
        if ($basePlayer->id === $otherPlayer->id)
            // Cannot befriend self
            return null;

        if ($otherPlayer->getIsDedicatedServer())
            // Cannot befriend servers
            return null;

        $previousRelationship = self::fetchRelationship($basePlayer, $otherPlayer);

        if ($previousRelationship)
            // Already have relationship
            return null;

        // Create pending friendship
        $request = new PlayerFriend();
        $request->playerOneId = $basePlayer->id;
        $request->playerTwoId = $otherPlayer->id;
        $request->isPending = true;
        $request->requestedAt = new \DateTime('now');

        if ($request->trySave())
            return $request;

        return null;
    }
}