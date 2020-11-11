<?php

namespace app\HTTP\Responses;

use app\HTTP\Response;

class JsonResponse extends Response
{
    public function __construct($dataToSerialize)
    {
        $_fnProcessArray = function (array $arr) use (&$_fnProcessArray) {
            foreach ($arr as $key => &$value) {
                if ($value instanceof \JsonSerializable) {
                    $value = $value->jsonSerialize();
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format('r');
                } else if (is_array($value)) {
                    $value = $_fnProcessArray($value);
                }
            }
            return $arr;
        };

        $body = json_encode($_fnProcessArray($dataToSerialize));

        parent::__construct(200, $body, "application/json");
    }
}