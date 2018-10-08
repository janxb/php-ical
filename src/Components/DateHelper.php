<?php


namespace App\Components;


use DateTime;

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

    public static function getDateDifference(string $dateStringA, string $dateStringB, string $format)
    {
        $dateA = DateTime::createFromFormat($format, $dateStringA);
        $dateB = DateTime::createFromFormat($format, $dateStringB);
        return abs($dateA->diff($dateB)->format("%a"));
    }
}