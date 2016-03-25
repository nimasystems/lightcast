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

define('NL', "\n");

define('L_TIME_MINUTE', 60);
define('L_TIME_HOUR', 3600);
define('L_TIME_DAY', 86400);
define('L_TIME_WEEK', 604800);
define('L_TIME_MONTH', 2678400);
define('L_TIME_YEAR', 32140800);

/**
 * Helper method to translate a string within a specific context
 *
 * @param $string
 * @param $context_type
 * @param $context_name
 * @return bool|string
 */
function __($string, $context_type = null, $context_name = null)
{
    return lcApp::translateInContext($string, $context_type, $context_name);
}

function e($param, $return = false)
{
    if (!$return) {
        var_dump($param);
        return null;
    }

    if (!is_string($param)) {
        $param =
            '<pre>' .
            print_r($param, true) .
            '</pre>';
    }

    if ($return) {
        return $param;
    }

    echo $param;
    return null;
}

function ee($param, $return = false)
{
    $param =
        '<pre>' .
        print_r($param, true) .
        '</pre>';

    if ($return) {
        return $param;
    }

    echo $param;
    return null;
}

function vd($val)
{
    if (is_array($val) OR is_object($val)) {
        echo
            '<pre>' .
            print_r($val, true) .
            '</pre>';
    } else {
        var_dump($val);
    }
}

// fast string lowercase
function low($string)
{
    return lcUnicode::strtolower($string);
}

// fast string uppercase
function up($string)
{
    return strtoupper($string);
}

// fast str_replace
function r($search, $replace, $subject)
{
    return str_replace($search, $replace, $subject);
}

function ri($search, $replace, $subject)
{
    return str_ireplace($search, $replace, $subject);
}

// fast explode
function ex($sep, $arr)
{
    return explode($sep, $arr);
}

function gc($objname)
{
    return get_class_methods($objname);
}

/*
 * @deprecated
 * Helper method to silence PHPSniffer unused function parameters error
 * wherever necessary
 */
function fnothing()
{
    //
}