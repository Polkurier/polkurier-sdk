<?php
namespace PolkurierWebServiceApi\Util;

/**
 * Class Arr
 * @package PolkurierWebServiceApi\Util
 *
 */
class Arr
{
    /**
     * @param array $arr
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public static function get(array $arr, $key, $default = null)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }
        return $default;
    }

}