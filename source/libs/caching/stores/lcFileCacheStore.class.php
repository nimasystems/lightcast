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

class lcFileCacheStore extends lcCacheStore implements iDebuggable
{
    const FILE_MODE = 0777;
    const FILES_PER_BUCKET = 30;
    const CACHE_META_FILE = 'cache.meta';
    const READ_ALL = 1;
    const READ_TIMESTAMP = 2;
    const READ_FILENAME = 3;
    const READ_CHECK = 4;
    const READ_DATA = 5;
    const READ_METAINFO = 6;
    const WRITE_DATA = 1;
    const WRITE_REMOVE_DATA = 2;
    const FLAG_REMOVED = 2;
    const FLAG_OK = 3;
    const META_IDENT = '#LIGHTCAST_CACHE_META';
    private $cache_folder;
    private $total_entries = 0;

    //const FLAG_EXPIRED = 1;
    private $next_dir = 0;
    private $next_subdir = 0;
    private $next_file = 0;

    /*
     * File cache is stored in a special directory structure
    * 1st level dirs - [0-9]
    * 2nd level dirs - [0-9]
    * - cache is stored in 2nd level dirs - with name as the cache key
    *
    * - cache_folder/cache.meta is storing:
    * 1. #LIGHTCAST_CACHE_META - prefix
    * 2. total number of cache entries
    * 3-10 - reserved data for future implementations
    * 3. #DATA - header for starting cache files
    * 4. \n
    * 11-N - cache [KEY]\t[TIMESTAMP]\t[cache FILENAME]
    * - cache filenames are [0..N.cache]
    * - TIMESTAMP - is the expiration time
    *
    */

    public function initialize()
    {
        parent::initialize();

        // cache folder must be relative to app_root_dir
        $this->cache_folder = (string)$this->configuration->getCacheDir();

        if (!isset($this->cache_folder)) {
            throw new lcConfigException('Cache folder not set');
        }

        $this->next_dir = 0;
        $this->next_subdir = 0;
        $this->next_file = 0;

        $this->initCache();
    }

