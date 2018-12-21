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

class lcDateValidator extends lcValidator
{
    public function validate($data)
    {
        if (!is_string($data)) {
            return false;
        }

        // check if we have time in the date also - remove it
        $tmp = array_filter(explode(' ', $data));

        $str = null;

        if (count($tmp)) {
            $str = $tmp[0];
        }

        if (!$str) {
            return false;
        }

        $date_only = isset($this->options['date_only']) && $this->options['date_only'];
        $time_only = isset($this->options['time_only']) && $this->options['time_only'];

        $match = [];

        $validate_date = !$time_only;
        $validate_time = !$date_only && count($tmp) >= 2;

        $ret = true;

        if ($validate_date) {
            if (preg_match('/^([\d]+){1,4}[-|\/]([\d]+){1,2}[-|\/]([\d]+){1,4}/', $str, $match)) {
                // check if we have year at the first position and swap it with the last
                if ($match[1] > 31) {
                    $tmp = $match[3];
                    $match[3] = $match[1];
                    $match[1] = $tmp;
                }

                // check if the first position is not a month - swap it with the second
                if ($match[1] > 12) {
                    $str = $match[2] . '/' . $match[1] . '/' . $match[3];
                } else {
                    $str = $match[1] . '/' . $match[2] . '/' . $match[3];
                }
            }

            if (!$stamp = strtotime($str)) {
                return false;
            }

            $m = date('m', $stamp);
            $d = date('d', $stamp);
            $y = date('Y', $stamp);

            $ret = (bool)checkdate($m, $d, $y);
        }

        if (!$ret) {
            return false;
        }

        if ($validate_time) {
            $ret = preg_match('/([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?/', $str, $match);
        }

        return $ret;
    }
}
