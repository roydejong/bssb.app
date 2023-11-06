<?php

namespace app\Utils;

class LocalOrReservedHostDetect
{
    public static function isLocalOrReserved(string $inputValue): bool
    {
        if ($inputValue === "localhost" || str_ends_with($inputValue, ".local")) {
            return true;
        }

        $isIpv4 = filter_var($inputValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $isIpv6 = filter_var($inputValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        if ($isIpv4 || $isIpv6) {
            $filterValidate = filter_var($inputValue, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE);
            // $filterValidate will be false if the IP is local or reserved (validation failed)
            return $filterValidate === false;
        }

        // Not an IP and not a blacklisted hostname
        return false;
    }
}