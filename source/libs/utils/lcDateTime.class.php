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

class lcDateTime
{
    public static function strftime($format, $timestamp = 0)
    {
        if (!function_exists('iconv')) {
            throw new lcSystemException('PHP module iconv is not available');
        }

        return strftime($format, $timestamp);
    }

    public static function utcDateTime($timestamp = false)
    {
        $d = new \DateTime('now', new \DateTimeZone('UTC'));
        return $timestamp ? $d->getTimestamp() : $d;
    }

    public static function getMicrotimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        $res = ((float)$usec + (float)$sec);

        return $res;
    }

    public static function hoursToSeconds($hours)
    {
        return self::hoursToMinutes($hours) * 60;
    }

    public static function hoursToMinutes($hours)
    {
        $minutes = 0;

        if (strpos($hours, ':') !== false) {
            // Split hours and minutes.
            list($hours, $minutes) = explode(':', $hours);
        }

        return (int)$hours * 60 + $minutes;
    }

    public static function minutesToHours($minutes)
    {
        $hours = (int)($minutes / 60);
        $minutes -= $hours * 60;
        return sprintf("%d:%02.0f", $hours, $minutes);
    }

    public static function timeDiff($firstTime, $lastTime)
    {
        $firstTime = strtotime($firstTime);
        $lastTime = strtotime($lastTime);
        $timeDiff = $lastTime - $firstTime;
        return $timeDiff;
    }

    public static function humanizeDateDifference($now, $otherDate = null, $offset = null, $config = [])
    {
        if ($otherDate != null) {
            $offset = $now - $otherDate;
        }

        if ($offset != null) {
            //$deltaS = $offset % 60;
            $offset /= 60;
            $deltaM = $offset % 60;
            $offset /= 60;
            $deltaH = $offset % 24;
            $offset /= 24;
            $deltaD = ($offset > 1) ? ceil($offset) : $offset;
        } else {
            return null;
        }

        $localization_strings = (isset($config['localization']) ? $config['localization'] : []);

        if ($deltaD > 1) {
            if ($deltaD > 365) {
                $years = ceil($deltaD / 365);
                if ($years == 1) {
                    return (isset($localization_strings['last_year']) ? $localization_strings['last_year'] : 'last year');
                } else {
                    return (isset($localization_strings['years_ago']) ? sprintf($localization_strings['years_ago'], $years) : sprintf('%s years ago', $years));
                }
            }

            $t = (isset($localization_strings['days_ago']) ? sprintf($localization_strings['days_ago'], $deltaD) : sprintf('%s days ago', $deltaD));

            if ($deltaD > 6) {
                $tt = strtotime($t);
                $y = date('Y', $tt);
                $yy = date('Y');
                return date((isset($config['default_date_format']) ? $config['default_date_format'] : ($y == $yy ? 'd M' : 'd M Y')), strtotime($t));
            }

            return $t;
        }

        if ($deltaD == 1) {
            return (isset($localization_strings['yesterday']) ? $localization_strings['yesterday'] : 'yesterday');
        }

        if ($deltaH == 1) {
            return (isset($localization_strings['last_hour']) ? $localization_strings['last_hour'] : 'last hour');
        }

        if ($deltaM == 1) {
            return (isset($localization_strings['last_minute']) ? $localization_strings['last_minute'] : 'last minute');
        }

        if ($deltaH > 0) {
            $t = (isset($localization_strings['hours_ago']) ? sprintf($localization_strings['hours_ago'], $deltaH) : sprintf('%s hours ago', $deltaH));
            return $t;
        }

        if ($deltaM > 0) {
            $t = (isset($localization_strings['minutes_ago']) ? sprintf($localization_strings['minutes_ago'], $deltaM) : sprintf('%s minutes ago', $deltaM));
            return $t;
        } else {
            $t = (isset($localization_strings['few_seconds_ago']) ? $localization_strings['few_seconds_ago'] : 'a few seconds ago');
            return $t;
        }
    }

    public static function secondsToTime($inputSeconds, $format = null, array $options = null)
    {
        if (!$inputSeconds) {
            return null;
        }

        $append_leading_zero = (isset($options['append_leading_zero']) && $options['append_leading_zero']) ||
            !isset($options['append_leading_zero']);

        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        $ns = $append_leading_zero ? '2' : '1';
        $mod = '%0' . $ns . 'd';

        // return the final array
        $obj = [
            'd' => sprintf($mod, $days),
            'h' => sprintf($mod, $hours),
            'm' => sprintf($mod, $minutes),
            's' => sprintf($mod, $seconds),
        ];

        if ($format) {
            $tstr = [];

            $formata = str_split($format);

            foreach ($formata as $v) {
                $tstr[] = isset($obj[$v]) ? $obj[$v] : $v;
                unset($v);
            }

            $tstr = array_filter($tstr);
            $tstr = implode('', $tstr);

            return $tstr;
        } else {
            return $obj;
        }
    }

    public static function convertPhpDateToJsFormat($datetime_format)
    {
        return trim(preg_replace_callback("/\\(.?)/", function ($y) {
            return self::convertPhpDatePartToMomentjsFormat($y) . ' ';
        }, $datetime_format));
    }

    public static function convertStrftimeToJsFormat($datetime_format)
    {
        return trim(preg_replace_callback("/%(.?)/", function ($y) {
            return self::convertStrtftimePartToMomentjsFormat($y);
        }, $datetime_format));
    }

    private function convertPhpDatePartToMomentjsFormat($part)
    {
        $part = is_array($part) ? (count($part) ? $part[0] : null) : $part;

        if (!$part) {
            return null;
        }

        switch ($part) {
            case "D":
                return "ddd";
            case "l":
                return "dddd";
            case "M":
                return "MMM";
            case "F":
                return "MMMM";
            case "j":
                return "D";
            case "m":
                return "MM";
            case "A":
                return "A";
            case "a":
                return "a";
            case "s":
                return "ss";
            case "i":
                return "mm";
            case "H":
                return "HH";
            case "g":
                return "h";
            case "h":
                return "hh";
            case "w":
                return "d";
            case "W":
                return "ww";
            case "y":
                return "YY";
            case "o":
            case "Y":
                return "YYYY";
            case "O":
                return "ZZ";
            case "z":
                return "DDD";
            case "d":
                return "DD";
            case "n":
                return "M";
            case "G":
                return "H";
            case "e":
                return "zz";
            default:
                return null;
        }
    }

    private static function convertStrtftimePartToMomentjsFormat($part)
    {
        $part = is_array($part) ? (count($part) ? $part[0] : null) : $part;

        if (!$part) {
            return null;
        }

        switch ($part) {
            case "%a":
                return "ddd";
            case "%A":
                return "dddd";
            case "%h":
            case "%b":
                return "MMM";
            case "%B":
                return "MMMM";
            case "%c":
                return "LLLL";
            case "%d":
                return "D";
            case "%j":
                return "DDDD";
            case "%e":
                return "Do";
            case "%m":
                return "MM";
            case "%p":
                return "A";
            case "%P":
                return "a";
            case "%S":
                return "ss";
            case "%M":
                return "mm";
            case "%H":
                return "HH";
            case "%I":
                return "hh";
            case "%w":
                return "d";
            case "%W":
            case "%U":
                return "ww";
            case '%x':
                //return 'YYYY-MM-DD';
                return "LL";
            case "%X":
                //return 'HH:mm:ss';
                return "LT";
            case "%g":
            case "%y":
                return "YY";
            case "%G":
            case "%Y":
                return "YYYY";
            case "%z":
                return "ZZ";
            case "%Z":
                return "z";
            case "%f":
                return "SSS";
            default:
                return null;
        }
    }
}
