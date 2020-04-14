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

class lcMisc
{
    public static function appendPathPrefix($path)
    {
        if ($path) {
            if ((string)$path[0] == DS) {
                return $path;
            } else {
                $path = DS . $path;
            }
        }

        return $path;
    }

    public static function isPathAbsolute($path)
    {
        if ($path === false) {
            return false;
        }

        if (
            $path[0] == '/' ||
            $path[0] == '\\' ||
            (
                strlen($path) > 3 && ctype_alpha($path[0]) &&
                $path[1] == ':' && ($path[2] == '\\' || $path[2] == '/')
            )
        ) {
            return true;
        }

        return false;
    }

    public static function minifyJs($filename = null, $content = null)
    {
        if (!isset($filename) && !isset($content)) {
            throw new lcSystemException('You must pass a filename or js content');
        }

        // 3rdparty lib
        if (!class_exists('JSMin')) {
            throw new lcSystemException('JSMin is not available');
        }

        if (isset($filename)) {
            // file
            return JSMin::minify(file_get_contents($filename));
        } else {
            // content
            return JSMin::minify($content);
        }
    }
}