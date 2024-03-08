<?php

namespace app\External\Models;

class SteamPlayerSummary
{
    public ?string $steamid = null;
    public ?string $personaname = null;
    public ?string $profileurl = null;
    public ?string $avatar = null;
    public ?string $avatarmedium = null;
    public ?string $avatarfull = null;
    public ?string $personastate = null;
    public ?string $communityvisibilitystate = null;
    public ?string $profilestate = null;
    public ?string $lastlogoff = null;
    public ?string $commentpermission = null;
    public ?string $realname = null;
    public ?string $primaryclanid = null;
    public ?string $timecreated = null;
    public ?string $personastateflags = null;
    public ?string $loccountrycode = null;
    public ?string $locstatecode = null;

    public function __construct(array $data)
    {
        foreach ($this as $key => $_) {
            $this->$key = $data[$key] ?? null;
        }
    }
}