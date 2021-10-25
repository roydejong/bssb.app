<?php

namespace app\BeatSaber;

use app\Common\CVersion;

final class GameVersionAliases
{
    /**
     * @param CVersion $gameVersion The game version to get aliases for.
     * @param bool $includeBaseVersion If set, include passed $gameVersion in the result array.
     * @param bool $recurse If true, recursively include aliased-on-aliased versions.
     * @return CVersion[] A list of any aliases, including $gameVersion if $includeBaseVersion is set.
     */
    public static function getAliasesFor(CVersion $gameVersion, bool $includeBaseVersion = true, bool $recurse = true): array
    {
        $results = [];

        foreach (self::$_aliases as $one => $two) {
            $versionCompareStr = $gameVersion->toString(3);
            if ($one === $versionCompareStr) {
                $results[] = new CVersion($two);
            }
            if ($two === $versionCompareStr) {
                $results[] = new CVersion($one);
            }
        }

        if ($recurse) {
            foreach ($results as $resultVersion) {
                $subAliases = self::getAliasesFor($resultVersion, includeBaseVersion: false, recurse: false);
                foreach ($subAliases as $subAlias) {
                    $results[] = $subAlias;
                }
            }
        }

        if ($includeBaseVersion) {
            $results[] = $gameVersion;
        }

        $results = array_unique($results);

        usort($results, function (CVersion $a, CVersion $b): int {
            if ($a->greaterThan($b)) return +1;
            if ($b->greaterThan($a)) return -1;
            return 0;
        });

        return $results;
    }

    private static array $_aliases = [
        "1.18.1" => "1.18.0",
        "1.18.2" => "1.18.0"
    ];
}