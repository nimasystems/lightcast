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

class lcDirs
{
    public static function recursiveMove($src, $dest)
    {
        // If source is not a directory stop processing
        if (!is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist create it
        if (!is_dir($dest) && !mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);

        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), $dest . DS . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                self::recursiveMove($f->getRealPath(), $dest . DS . $f);
            }
        }

        rmdir($src);

        return true;
    }

    public static function recursiveCopy($src, $dest)
    {

        // If source is not a directory stop processing
        if (!is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist create it
        if (!is_dir($dest) && !mkdir($dest)) {
            // If the destination directory could not be created stop processing
            return false;
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), $dest . DS . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                self::recursiveCopy($f->getRealPath(), $dest . DS . $f);
            }
        }

        return true;
    }

    public static function recursiveChmod($path, $filePerm = 0644, $dirPerm = 0755)
    {
        // Check if the path exists
        if (!file_exists($path)) {
            return false;
        }

        // See whether this is a file
        if (is_file($path)) {
            // Chmod the file with our given filepermissions
            chmod($path, $filePerm);

            // If this is a directory...
        } elseif (is_dir($path)) {
            // Then get an array of the contents
            $foldersAndFiles = scandir($path);

            // Remove " . " and " .." from the list
            $entries = array_slice($foldersAndFiles, 2);

            // Parse every result...
            foreach ($entries as $entry) {
                // And call this function again recursively, with the same permissions
                self::recursiveChmod($path . ' / ' . $entry, $filePerm, $dirPerm);
            }

            // When we are done with the contents of the directory, we chmod the directory itself
            chmod($path, $dirPerm);
        }

        // Everything seemed to work out well, return true
        return true;
    }

    public static function removeDirDelimiter($dirname)
    {
        /** @noinspection PhpExpressionResultUnusedInspection */
        ($dirname{strlen($dirname) - 1} == DS) ?
            $dirname = substr($dirname, 0, -1) :
            $dirname;

        return $dirname;
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     * @param string $source Source path
     * @param string $dest Destination path
     * @param int|string $permissions New folder creation permissions
     * @return bool Returns true on success, false on failure
     */
    public static function xcopy($source, $dest, $permissions = 0755)
    {
        // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $permissions);
        }

        // Loop through the folder
        $dir = dir($source);

        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories
            lcDirs::xcopy("$source / $entry", "$dest / $entry");
        }

        // Clean up
        $dir->close();

        return true;
    }

    public static function isDirEmpty($dir)
    {
        $iterator = new \FilesystemIterator($dir);
        return !$iterator->valid();
    }

    public static function checkCreateDir($dirname, $trycreate = false)
    {
        if (substr($dirname, strlen($dirname) - 1, strlen($dirname)) != DS) {
            $dirname .= DS;
        }

        if (!$trycreate && ((!is_dir($dirname)) || (!is_readable($dirname)))) {
            return false;
        }

        if ($trycreate) {
            if (is_dir($dirname)) {
                if (!is_writable($dirname)) {
                    throw new lcIOException('Directory ' . $dirname . ' is not writeable');
                }
            } else {
                self::mkdirRecursive($dirname);
            }
        }
        return true;
    }

    public static function mkdirRecursive($path, $mode = 0777)
    {
        if (is_dir($path)) {
            return true;
        }

        $old = umask(0);

        if (!@mkdir($path, $mode, true)) {
            umask($old);
            throw new lcIOException('Cannot create folder recursively: ' . $path);
        }

        umask($old);

        if ($old != umask()) {
            throw new lcIOException('Error setting umask');
        }

        return true;
    }

    public static function rmdirRecursive($directory, $empty = false, $skipHidden = false)
    {
        if (substr($directory, -1) == DS) {
            $directory = substr($directory, 0, -1);
        }

        if (!file_exists($directory) || !is_dir($directory)) {
            return true;
            //throw new lcIOException('Directory is not valid: '.$directory);
        } elseif (!is_readable($directory)) {
            throw new lcIOException('Directory is not readable: ' . $directory);
        } else {
            $handle = opendir($directory);

            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if ($empty && $skipHidden && 0 === strpos($item, '.')) {
                        continue;
                    }

                    $path = $directory . DS . $item;

                    if (is_dir($path)) {
                        # makes a recursion to get subfolders
                        self::rmdirRecursive($path);
                    } else {
                        unlink($path);
                    }
                }

                unset($item, $path);
            }

            closedir($handle);

            if ($empty == false) {
                if (!rmdir($directory)) {
                    throw new lcIOException('Cannot remove folder recursively: ' . $directory);
                }
            }

            return true;
        }
    }

    public static function recursiveFilesCallback($dir, $callback_func, array $callback_params = null, $scan_subdirs = true)
    {
        $dir = self::fixDirDelimiter($dir);

        $files = self::searchDir($dir);

        if ($files) {
            foreach ($files as $file) {
                $filename = $file['name'];
                call_user_func_array($callback_func, array_merge(array($dir . $filename), (array)$callback_params));
                unset($file);
            }
        }

        unset($files);

        if ($scan_subdirs) {
            $subdirs = self::getSubDirsOfDir($dir);

            if ($subdirs) {
                foreach ($subdirs as $subdir) {
                    self::recursiveFilesCallback($dir . $subdir, $callback_func, $callback_params);
                    unset($subdir);
                }
            }
        }

        unset($subdirs);
    }

    public static function fixDirDelimiter($dirname)
    {
        if ($dirname{strlen($dirname) - 1} != DS) {
            $dirname .= DS;
        }

        return $dirname;
    }

    public static function searchDir($dir, $onlyfiles = true, $skip_system_dirs = false, array $filetypes_ = null)
    {
        if (!is_dir($dir)) {
            return false;
        }

        if (substr($dir, strlen($dir) - 1, strlen($dir)) != '/') {
            $dir .= '/';
        }

        if (!$dh = opendir($dir)) {
            return false;
        }

        $files = array();

        while (($file = readdir($dh)) !== false) {
            if ($skip_system_dirs && (($file == '.') || ($file == '..'))) {
                continue;
            }

            $t = filetype($dir . $file);

            if ($onlyfiles && ($t != 'file')) {
                continue;
            }

            if ($filetypes_) {
                $fext = lcFiles::getFileExt($file);

                if (!in_array($fext, $filetypes_)) {
                    continue;
                }
            }

            $files[] = array(
                'name' => $file,
                'type' => $t
            );
            unset($file, $t);
        }

        closedir($dh);

        return $files;
    }

    public static function getSubDirsOfDir($dir, $skip = null)
    {
        if (!$d = @dir($dir)) {
            return false;
        }

        $dirs = array();

        while (false !== ($entry = $d->read())) {
            if (($entry == '.') || ($entry == '..') || !is_dir($dir . DS . $entry) ||
                (null !== $skip && ($entry == $skip) || 0 === strpos($entry, '.'))
            ) {
                continue;
            }

            $dirs[] = $entry;
            unset($entry);
        }
        $d->close();

        return $dirs;
    }

    public static function getFileCountInDir($dir)
    {
        return count(glob($dir . '*'));
    }

    public static function getRandomFileDirName()
    {
        return
            md5(
                md5(time()) .
                md5(microtime()) .
                md5(mt_rand(1, 10000000)) .
                md5(mt_rand(1, 10000000)) .
                md5(mt_rand(1, 10000000)) .
                md5(mt_rand(1, 10000000))
            );
    }

    public static function exists($dirname)
    {
        return (file_exists($dirname) && is_dir($dirname));
    }

    public static function writable($dirname)
    {
        return is_writable($dirname);
    }

    public static function create($dirname, $recursive = false, $mode = 0777)
    {
        try {
            if ($recursive) {
                self::mkdirRecursive($dirname, $mode);
            } else {
                $old = umask(0);
                mkdir($dirname, $mode);
                umask($old);

                if ($old != umask()) {
                    throw new lcIOException('Error setting umask');
                }
            }
        } catch (Exception $e) {
            throw new lcIOException('Cannot create folder: ' . $dirname . ': ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
