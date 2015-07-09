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
 * @changed $Id: lcFiles.class.php 1552 2014-08-01 07:13:50Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1552 $
 */

if (!function_exists('mime_content_type')) {
    function mime_content_type($filename)
    {
        if (!function_exists('finfo_open')) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;
    }
}

class lcFiles
{
    const DEFAULT_DIR_MODE = 0755;
    const DEFAULT_FILE_MODE = 0755;

    public static function getFile($filename)
    {
        try {
            $fdata = file_get_contents($filename);
        } catch (Exception $e) {
            throw new lcIOException($e->getMessage(), null, $e);
        }

        return $fdata;
    }

    public static function putFile($filename, $contents)
    {
        try {
            if (lcVm::file_put_contents($filename, $contents) === false) {
                throw new lcIOException('Cannot put file: ' . $filename);
            }
        } catch (Exception $e) {
            throw new lcIOException($e->getMessage(), null, $e);
        }

        return true;
    }

    public static function deleteFilesByGlob($glob_criteria)
    {
        if ($t = glob($glob_criteria)) {
            foreach ($t as $ff) {
                lcFiles::rm($ff);

                unset($ff);
            }

            unset($t);
        }

        return true;
    }

    public static function splitFileName($filename)
    {
        $filename = basename($filename);

        # if no extension at all - add one
        if (strpos($filename, '.') === false) {
            $filename .= '.';
        }

        $lastd = strrpos($filename, '.');
        $ext = substr($filename, $lastd, strlen($filename));
        $fname = substr($filename, 0, $lastd);

        $ext = ($ext != '.') ? $ext : null;

        $ret =
            array(
                'name' => $fname,
                'ext' => $ext
            );

        return $ret;
    }

    public static function rm($filename)
    {
        if (!file_exists($filename)) {
            return true;
        }

        if (!@unlink($filename)) {
            throw new lcIOException('Cannot remove file: ' . $filename);
        }

        return true;
    }

    public static function fixFileExt($ext)
    {
        if (!$ext) {
            return false;
        }

        if (substr($ext, 0, 1) == '.') {
            return substr($ext, 1, strlen($ext));
        } else {
            return $ext;
        }
    }

    // returns the dot with the extension (e.g: .jpg)!
    public static function getFileExt($filename)
    {
        if (!$tmp = self::splitFileName($filename)) {
            return false;
        }

        return $tmp['ext'];
    }

    public static function getMimetype($filename)
    {
        $mimetype = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            unset($finfo);
        }

        // if it didn't work out - retry with the integrated one
        $mimetype = $mimetype ? $mimetype : mime_content_type($filename);

        // strip out any additional data returned
        if (strstr($mimetype, ';')) {
            $mimetype = array_filter(explode(';', $mimetype));
            $mimetype = $mimetype[0];
        }

        return $mimetype;
    }

    public static function isReadable($filename)
    {
        if (!$filename || !file_exists($filename) || !is_readable($filename) || !is_file($filename)) {
            return false;
        }

        return true;
    }

    public static function getFileCountInDir($dir)
    {
        return count(glob($dir . "*"));
    }

    public static function is_writable($path)
    {
        //will work in despite of Windows ACLs bug
        //NOTE: use a trailing slash for folders!!!
        //see http://bugs.php.net/bug.php?id=27609
        //see http://bugs.php.net/bug.php?id=30931

        if ($path{strlen($path) - 1} == DS) {
            // recursively return a temporary file path
            return self::is_writable($path . uniqid(mt_rand()) . '.tmp');
        } elseif (is_dir($path)) {
            return self::is_writable($path . DS . uniqid(mt_rand()) . '.tmp');
        }

        // check tmp file for read/write capabilities
        $rm = file_exists($path);
        $f = @fopen($path, 'a');

        if ($f === false) {
            return false;
        }

        fclose($f);

        if (!$rm) {
            unlink($path);
        }

        return true;
    }

    public static function formatFilesize($file_size)
    {
        return lcSys::formatObjectSize($file_size);
    }

    public static function exists($filename)
    {
        return file_exists($filename);
    }

    public static function fixFileEnding($filename)
    {
        if (substr($filename, strlen($filename) - 1, strlen($filename)) == '/') {
            return substr($filename, 0, strlen($filename) - 1);
        }

        return $filename;
    }

    public static function copy($file_from, $file_to)
    {
        if (!copy($file_from, $file_to)) {
            throw new lcIOException('Cannot copy file: ' . $file_from . ' to: ' . $file_to);
        }

        return true;
    }

    public static function move($file_from, $file_to)
    {
        if (!rename($file_from, $file_to)) {
            throw new lcIOException('Cannot move file: ' . $file_from . ' to: ' . $file_to);
        }

        return true;
    }

    public static function globMove($glob_pattern, $target_dir, $mk_target = true)
    {
        $f = glob($glob_pattern, GLOB_BRACE);

        if (!$f) {
            return false;
        }

        if ($mk_target) {
            lcDirs::mkdirRecursive($target_dir);
        }

        foreach ($f as $filename) {
            $b = basename($filename);
            $nf = $target_dir . DS . $b;

            rename($filename, $nf);

            unset($filename);
        }

        return true;
    }

    public static function globRecursive($dir, $glob_pattern, $glob_options = GLOB_BRACE)
    {
        $ret = (array)glob($dir . DS . $glob_pattern, $glob_options);

        $subdirs = lcDirs::getSubDirsOfDir($dir);

        if ($subdirs) {
            foreach ($subdirs as $subdir) {
                $ret = array_merge(
                    (array)self::globRecursive($dir . DS . $subdir, $glob_pattern, $glob_options),
                    $ret
                );
                unset($subdir);
            }
        }

        return $ret;
    }
}