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
 * XHProf - Prepend file
 * @package FileCategory
 * @subpackage FileSubcategory
 * @changed $Id: lcDebugger.class.php 1482 2013-12-12 05:54:34Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1482 $
 */
class lcDebugger
{

    private static $instance;

    public static function getInstance()
    {
        if (self::$instance) {
            return self::$instance;
        }

        self::$instance = new lcDebugger();
        return self::$instance;
    }

    public function backtrace($traces_to_ignore = 1)
    {
        $fulltrace = debug_backtrace();

        $traces = array();
        $i = 0;

        foreach ($fulltrace as $trace) {
            if ($traces_to_ignore && $i < $traces_to_ignore) {
                ++$i;
                continue;
            }

            $traces[] = $this->showTextTrace($trace, $i);
            ++$i;
            unset($trace);
        }

        $ret = implode("\n", $traces);
        return $ret;
    }

    private function showTextTrace($_trace, $_i)
    {
        $htmldoc = ' #' . $_i . ' ';

        if (array_key_exists('file', $_trace)) {
            $htmldoc .= $_trace['file'];
        }

        if (array_key_exists('line', $_trace)) {
            $htmldoc .= '(' . $_trace["line"] . '): ';
        }

        if (array_key_exists('class', $_trace) && array_key_exists('type', $_trace)) {
            $htmldoc .= $_trace['class'] . $_trace['type'];
        }

        if (array_key_exists('function', $_trace)) {
            $htmldoc .= $_trace["function"] . '(';

            if (array_key_exists('args', $_trace)) {
                if (count($_trace['args']) > 0) {
                    $prep = array();

                    foreach ($_trace['args'] as $arg) {
                        $type = gettype($arg);
                        $value = $arg;
                        $str = '';

                        if ($type == 'boolean') {
                            if ($value) {
                                $str .= 'true';
                            } else {
                                $str .= 'false';
                            }
                        } elseif ($type == 'integer' || $type == 'double') {
                            if (settype($value, 'string')) {
                                $str .= $value;
                            } else {
                                if ($type == 'integer') {
                                    $str .= '? integer ?';
                                } else {
                                    $str .= '? double or float ?';
                                }
                            }
                        } elseif ($type == 'string') {
                            $str .= "'" . (strlen($value) > 50 ? substr($value, 0, 50) : $value) . "'";
                        } elseif ($type == 'array') {
                            $str .= 'Array';
                        } elseif ($type == 'object') {
                            $str .= 'Object';
                        } elseif ($type == 'resource') {
                            $str .= 'Resource';
                        } elseif ($type == 'NULL') {
                            $str .= 'null';
                        } elseif ($type == 'unknown type') {
                            $str .= '? unknown type ?';
                        }

                        $prep[] = $str;

                        unset($type);
                        unset($value);
                        unset($arg);
                    }

                    if ($prep) {
                        $htmldoc .= implode(', ', $prep);
                    }
                }
            }

            $htmldoc .= ')';
        }

        return $htmldoc;
    }
}