    private function initCache()
    {
        try {
            // check / create main cache folder
            if (!lcDirs::exists($this->cache_folder)) {
                lcDirs::mkdirRecursive($this->cache_folder, self::FILE_MODE);
            }

            // check for cache folders - recreate folder structure
            for ($i = 0; $i <= 9; $i++) {
                for ($j = 0; $j <= 9; $j++) {
                    $dirname = $this->cache_folder . DS . $i . DS . $j;

                    if (!lcDirs::exists($dirname)) {
                        lcDirs::mkdirRecursive($dirname, self::FILE_MODE);
                    }
                }
            }

            // init the cache metadata file
            $this->initCacheMetafile();
        } catch (Exception $e) {
            throw new lcSystemException('Cannot initialize cache: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function initCacheMetafile()
    {
        // check / create cache meta file
        if (lcFiles::exists($this->cache_folder . DS . self::CACHE_META_FILE)) {
            // read and save the metainfo
            $metainfo = $this->read(null, self::READ_METAINFO);
            $this->total_entries = $metainfo['total_entries'];
            $this->next_dir = $metainfo['next_dir'];
            $this->next_subdir = $metainfo['next_subdir'];
            $this->next_file = $metainfo['next_file'];
            return;
        }

        if (!$f = fopen($this->cache_folder . DS . self::CACHE_META_FILE, 'w+')) {
            throw new lcIOException('Cannot open cache file');
        }

        // initial metadata
        $data = $this->getMetadataInternal();

        // write zero
        fwrite($f, $data);

        @fclose($f);
    }

    private function read($key = null, $get_type = self::READ_DATA)
    {
        if (!$key && $get_type != self::READ_METAINFO) {
            throw new lcInvalidArgumentException('Missing KEY. Cannot obtain cache data');
        }

        try {
            // open and lock the metafile
            if (!$f = fopen($this->cache_folder . DS . self::CACHE_META_FILE, 'r')) {
                throw new lcIOException('Cannot read from cache meta file. Key: ' . $key);
            }

            if (!flock($f, LOCK_SH)) {
                throw new lcIOException('Cannot lock the cache meta file. Key: ' . $key);
            }

            $data = null;

            // check the metadata file
            if (!$line = explode(',', stream_get_line($f, 1000, "\n"))) {
                throw new lcSystemException('Invalid cache. Reinitialization needed');
            }

            if (count($line) != 5) {
                throw new lcSystemException('Invalid cache. Reinitialization needed');
            }

            if ($line[0] != self::META_IDENT) {
                throw new lcSystemException('Invalid cache. Reinitialization needed');
            }

            // if we just want the total cache entries
            if ($get_type == self::READ_METAINFO) {
                return array(
                    'total_entries' => (int)$line[1],
                    'next_dir' => (int)$line[2],
                    'next_subdir' => (int)$line[3],
                    'next_file' => (int)$line[4]
                );
            }

            $line = null;

            // read the data from the meta file
            while (!feof($f)) {
                $line = stream_get_line($f, 1000, "\n");

                if ($line = array_filter(explode("\t", $line))) {
                    if (count($line) == 4) {
                        // check if it is not deleted
                        if ($line[1] == $key && $line[0] == self::FLAG_OK) {
                            // check if it has not expired
                            if ($line[2] > time()) {
                                $data = $line;
                                break;
                            } else {
                                $line = null;
                            }
                        } else {
                            $line = null;
                        }
                    } else {
                        $line = null;
                    }
                }
            }

            // close and unlock the metafile
            if (!flock($f, LOCK_UN)) {
                throw new lcIOException('Cannot unlock the cache meta file. Key: ' . $key);
            }

            @fclose($f);
        } catch (Exception $e) {
            throw new lcIOException('Error while reading the cache meta file: ' . $e->getMessage(), null, $e);
        }

        // if just checking
        if ($get_type == self::READ_CHECK) {
            return $data ? true : false;
        }

        // not found return nothing
        if (!$data) {
            return null;
        }

        // if just requesting the last time
        if ($get_type == self::READ_ALL) {
            return array(
                'status' => $data[0],
                'key' => $data[1],
                'timestamp' => $data[2],
                'cache_filename' => $data[3]
            );
        } elseif ($get_type == self::READ_FILENAME) {
            return $data[3];
        } elseif ($get_type == self::READ_TIMESTAMP) {
            return $data[2];
        }

        // else read and return the cache

        // open the cache file
        $cachef = $this->cache_folder . DS . $data[3];

        try {
            if (!$f = fopen($cachef, 'rb')) {
                throw new lcIOException('Cannot read from the file cache. Key: ' . $key);
            }

            // lock the cache file
            if (!flock($f, LOCK_SH)) {
                throw new lcIOException('Cannot lock the cache file. Key: ' . $key);
            }

            // read the cache
            $cachedata = stream_get_contents($f);
            //{
            //	throw new lcIOException('Cannot read from the file cache. Key: '.$key);
            //}

            // close the cache
            if (!flock($f, LOCK_UN)) {
                throw new lcIOException('Cannot unlock the file cache. Key: ' . $key);
            }

            @fclose($f);
        } catch (Exception $e) {
            throw new lcIOException('Error while reading cache data (' . $cachef . '): ' . $e->getMessage(), null, $e);
        }

        if ($cachedata) {
            try {
                $cachedata = unserialize($cachedata);
            } catch (Exception $e) {
                throw new lcIOException('Error on unserializing cache data. Key: ' . $key, null, $e);
            }
        }

        return $cachedata;
    }

    private function getMetadataInternal()
    {
        $data =
            self::META_IDENT . ',' .
            (int)$this->total_entries . ',' .
            (int)$this->next_dir . ',' .
            (int)$this->next_subdir . ',' .
            (int)$this->next_file . "\n";

        return $data;
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug = array(
            'cache_folder' => $this->cache_folder,
            'total_entries' => $this->total_entries
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug = array(
            'total_entries' => $this->total_entries
        );

        return $debug;
    }

    public function compact()
    {
        try {
            // open the meta file in write mode
            if (!$f = fopen($this->cache_folder . DS . self::CACHE_META_FILE, 'rw+')) {
                throw new lcIOException('Cannot open cache file');
            }

            // lock the metafile for writing
            if (!flock($f, LOCK_EX)) {
                throw new lcIOException('Cannot open cache file');
            }

            $data = array();
            $line_num = 0;

            while (!feof($f)) {
                $str = stream_get_line($f, 1000, "\n");
                $line = array_filter(explode("\t", $str));

                if ($line_num <= 10) {
                    $data[] = $str;
                } else {
                    if (
                        $line &&
                        count($line) == 4 &&
                        $line[0] == self::FLAG_OK &&
                        $line[2] > time()
                    ) {
                        $data[] = $str;
                    }
                }

                ++$line_num;
            }

            // close and unlock the metafile
            if (!flock($f, LOCK_UN)) {
                throw new lcIOException('Cannot open cache file');
            }

            @fclose($f);

            // - create a temp dir
            // iterate from 0/0/0 up to the last file and save them
            // into the new temp dir
            // - remove the old cache dir contents
            // - move the new dir into the current one
            // - update internal class metadata
        } catch (Exception $e) {
            throw new lcIOException($e->getMessage(), null, $e);
        }
    }

    public function has($key)
    {
        return $this->read($key, self::READ_CHECK);
    }

    public function get($key)
    {
        return $this->read($key, self::READ_DATA);
    }

    public function set($key, $value = null, $lifetime = null)
    {
        if (!$lifetime) {
            $lifetime = $this->default_lifetime;
        }
        return $this->write($key, $value, self::WRITE_DATA, $lifetime);
    }

    private function write($key, $data = null, $write_type = self::WRITE_DATA, $lifetime = null)
    {
        if (strlen($key) < 1) {
            throw new lcInvalidArgumentException('Invalid cache KEY name: ' . $key);
        }

        $lifetime = isset($lifetime) ?
            time() + $lifetime :
            time() + $this->default_lifetime;

        // serialize the data
        if ($data) {
            $write_data = serialize($data);
        }

        try {
            // open the meta file in write mode
            if (!$f = fopen($this->cache_folder . DS . self::CACHE_META_FILE, 'rw+')) {
                throw new lcIOException('Cannot read from cache meta file. Key: ' . $key);
            }

            // lock the metafile for writing
            if (!flock($f, LOCK_EX)) {
                throw new lcIOException('Cannot lock the cache meta file. Key: ' . $key);
            }

            // walk each line - if there is a match on the current key
            // get the filename and timestamp
            // otherwise create a new line at the end of the file with the
            // current cache

            $data = null;
            $line = null;
            $lastpos = null;
            $cache_filename = null;

            while (!feof($f)) {
                $lastpos = ftell($f);

                $line = stream_get_line($f, 1000, "\n");

                if ($line = array_filter(explode("\t", $line))) {
                    if (count($line) == 4) {
                        if ($line[1] == $key) {
                            $data = $line;
                            $cache_filename = $line[3];

                            break;
                        }
                    } else {
                        $line = null;
                    }
                }
            }

            // write a key
            if ($write_type == self::WRITE_DATA) {
                if (!$data) {
                    if ($line) {
                        $cache_filename = $this->getNextCacheFilename();
                    } else {
                        $cache_filename = '0' . DS . '0' . DS . '0' . '.cache';
                    }

                    $dd = self::FLAG_OK . "\t" . $key . "\t" . $lifetime . "\t" . $cache_filename . "\n";

                    if (!fwrite($f, $dd)) {
                        throw new lcIOException('Cannot write to the cache metadata');
                    }

                    // increase the entry count
                    ++$this->total_entries;
                } else {
                    // the key is already in the cache
                    // update the expiration time
                    fseek($f, $lastpos + 1 + strlen("\t" . $key . "\t"));

                    fwrite($f, $lifetime, strlen($lifetime));

                    $cache_filename = $data[3];
                }
            } elseif ($write_type == self::WRITE_REMOVE_DATA) {
                if ($data) {
                    // remove the actual cache
                    $filename = $this->cache_folder . DS . $data[3];

                    if (lcFiles::exists($filename)) {
                        lcFiles::rm($filename);
                    }

                    // move one line back
                    fseek($f, $lastpos);

                    // write a removed flag
                    fwrite($f, self::FLAG_REMOVED, 1);

                    // decrease the entry count
                    $this->total_entries--;
                }
            }

            // update metadata
            fseek($f, 0);

            $m = $this->getMetadataInternal();

            fwrite($f, $m);

            // close and unlock the metafile
            if (!flock($f, LOCK_UN)) {
                throw new lcIOException('Cannot unlock the cache meta file. Key: ' . $key);
            }

            @fclose($f);

            // if a remove type return
            if ($write_type == self::WRITE_REMOVE_DATA) {
                return true;
            }

            // append folder
            $cache_filename = $this->cache_folder . DS . $cache_filename;

            // open the cache filename
            if (!$f = fopen($cache_filename, 'wb+')) {
                throw new lcIOException('Error while trying to write the cache. Key: ' . $key);
            }

            // lock the cache for writing
            if (!flock($f, LOCK_EX)) {
                throw new lcIOException('Cannot lock the cache file. Key: ' . $key);
            }

            // write the cache data
            if (isset($write_data)) {
                fwrite($f, $write_data, strlen($write_data));
            }

            // close and unlock the cache file
            if (!flock($f, LOCK_UN)) {
                throw new lcIOException('Cannot unlock the cache file. Key: ' . $key);
            }

            @fclose($f);
        } catch (Exception $e) {
            throw new lcIOException('Error while working with the cache meta file: ' . $e->getMessage(), null, $e);
        }

        return true;
    }

    private function getNextCacheFilename()
    {
        if ($this->next_file == self::FILES_PER_BUCKET) {
            $this->next_file = 0;

            if ($this->next_subdir == 9) {
                $this->next_subdir = 0;

                if ($this->next_dir == 9) {
                    throw new lcSystemException('Cache maximum storage reached');
                } else {
                    ++$this->next_dir;
                }
            } else {
                ++$this->next_subdir;
            }
        } else {
            ++$this->next_file;
        }

        return
            (int)$this->next_dir . DS .
            (int)$this->next_subdir . DS .
            (int)$this->next_file .
            '.cache';
    }

    public function remove($key)
    {
        return $this->write($key, null, self::WRITE_REMOVE_DATA);
    }

    public function clear()
    {
        if (!$this->total_entries) {
            return true;
        }

        return $this->reinitCache();
    }

    public function reinitCache()
    {
        lcDirs::rmdirRecursive($this->cache_folder, true);

        $this->total_entries = 0;
        $this->next_dir = 0;
        $this->next_subdir = 0;
        $this->next_file = 0;

        $this->initCache();

        return true;
    }

    public function hasValues()
    {
        return $this->total_entries ? true : false;
    }

    public function count()
    {
        return $this->total_entries;
    }

    public function getCachingSystem()
    {
        return false;
    }
}
