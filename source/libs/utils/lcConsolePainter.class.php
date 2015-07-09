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
 * @changed $Id: lcConsolePainter.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcConsolePainter
{
    protected static $styles = array(
        'error' => array('bg' => 'red', 'fg' => 'white', 'bold' => true, 'blink' => true),
        'info' => array('fg' => 'green', 'bold' => true),
        'information' => array('fg' => 'green', 'bold' => true),
        'comment' => array('fg' => 'yellow', 'blink' => true),
        'question' => array('bg' => 'cyan', 'fg' => 'black',),
    );

    protected static $options = array('bold' => 1, 'underscore' => 4, 'blink' => 5, 'reverse' => 7, 'conceal' => 8);
    protected static $foreground = array('black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'white' => 37);
    protected static $background = array('black' => 40, 'red' => 41, 'green' => 42, 'yellow' => 43, 'blue' => 44, 'magenta' => 45, 'cyan' => 46, 'white' => 4);

    public static function formatColoredConsoleText($text, $foreground_color = 'white', $background_color = 'black')
    {
        if (!$text || !$foreground_color || !$background_color) {
            return $text;
        }

        $codes = array();

        if (isset(self::$foreground[$foreground_color])) {
            $codes[] = self::$foreground[$foreground_color];
        }

        if (isset(self::$background[$background_color])) {
            $codes[] = self::$background[$background_color];
        }

        $ret = "\033[" . implode(';', $codes) . 'm' . $text . "\033[0m";
        return $ret;
    }

    public static function formatConsoleText($text, $style)
    {
        if (!isset(self::$styles[$style])) {
            return $text;
        }

        $codes = array();

        if (isset(self::$styles[$style]['fg'])) {
            $codes[] = self::$foreground[self::$styles[$style]['fg']];
        }

        if (isset(self::$styles[$style]['bg'])) {
            $codes[] = self::$foreground[self::$styles[$style]['bg']];
        }

        foreach (self::$options as $option => $key) {
            if (isset(self::$styles[$style][$option])) {
                $codes[] = $key;
            }

            unset($option, $key);
        }

        $ret = "\033[" . implode(';', $codes) . 'm' . $text . "\033[0m";
        return $ret;
    }
}
