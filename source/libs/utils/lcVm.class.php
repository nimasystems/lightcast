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
 * @changed $Id: lcVm.class.php 1570 2015-02-06 09:56:05Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1570 $
 */
class lcVm
{
    // file_put_contents emulation
    public static function file_put_contents($fileName, $data)
    {
        if (!function_exists('file_put_contents')) {
            if (is_array($data)) {
                $data = join('', $data);
            }

            $res = @fopen($fileName, 'w+b');

            if ($res) {
                $write = @fwrite($res, $data);

                if ($write === false) {
                    return false;
                } else {
                    @fclose($res);
                    return $write;
                }
            }
            return false;
        } else {
            $written = file_put_contents($fileName, $data);

            return $written;
        }
    }

    public static function json_encode($data, $unescaped_unicode = false)
    {
        if (PHP_GREATER_EQUAL_54) {
            return json_encode($data, ($unescaped_unicode ? JSON_UNESCAPED_UNICODE : 0));
        } else {
            $data = json_encode($data);

            if ($unescaped_unicode) {
                // <= 5.3 support for JSON_UNESCAPED_UNICODE
                $str = preg_replace_callback(
                    '/\\\\u([0-9a-f]{4})/i',
                    function ($matches) {
                        $sym = mb_convert_encoding(
                            pack('H*', $matches[1]),
                            'UTF-8',
                            'UTF-16'
                        );
                        return $sym;
                    },
                    $data
                );
            } else {
                $str = $data;
            }

            return $str;
        }
    }

    // php log event
    public static function error_log($message, $message_type, $destination = null, $extra_headers = null)
    {
        return error_log($message, $message_type, $destination, $extra_headers);
    }

    public static function property_exists($className, $varName)
    {
        return property_exists($className, $varName);
    }

    public static function date_default_timezone_set($timezone)
    {
        return date_default_timezone_set($timezone);
    }

    public static function date_default_timezone_get()
    {
        return date_default_timezone_get();
    }

    public static function memory_get_peak_usage($emalloc)
    {
        if (!function_exists('memory_get_peak_usage')) {
            return null;
        }

        return memory_get_peak_usage($emalloc);
    }

    public static function memory_get_usage()
    {
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            if (substr(PHP_OS, 0, 3) == 'WIN') {
                $output = array();
                exec('tasklist /FI "PID eq ' . getmypid() . '" /FO LIST', $output);

                return preg_replace('/[\D]/', '', $output[5]) * 1024;
            }
        } else {
            $pid = getmypid();
            exec("ps -eo%mem,rss,pid | grep $pid", $output);
            $output = explode("  ", $output[0]);

            return $output[1] * 1024;
        }

        return null;
    }

    public static function php_check_syntax($filename, &$error_message = null)
    {
        // PHP 5 <= 5.0.4 - has an integrated method for this - php_check_syntax
        if (function_exists('php_check_syntax')) {
            $res = php_check_syntax($filename, isset($error_message) ? $error_message : null);
        } else {
            // shell_exec
            $res = false;
            $cmd = 'php -l ' . escapeshellarg($filename);
            $error_message = lcSys::execCmd($cmd, $res);

            $res = ($res == '0') ? true : false;
        }

        return $res;
    }
}
