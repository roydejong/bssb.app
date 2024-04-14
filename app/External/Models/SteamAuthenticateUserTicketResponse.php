<?php

namespace app\External\Models;

class SteamAuthenticateUserTicketResponse
{
    public ?string $result;
    public ?string $steamid;
    public ?string $ownersteamid;
    public ?bool $vacbanned;
    public ?bool $publisherbanned;

    public function __construct(array $data)
    {
        $this->result = $data['result'] ?? null;
        $this->steamid = $data['steamid'] ?? null;
        $this->ownersteamid = $data['ownersteamid'] ?? null;
        $this->vacbanned = $data['vacbanned'] ?? null;
        $this->publisherbanned = $data['publisherbanned'] ?? null;
    }

    public function isValid(): bool
    {
        return $this->result === 'OK' && !empty($this->steamid) && !empty($this->ownersteamid);
    }
}