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

class lcRand
{
    public static function randomFloat($max)
    {
        $v = rand() / getrandmax();
        $v = $v * $max;
        return $v;
    }

    public static function randomGeoLat()
    {
        return (double)(rand(-90, 90) . '.' . rand(0, 10000000));
    }

    public static function randomGeoLon()
    {
        return (double)(rand(-180, 180) . '.' . rand(0, 10000000));
    }

    public static function randomIp()
    {
        return rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
    }

    public static function randomWords($len)
    {
        $str_out = null;

        while (strlen($str_out) < $len) {
            $str_out .= lcStrings::randomString(rand(2, 20), true) . ' ';
        }

        $str_out = trim($str_out);

        return $str_out;
    }

    public static function randomDateTimeStr()
    {
        $t = mktime(rand(0, 24), rand(0, 59), rand(0, 59), rand(1, 12), rand(1, 28), rand(2005, 2014));
        $t = date('Y-m-d H:i:s', $t);
        return $t;
    }

    public static function randomDateStr()
    {
        $t = mktime(rand(0, 24), rand(0, 59), rand(0, 59), rand(1, 12), rand(1, 28), rand(2005, 2014));
        $t = date('Y-m-d', $t);
        return $t;
    }

    public static function randomTimestamp()
    {
        return mktime(rand(0, 24), rand(0, 59), rand(0, 59), rand(1, 12), rand(1, 28), rand(2005, 2014));
    }

    public static function randomArrayVal(array $arr)
    {
        $r = rand(0, count($arr) - 1);
        return $arr[$r];
    }
}
