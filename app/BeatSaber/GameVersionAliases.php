<?php

namespace app\BeatSaber;

use app\Common\CVersion;

final class GameVersionAliases
{
    /**
     * @param CVersion $gameVersion The game version to get aliases for.
     * @param bool $includeBaseVersion If set, include passed $gameVersion in the result array.
     * @return CVersion[] A list of any aliases, including $gameVersion if $includeBaseVersion is set.
     */
    public static function getAliasesFor(CVersion $gameVersion, bool $includeBaseVersion = true): array
    {
        $results = [];

        if ($includeBaseVersion) {
            $results[] = $gameVersion;
        }

        foreach (self::$_aliases as $one => $two) {
            $versionCompareStr = $gameVersion->toString(3);
            if ($one === $versionCompareStr) {
                $results[] = new CVersion($two);
            }
            if ($two === $versionCompareStr) {
                $results[] = new CVersion($one);
            }
        }

        usort($results, function (CVersion $a, CVersion $b): int {
            if ($a->greaterThan($b)) return +1;
            if ($b->greaterThan($a)) return -1;
            return 0;
        });

        return $results;
    }

    private static array $_aliases = [
        "1.18.1" => "1.18.0"
    ];
}