<?php

namespace app\Models\Enums;

enum LobbyBanType : string
{
    case ServerCode = "server_code";

    public function describe(): string
    {
        return match ($this) {
            self::ServerCode => "Server code",
            default => $this->value
        };
    }
}
