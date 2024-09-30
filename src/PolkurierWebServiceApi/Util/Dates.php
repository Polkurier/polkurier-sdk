<?php

namespace PolkurierWebServiceApi\Util;

use DateTime;
use DateTimeImmutable;
use Exception;

class Dates
{

    /**
     * @param mixed $date
     * @return DateTimeImmutable|null
     */
    public static function dateTimeOrNull($date)
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof DateTimeImmutable) {
            return $date;
        }

        if ($date instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($date);
        }

        try {
            if (is_numeric($date)) {
                $date = '@' . $date;
            }
            return new DateTimeImmutable($date);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param mixed $date
     * @return DateTimeImmutable|null
     */
    public static function ensureDateTime($date)
    {
        $dateTime = self::dateTimeOrNull($date);
        if ($dateTime === null) {
            return new DateTimeImmutable('@0');
        }
        return $dateTime;
    }

}
