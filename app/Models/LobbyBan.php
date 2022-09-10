<?php

namespace app\Models;

use app\Models\Enums\LobbyBanType;
use SoftwarePunt\Instarecord\Model;

class LobbyBan extends Model
{
    public int $id;
    public LobbyBanType $type;
    public string $value;
    public ?\DateTime $expires = null;
    public ?string $comment = null;
    public \DateTime $created;

    // -----------------------------------------------------------------------------------------------------------------
    // Save / apply

    /**
     * Forces any games that match the ban to be marked as ended.
     * Does nothing if ban has expired.
     */
    public function apply(): void
    {
        if ($this->getHasExpired())
            return;

        switch ($this->type) {
            case LobbyBanType::ServerCode:
                $now = new \DateTime('now');
                HostedGame::query()
                    ->update()
                    ->set("ended_at = ?", $now)
                    ->where('server_code = ?', $this->value)
                    ->andWhere('ended_at IS NULL')
                    ->execute();
                break;
        }
    }

    public function save(): bool
    {
        if (empty($this->created))
            $this->created = new \DateTime('now');

        $saveOk = parent::save();

        if ($saveOk)
            $this->apply();

        return $saveOk;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Data utils

    public function describeBan(): string
    {
        return "{$this->type->describe()}: {$this->value}";
    }

    public function getHasExpired(): bool
    {
        if ($this->expires === null)
            return false;

        $now = new \DateTime('now');
        return $this->expires <= $now;
    }

    // -----------------------------------------------------------------------------------------------------------------
    // Checks

    /**
     * @var LobbyBan[]
     */
    private static ?array $cachedBans = null;

    public static function getCachedBans(): array
    {
        if (self::$cachedBans)
            return self::$cachedBans;

        $now = new \DateTime('now');

        self::$cachedBans = LobbyBan::query()
            ->where('expires IS NULL OR expires > ?', $now)
            ->queryAllModels();

        return self::$cachedBans;
    }

    public static function getServerCodeBan(string $serverCode): ?LobbyBan
    {
        $cachedBans = self::getCachedBans();

        foreach ($cachedBans as $ban)
            if ($ban->type === LobbyBanType::ServerCode && $ban->value === $serverCode)
                return $ban;

        return null;
    }
}