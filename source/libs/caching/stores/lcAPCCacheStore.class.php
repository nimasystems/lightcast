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

class lcAPCCacheStorage extends lcCacheStore implements iDatabaseCacheProvider, ArrayAccess, iDebuggable
{
    /** @var lcAPC */
    protected $apc;

    private $namespace_prefix;

    public function initialize()
    {
        parent::initialize();

        $this->apc = new lcAPC();

        // global application / project namespace prefix
        $this->namespace_prefix = isset($this->configuration['cache.namespace']) ? ((string)$this->configuration['cache.namespace']) . '_' : 'lc_';
        assert(isset($this->namespace_prefix));
    }

    public function shutdown()
    {
        $this->apc = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug = [
            'namespace_prefix' => $this->namespace_prefix,
        ];

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function clear()
    {
        $this->apc->clear();
    }

    public function hasValues()
    {
        throw new lcSystemException('Unimplemented');
    }

    public function count()
    {
        throw new lcSystemException('Unimplemented');
    }

    // lifetime passed in seconds!
    // max object size: 1 MB!

    public function getCachingSystem()
    {
        return $this->apc->getBackend();
    }

    public function setDbCache($namespace, $key, $value = null, $lifetime = null)
    {
        $key = $namespace . ':' . $key;
        return $this->set($key, $value, $lifetime);
    }

    public function set($key, $value = null, $lifetime = null)
    {
        $key = (string)$key;

        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $key1 = $this->keyWithNamespace($key);

        $res = $this->apc->set($key1, $value, $lifetime);

        // throw an exception only if debugging
        if (!$res && DO_DEBUG) {
            throw new lcSystemException('Cannot write data to APC, key: ' . $key1 . ', life: ' . $lifetime);
        }

        return true;
    }

    protected function keyWithNamespace($key)
    {
        $k = $this->namespace_prefix . $key;
        return $k;
    }

    public function removeDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        $this->remove($key);
    }

    public function remove($key)
    {
        $namespaced_key = $this->keyWithNamespace($key);

        $this->apc->remove($namespaced_key);
    }

    #pragma mark - Database Caching

    public function removeDbCacheForNamespace($namespace)
    {
        // cannot be implemented - no namespace support
        return false;
    }

    public function getDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        return $this->get($key);
    }

    public function get($key)
    {
        if (is_array($key)) {
            throw new lcInvalidArgumentException('Multiply key fetching is not supported');
        }

        $key1 = $this->keyWithNamespace($key);
        $ret = $this->apc->get($key1);

        return $ret;
    }

    public function hasDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        return $this->has($key);
    }

    public function has($key)
    {
        return $this->apc->has($key);
    }
}
