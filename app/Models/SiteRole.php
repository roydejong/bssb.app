<?php

namespace app\Models;

use SoftwarePunt\Instarecord\Model;

class SiteRole extends Model
{
    // -----------------------------------------------------------------------------------------------------------------
    // Columns

    public int $id;
    public string $name;
    public bool $isAdmin;

    // -----------------------------------------------------------------------------------------------------------------
    // Cached retrieval

    /**
     * @var SiteRole[]
     */
    private static array $roles = [];

    public static function fetchCached(int $roleId): ?SiteRole
    {
        if ($roleId <= 0)
            return null;

        if (!isset(self::$roles[$roleId]))
            self::$roles[$roleId] = SiteRole::fetch($roleId);

        return self::$roles[$roleId];
    }
}