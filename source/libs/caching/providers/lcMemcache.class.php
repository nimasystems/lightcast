<?php /** @noinspection PhpComposerExtensionStubsInspection */

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

class lcMemcache extends lcCacheStore
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
        return $this->memcache_backend->addServer($hostname, $port);
    }

    public function getStats()
    {
        return $this->memcache_backend->getStats();
    }

    public function getBackend()
    {
        return $this->memcache_backend;
    }

    public function set($key, $value = null, array $options = [])
    {
        $other_flags = isset($options['flags']) ? $options['flags'] : null;
        $lifetime = isset($options['lifetime']) ? $options['lifetime'] : null;
        return $this->memcache_backend->set($key, $value, $other_flags, $lifetime);
    }

    public function remove($key)
    {
        return $this->memcache_backend->delete($key);
    }

    public function has($key)
    {
        return (bool)$this->get($key) ? true : false;
    }

    public function get($key)
    {
        return $this->memcache_backend->get($key);
    }

    public function clear()
    {
        return $this->memcache_backend->flush();
    }

    public function hasValues()
    {
        // TODO: Implement hasValues() method.
    }

    public function count()
    {
        // TODO: Implement count() method.
    }

    public function getCachingSystem()
    {
        return $this->memcache_backend;
    }
}