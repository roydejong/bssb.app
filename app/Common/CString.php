<?php

namespace app\Common;

final class CString
{
    public static function startsWith(string $string, string $startsWith): bool
    {
        if (empty($startsWith))
            return false;

        return substr($string, 0, strlen($startsWith)) === $startsWith;
    }

    public static function endsWith(string $string, string $endsWith): bool
    {
        if (empty($endsWith))
            return false;

        return substr($string, -strlen($endsWith)) === $endsWith;
    }

    public static function contains(string $string, string $contains): bool
    {
        if (empty($contains))
            return false;

        return strpos($string, $contains) !== false;
    }
}