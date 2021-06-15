<?php

namespace app\Utils;

class TimeAgo
{
    public static function format(\DateTime $then, \DateTime $now = null)
    {
        if (!$now)
            $now = new \DateTime('now');

        $diffSecs = $now->getTimestamp() - $then->getTimestamp();

        if (abs($diffSecs) <= 1)
            return 'now';

        if (abs($diffSecs) < 15)
            return 'a few seconds ago';

        $isFuturistic = $diffSecs < 0;
        $diffSecs = abs($diffSecs);

        $units = [
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];

        $text = "";
        $depth = 0;

        foreach ($units as $unitSeconds => $unitName) {
            if ($diffSecs < $unitSeconds)
                if ($depth === 0)
                    continue;
                else
                    break;

            $unitParts = floor($diffSecs / $unitSeconds);
            $diffSecs -= $unitParts * $unitSeconds;

            if ($unitParts != 1)
                $unitName .= 's'; // seconds rather than second, etc

            if ($depth > 0)
                $text .= ", "; // combine e.g. 1 year, 4 months ago

            $text .= "{$unitParts} {$unitName}";
            $depth++;

            if ($depth >= 2)
                break; // don't combine more than 2 parts
        }

        if ($isFuturistic) {
            return "in {$text}";
        }

        return "{$text} ago";
    }
}