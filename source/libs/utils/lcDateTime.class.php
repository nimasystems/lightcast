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

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcDateTime.class.php 1550 2014-07-13 10:54:12Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1550 $
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

    public static function getMicrotimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        $res = ((float)$usec + (float)$sec);

        return $res;
    }

    public static function hoursToMinutes($hours)
    {
        $minutes = 0;
        if (strpos($hours, ':') !== false) {
            // Split hours and minutes.
            list($hours, $minutes) = explode(':', $hours);
        }
        return $hours * 60 + $minutes;
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

    public static function humanizeDateDifference($now, $otherDate = null, $offset = null, $config = array())
    {
        if ($otherDate != null) {
            $offset = $now - $otherDate;
        }

        if ($offset != null) {
            $deltaS = $offset % 60;
            $offset /= 60;
            $deltaM = $offset % 60;
            $offset /= 60;
            $deltaH = $offset % 24;
            $offset /= 24;
            $deltaD = ($offset > 1) ? ceil($offset) : $offset;
        } else {
            return null;
        }

        $localization_strings = (isset($config['localization']) ? $config['localization'] : array());

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

    public static function secondsToTime($inputSeconds)
    {
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

        // return the final array
        $obj = array(
            'd' => (int)$days,
            'h' => (int)$hours,
            'm' => (int)$minutes,
            's' => (int)$seconds,
        );
        return $obj;
    }
}

?>