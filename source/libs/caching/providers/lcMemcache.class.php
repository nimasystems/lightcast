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
 * @changed $Id: lcMemcache.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcMemcache extends lcSysObj implements iCacheStorage
{
    const DEFAULT_PORT = 11211;
    /** @var Memcache */
    protected $memcache_backend;

    public function __construct()
    {
        parent::__construct();

        $this->initBackend();
    }

    protected function initBackend()
    {
        // check for memcache
        if (!class_exists('Memcache', false)) {
            throw new Exception('Memcache is not available');
        }

        $this->memcache_backend = new Memcache();

        ini_set('memcache.compress_threshold', 0);
        ini_set('memcache.protocol', 'ascii');

        // disable compression
        //$this->memcache_backend->setCompressThreshold(0, 1);
    }

    public function addServer($hostname, $port = self::DEFAULT_PORT)
    {
        $ret = $this->memcache_backend->addServer($hostname, $port);

        return $ret;
    }

    public function getStats()
    {
        return $this->memcache_backend->getStats();
    }

    public function getBackend()
    {
        $ret = $this->memcache_backend;

        return $ret;
    }

    public function set($key, $value = null, $lifetime = null, $other_flags = null)
    {
        $ret = $this->memcache_backend->set($key, $value, $other_flags, $lifetime);

        return $ret;
    }

    public function remove($key)
    {
        $ret = $this->memcache_backend->delete($key);

        return $ret;
    }

    public function has($key)
    {
        $has = (bool)$this->get($key) ? true : false;

        return $has;
    }

    public function get($key)
    {
        $ret = $this->memcache_backend->get($key);

        return $ret;
    }

    public function clear()
    {
        $ret = $this->memcache_backend->flush();

        return $ret;
    }
}