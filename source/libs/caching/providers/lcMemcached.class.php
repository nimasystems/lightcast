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

class lcMemcached extends lcMemcache implements iCacheMultiStorage
{
    /** @var Memcached */
    protected $memcache_backend;

    public function getMulti($key)
    {
        return $this->memcache_backend->getMulti($key);
    }

    public function set($key, $value = null, $lifetime = null, $other_flags = null)
    {
        $ret = $this->memcache_backend->set($key, $value, $lifetime);

        return $ret;
    }

    protected function initBackend()
    {
        // check for memcache
        if (!class_exists('Memcached', false)) {
            throw new Exception('Memcached is not available');
        }

        $this->memcache_backend = new Memcached();
    }
}