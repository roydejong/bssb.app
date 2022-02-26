<?php

namespace app\Session;

use app\HTTP\Request;
use app\HTTP\Response;
use app\Models\Player;

class Session
{
    private const CookieName = "bssb";

    private static ?Session $instance = null;

    private bool $startRequested = false;
    private bool $didStart = false;
    private array $dataSet = [];

    // -----------------------------------------------------------------------------------------------------------------
    // Singleton

    public static function getInstance(): Session
    {
        if (!self::$instance)
            self::$instance = new Session();

        return self::$instance;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Request/response

    public function onRequest(Request $request): void
    {
        if (!isset($request->cookies[self::CookieName]))
            // Session has not started, and we don't do so unless requested
            return;

        $this->startSession();
    }

    public function beforeResponse(Response $response): void
    {
        if ($this->startRequested && !$this->didStart)
            $this->startSession();

        if ($this->didStart)
            foreach ($this->dataSet as $key => $value)
                $_SESSION[$key] = $value;
    }

    private function startSession(): void
    {
        $this->startRequested = true;

        session_start([
            'name' => self::CookieName,
            'cookie_path' => '/',
            'cookie_httponly' => true
        ]);

        $this->dataSet = $_SESSION;
        $this->didStart = true;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // API

    public function forceStart(): void
    {
        $this->startRequested = true;
    }

    public function set(string $key, mixed $value): void
    {
        $this->forceStart();
        $this->dataSet[$key] = $value;
    }

    public function get(string $key, mixed $defaultValue = null): mixed
    {
        return $this->dataSet[$key] ?? $defaultValue;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Steam auth

    public function setSteamAuth(?string $steamUserId): void
    {
        if ($steamUserId) {
            $this->set('steam_authed', true);
            $this->set('steam_uid', $steamUserId);
        } else {
            $this->set('steam_authed', false);
            $this->set('steam_uid', null);
        }
    }

    public function getIsSteamAuthed(): bool
    {
        return $this->get('steam_authed') && !empty($this->get('steam_uid'));
    }

    public function getSteamUserId64(): ?string
    {
        if ($this->getIsSteamAuthed())
            return $this->get('steam_uid');
        return null;
    }

    public function setPlayerInfo(Player $player): void
    {
        $this->set('player_id', $player->id);
    }

    private ?Player $cachedPlayer = null;

    public function getPlayer(): ?Player
    {
        if (!$this->getIsSteamAuthed())
            return null;

        $playerId = intval($this->get('player_id'));

        if (!$playerId)
            return null;

        if ($this->cachedPlayer && $this->cachedPlayer->id == $playerId)
            return $this->cachedPlayer;

        $player = Player::fetch($playerId);

        if (!$player)
            return null;

        $this->cachedPlayer = $player;
        return $player;
    }
}