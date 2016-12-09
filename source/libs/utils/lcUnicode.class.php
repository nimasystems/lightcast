<?php

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com


*/

class lcUnicode
{
    public static $has_mb;

    public static function strlen($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strlen($string, $encoding) : strlen($string);
    }

    public static function ucfirst($string, $encoding = 'UTF8')
    {
        if (self::$has_mb) {
            $firstChar = $string[0];
            $then = mb_substr($string, 1, null, $encoding);
            return mb_strtoupper($firstChar, $encoding) . $then;
        } else {
            return ucfirst($string);
        }
    }

    public static function strtoupper($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtoupper($string, $encoding) : strtoupper($string);
    }

    public static function strtolower($string, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strtolower($string, $encoding) : strtolower($string);
    }

    public static function substr($str, $start, $length = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_substr($str, $start, $length, $encoding) : substr($str, $start, $length);
    }

    public static function strpos($haystack, $needle, $offset = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strpos($haystack, $needle, $offset, $encoding) : strpos($haystack, $needle, $offset);
    }

    public static function stripos($haystack, $needle, $offset = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_stripos($haystack, $needle, $offset, $encoding) : stripos($haystack, $needle, $offset);
    }

    public static function strripos($haystack, $needle, $offset = null, $encoding = 'UTF8')
    {
        return self::$has_mb ? mb_strripos($haystack, $needle, $offset, $encoding) : strripos($haystack, $needle, $offset);
    }
}

lcUnicode::$has_mb = function_exists('mb_strtolower');
