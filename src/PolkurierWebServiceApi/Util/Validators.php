<?php

namespace PolkurierWebServiceApi\Util;

use PolkurierWebServiceApi\Exception\ErrorException;

class Validators
{

    /**
     * @param string $hour
     * @return bool
     */
    public static function validateHourFormat($hour)
    {
        return preg_match('/\d{2}:\d{2}/', $hour);
    }

    /**
     * @param string $hour
     * @return bool
     * @throws ErrorException
     */
    public static function validateHourFormatAndThrowOnInvalid($hour)
    {
        if (!self::validateHourFormat($hour)) {
            throw new ErrorException('Błędny format godzin: oczekiwany hh:mm, otrzymany' . $hour);
        }
        return true;
    }

}
