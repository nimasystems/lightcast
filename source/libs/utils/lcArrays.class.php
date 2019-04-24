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

class lcArrays
{
    public static function filterNum(array $array = null)
    {
        if (!$array) {
            return $array;
        }

        $ret = array_filter(array_filter($array, 'is_numeric'));

        return $ret;
    }

    public static function arrayContainsArrayValues(array $needle, array $haystack)
    {
        return count(array_intersect($haystack, $needle)) == count($needle);
    }

    public static function arrayFilterDeep(array $input, $null_only = false)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterDeep($value, $null_only);
            }
        }

        $ret = null;

        if ($null_only) {
            $ret = array_filter($input, function ($a) {
                return (is_array($a) && $a) || (!is_array($a) && (is_bool($a) || (is_string($a) && strlen(trim($a))) || is_numeric($a)));
            });
        } else {
            $ret = array_filter($input);
        }

        return $ret;
    }

    /*
     * Original author: symfony framework
    * All copyrights reserved to symfony
    */
    public static function arrayDeepMerge()
    {
        switch (func_num_args()) {
            case 0:
                return false;
            case 1:
                return func_get_arg(0);
            case 2:
                $args = func_get_args();
                $args[2] = [];

                if (is_array($args[0]) && is_array($args[1])) {
                    $m = array_unique(array_merge(array_keys($args[0]), array_keys($args[1])));

                    foreach ($m as $key) {
                        $isKey0 = array_key_exists($key, $args[0]);
                        $isKey1 = array_key_exists($key, $args[1]);

                        if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key])) {
                            $args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
                        } else if ($isKey0 && $isKey1) {
                            $args[2][$key] = $args[1][$key];
                        } else if (!$isKey1) {
                            $args[2][$key] = $args[0][$key];
                        } else if (!$isKey0) {
                            $args[2][$key] = $args[1][$key];
                        }

                        unset($key);
                    }

                    unset($m);
                    return $args[2];
                } else {
                    return $args[1];
                }
            default:
                $args = func_get_args();
                $args[1] = lcArrays::arrayDeepMerge($args[0], $args[1]);
                array_shift($args);
                return call_user_func_array(['lcArrays', 'arrayDeepMerge'], $args);
                break;
        }
    }

    public static function removeAssociativeArrayKeys(array $arr)
    {
        return array_values($arr);

        /*
         if (!$arr)
         {
        return false;
        }

        $n = array();

        foreach($arr as $k => $v)
        {
        $n[] = $v;
        }

        return $n;*/
    }

    public static function mergeRecursiveDistinct()
    {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        if (!is_array($base)) {
            $base = empty($base) ? [] : [$base];
        }

        foreach ($arrays as $append) {
            if (!is_array($append)) {
                $append = array_filter([$append]);
            }

            foreach ($append as $key => $value) {
                if (!array_key_exists($key, $base) and !is_numeric($key)) {
                    $base[$key] = $append[$key];
                    continue;
                }

                if (isset($base[$key]) && (is_array($value) || is_array($base[$key]))) {
                    $base[$key] = lcArrays::mergeRecursiveDistinct($base[$key], $append[$key]);
                } else if (is_numeric($key)) {
                    if (!in_array($value, $base)) {
                        $base[] = $value;
                    }
                } else {
                    $base[$key] = $value;
                }

                unset($key, $value);
            }

            unset($append);
        }

        return $base;
    }

    public static function arrayToString(/** @noinspection PhpUnusedParameterInspection */
        array $arr = null)
    {
        // TODO - Bug - memory exhaust
        return '';

        /** @noinspection PhpUnreachableStatementInspection */
        if (!count($arr)) {
            return '';
        }

        $out = '';

        $a = [];

        foreach ($arr as $key => $val) {
            $a[] = $key . '=' . $val;
            unset($key, $val);
        }
        $out = implode(', ', $a);

        unset($a);

        return $out;
    }
}