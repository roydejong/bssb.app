<?php

namespace app\BeatSaber;

class MultiplayerLobbyState
{
    public const None = 0;
    public const LobbySetup = 1;
    public const GameStarting = 2;
    public const GameRunning = 3;
    public const Error = 4;
}