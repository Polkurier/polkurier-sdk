<?php

namespace PolkurierWebServiceApi\Util;

use InvalidArgumentException;

class Arr
{

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(array $arr, $key, $default = null)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }
        return $default;
    }

    public static function assertInt(array $arr)
    {
        foreach ($arr as $value) {
            if (!is_int($value)) {
                throw new InvalidArgumentException("Collection items must be of int type.");
            }
        }
    }

    public static function assertStrings(array $arr)
    {
        foreach ($arr as $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException("Collection items must be of string type.");
            }
        }
    }

    public static function assertInstancesOf(array $arr, $className)
    {
        foreach ($arr as $value) {
            if ($value instanceof $className === false) {
                throw new InvalidArgumentException("Collection items must be of $className class type.");
            }
        }
    }

}
