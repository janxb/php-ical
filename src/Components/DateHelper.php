<?php


namespace App\Components;


class DateHelper
{
    public static function getDateFromDateTimeString(string $dateTimeString)
    {
        return substr($dateTimeString, 0, 8);
    }

    public static function getTimeFromDateTimeString(string $dateTimeString)
    {
        return rtrim(substr($dateTimeString, 10, 6), 'Z');
    }

    public static function getDateDifference(string $dateStringA, string $dateStringB)
    {
        return abs(
            self::getDateFromDateTimeString($dateStringB) - self::getDateFromDateTimeString($dateStringA)
        );
    }
}