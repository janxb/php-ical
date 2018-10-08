<?php

namespace App\Components;

class StringHelper
{
    static function stringStartsWith($string, $subString, $caseSensitive = true)
    {
        if ($caseSensitive === false) {
            $string = mb_strtolower($string);
            $subString = mb_strtolower($subString);
        }
        if (mb_substr($string, 0, mb_strlen($subString)) == $subString) {
            return true;
        } else {
            return false;
        }
    }

    static function stringEndsWith($string, $subString, $caseSensitive = true)
    {
        if ($caseSensitive === false) {
            $string = mb_strtolower($string);
            $subString = mb_strtolower($subString);
        }
        $strlen = strlen($string);
        $subStringLength = strlen($subString);
        if ($subStringLength > $strlen) {
            return false;
        }
        return substr_compare($string, $subString, $strlen - $subStringLength, $subStringLength) === 0;
    }

    static function stringContains($haystack, $needle, $caseSensitive = true)
    {
        if ($caseSensitive === false) {
            $haystack = mb_strtolower($haystack);
            $needle = mb_strtolower($needle);
        }
        if (mb_substr_count($haystack, $needle) > 0) {
            return true;
        } else {
            return false;
        }
    }
}