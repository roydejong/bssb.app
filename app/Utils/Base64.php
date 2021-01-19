<?php

namespace app\Utils;

class Base64
{
    public static function getDataForImageFile(string $filePath): ?string
    {
        $validImageTypes = ["png", "jpg", "jpeg"];

        try {
            if (is_readable($filePath)) {
                $type = pathinfo($filePath, PATHINFO_EXTENSION);
                $data = file_get_contents($filePath);

                if ($type && $data && in_array($type, $validImageTypes, true)) {
                    $dataEncoded = base64_encode($data);
                    return "data:image/{$type};base64,{$dataEncoded}";
                }
            }
        } catch (\Exception $ex) { }

        return null;
    }
}