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
 * @changed $Id: lcSys.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcSys
{
    public static function execCmd($cmd, &$result = null, $dont_implode = false)
    {
        $output = null;
        exec($cmd, $output, $result);

        return ($dont_implode ? $output : implode("\n", $output));
    }

    public static function correctShellParam($param)
    {
        $param = str_replace(' ', '\ ', $param);
        $param = str_replace('&', '\&', $param);
        $param = escapeshellarg($param);

        return $param;
    }

    public static function getHostname()
    {
        static $hostname;

        if ($hostname) {
            return $hostname;
        }

        if (function_exists('gethostname')) {
            $hostname = gethostname();
        } else {
            $hostname = php_uname('n');
        }

        // check other ways
        $hostname = !$hostname && isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $hostname;

        return $hostname;
    }

    public static function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public static function get_sapi()
    {
        return php_sapi_name();
    }

    public static function getOSType($basic = false)
    {
        $ostype = $basic ? PHP_OS : php_uname();
        return $ostype;
    }

    public static function isOSWin()
    {
        if (strtolower(substr(self::getOSType(), 0, 3)) == strtolower('WIN')) {
            return true;
        }

        return false;
    }

    public static function isOSLinux()
    {
        if (strtolower(substr(self::getOSType(), 0, 3)) == strtolower('LIN')) {
            return true;
        }

        return false;
    }

    public static function getPhpVer($php_extension = null)
    {
        return isset($php_extension) ? phpversion($php_extension) : phpversion();
    }

    public static function getProcessOwner()
    {
        //works only in Linux
        if (substr(self::getOSType(), 0, 3) != 'Lin') {
            return false;
        }

        $processUser = posix_getpwuid(posix_geteuid());
        return $processUser['name'];
    }

    public static function getProcessList()
    {
        return array_filter(explode(PHP_EOL, shell_exec("/bin/ps -e | awk '{print $1}'")));
    }

    public static function isRunningCLI()
    {
        return 0 == strncasecmp(PHP_SAPI, 'cli', 3);
    }

    /*public static function getPhpCli()
     {
    if (self::isOSLinux()) return '/usr/bin/php'; else
        if (self::isOSWin()) return 'C:\\php\\php.exe'; else
        return false;
    }*/

    /**
     * Get path to php cli.
     * @return string If no php cli found
     * @throws lcSystemException
     */
    public static function getPhpCli()
    {
        $path = getenv('PATH') ? getenv('PATH') : getenv('Path');
        $suffixes = DIRECTORY_SEPARATOR == '\\' ? (getenv('PATHEXT') ?
            explode(PATH_SEPARATOR, getenv('PATHEXT')) : array('.exe', '.bat', '.cmd', '.com')) :
            array('');

        foreach (array('php5', 'php') as $phpCli) {
            foreach ($suffixes as $suffix) {
                $pp = explode(PATH_SEPARATOR, $path);

                foreach ($pp as $dir) {
                    $file = $dir . DIRECTORY_SEPARATOR . $phpCli . $suffix;
                    if (is_executable($file)) {
                        return $file;
                    }
                }
            }
        }

        throw new lcSystemException('Unable to find PHP executable.');
    }

    /**
     * From PEAR System.php
     *
     * LICENSE: This source file is subject to version 3.0 of the PHP license
     * that is available through the world-wide-web at the following URI:
     * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
     * the PHP License and are unable to obtain it through the web, please
     * send a note to license@php.net so we can mail you a copy immediately.
     *
     * @author     Tomas V.V.Cox <cox@idecnet.com>
     * @copyright  1997-2006 The PHP Group
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     */
    public static function getTmpDir()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            if ($var = isset($_ENV['TEMP']) ? $_ENV['TEMP'] : getenv('TEMP')) {
                return $var;
            }
            if ($var = isset($_ENV['TMP']) ? $_ENV['TMP'] : getenv('TMP')) {
                return $var;
            }
            if ($var = isset($_ENV['windir']) ? $_ENV['windir'] : getenv('windir')) {
                return $var;
            }

            return getenv('SystemRoot') . '\temp';
        }

        if ($var = isset($_ENV['TMPDIR']) ? $_ENV['TMPDIR'] : getenv('TMPDIR')) {
            return $var;
        }

        return '/tmp';
    }

    public static function getScriptOwner()
    {
        return get_current_user();
    }

    public static function getScriptUID()
    {
        return getmyuid();
    }

    public static function getScriptGID()
    {
        return getmygid();
    }

    public static function getMemoryUsage($humanize = false, $precision = 2, $size_in = null)
    {
        $mem = memory_get_usage();

        return $humanize ?
            self::formatObjectSize($mem, $precision, $size_in) :
            $mem;
    }

    public static function getMemoryPeakUsage($emalloc = false, $humanize = false, $precision = 2, $size_in = null)
    {
        $mem = lcVm::memory_get_peak_usage($emalloc);

        return $humanize ?
            self::formatObjectSize($mem, $precision, $size_in) :
            $mem;
    }

    public static function getUploadMaxFilesize()
    {
        $max_fs = self::getPHPVarBytesRepresentation(ini_get('upload_max_filesize'));
        $post_max_size = self::getPHPVarBytesRepresentation(ini_get('post_max_size'));

        $max = ($max_fs > $post_max_size) ? $max_fs : $post_max_size;

        return $max;
    }

    public static function getPHPVarBytesRepresentation($input)
    {
        $ret = 0;

        if (stristr($input, 'b')) {
            $ret = $input;
        } elseif (stristr($input, 'k')) {
            $ret = $input * 1024;
        } elseif (stristr($input, 'm')) {
            $ret = $input * 1048576;
        } elseif (stristr($input, 'g')) {
            $ret = $input * 1073741824;
        } elseif (stristr($input, 't')) {
            $ret = $input * 1099511627776;
        }

        return $ret;
    }

    public static function formatObjectSize($bytes, $precision = 2)
    {
        $suffix = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $total = count($suffix);

        for ($i = 0; $bytes > 1024 && $i < $total; $i++) {
            $bytes /= 1024;
        }

        return number_format($bytes, $precision) . ' ' . $suffix[$i];
    }

    public static function formatFileSize($object_size, $precision = 2, $size_in = null)
    {
        # $file_size MUST be in bytes
        $object_size = (int)$object_size;

        if (!isset($size_in)) {
            if ($object_size < 1) {
                return '-';
            }

            if ($object_size < 1024) {
                return $object_size . ' b';
            }

            if (($object_size > 1024) && ($object_size < 1048576)) {
                return round($object_size / 1024, $precision) . ' kb';
            }

            if ($object_size > 1048576) {
                return round($object_size / 1048576, $precision) . ' mb';
            }
        } else {

            switch ($size_in) {
                case 'kb': {
                    return round($object_size / 1024, $precision) . ' kb';

                    break;
                }
                case 'mb': {
                    return round($object_size / 1048576, $precision) . ' mb';

                    break;
                }
                case 'gb': {
                    return round($object_size / 1073741824, $precision) . ' gb';

                    break;
                }
                case 'pb': {
                    return round($object_size / 1099511627776, $precision) . ' pb';

                    break;
                }
                default: {
                    return round($object_size / 1125899906842624, $precision) . ' mb';

                    break;
                }
            }
        }

        return null;
    }

    public static function loadedExts()
    {
        return get_loaded_extensions();
    }

    public static function getTotalDiskSpace($device = '/')
    {
        return disk_total_space($device);
    }

    public static function getFreeDiskSpace($device = '/')
    {
        return disk_free_space($device);
    }

    public static function getLoad()
    {
        if (!function_exists('sys_getloadavg')) {
            return false;
        }

        $lavg = sys_getloadavg();

        if ($lavg) {
            if (isset($lavg[0])) {
                $lavg[0] = sprintf("%01.2f", $lavg[0]);
            }

            if (isset($lavg[1])) {
                $lavg[1] = sprintf("%01.2f", $lavg[1]);
            }

            if (isset($lavg[2])) {
                $lavg[2] = sprintf("%01.2f", $lavg[2]);
            }
        }

        return $lavg;
    }

    public static function isPidRunning($pid)
    {
        $pid = (int)$pid;

        if (!$pid) {
            return false;
        }

        $result = null;
        lcSys::execCmd('ps -p ' . $pid, $result, false);

        $ret = ($result == '0');

        return $ret;
    }
}
