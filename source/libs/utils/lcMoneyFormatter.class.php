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

class lcMoneyFormatter
{
    public static function thousandsSeparated($number)
    {
        return number_format($number, 0, ',', ' ');
    }

    public static function standartOutput($value, array $options = null)
    {
        if (!$value) {
            return $value;
        }

        $format = isset($options['format']) && $options['format'] ? $options['format'] : null;
        $decimals = isset($options['decimals']) && $options['decimals'] ? $options['decimals'] : 2;
        $decimal_point = isset($options['decimal_point']) && $options['decimal_point'] ?
            $options['decimal_point'] : '.';
        $thousands_separator = isset($options['thousands_separator']) && $options['thousands_separator'] ?
            $options['thousands_separator'] : '';
        //$value = round($value, $decimals);

        $nf = number_format($value, $decimals, $decimal_point, $thousands_separator);
        return $format ? sprintf($format, $nf) : $nf;
    }
}
