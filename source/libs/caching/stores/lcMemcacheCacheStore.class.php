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

class lcMemcacheCacheStorage extends lcCacheStore implements iDatabaseCacheProvider, ArrayAccess, iDebuggable
{
    const DEFAULT_MEMCACHE_STORE = 'memcache';

    const MAX_KEY_SIZE = 250;

    /** @var lcMemcache */
    protected $memcache;

    private $namespace_prefix;

    private $should_use_internal_storage;
    private $internal_storage;

    public function initialize()
    {
        parent::initialize();

        $memcache_store = isset($this->configuration['cache.memcache_store']) ? (string)$this->configuration['cache.memcache_store'] : self::DEFAULT_MEMCACHE_STORE;

        if (!$memcache_store) {
            throw new lcConfigException('Invalid memcache store set in configuration', 1);
        }

        $memcache_store = 'lc' . lcInflector::camelize($memcache_store);

        if (!class_exists($memcache_store)) {
            throw new lcConfigException('Invalid memcache store set in configuration', 2);
        }

        $this->memcache = new $memcache_store();

        if (!($this->memcache instanceof iCacheStorage)) {
            throw new lcConfigException('Invalid memcache store set in configuration', 3);
        }

        // internal storage
        $this->internal_storage = [];
        $this->should_use_internal_storage = isset($this->configuration['cache.use_internal_storage']) ? (bool)$this->configuration['cache.use_internal_storage'] : true;

        // init from the current configuration
        $servers_array = $this->configuration['cache.servers'];

        if (!$servers_array || !is_array($servers_array)) {
            throw new lcConfigException('No memcached servers configured');
        }

        $c = 0;

        foreach ($servers_array as $server) {
            $ex = array_filter(explode(':', $server));

            if (!isset($ex[0])) {
                throw new lcConfigException('Invalid server at position ' . $c);
            }

            $y = 0;

            // check for dups - memcached does not do this!
            foreach ($servers_array as $server2) {
                $ex2 = array_filter(explode(':', $server2));

                if (!isset($ex2[0])) {
                    continue;
                }

                if ($ex2[0] === $ex[0] && $c != $y) {
                    throw new lcConfigException('Duplicate server detected: ' . $ex2[0]);
                }

                $y++;
                unset($ex2, $server2);
            }

            if (!isset($ex[1])) {
                $ex[1] = lcMemcache::DEFAULT_PORT;
            }

            // set the server to memcached
            $this->memcache->addServer($ex[0], $ex[1]);

            $c++;
            unset($server, $ex);
        }

        // global application / project namespace prefix
        $this->namespace_prefix = isset($this->configuration['cache.namespace']) ? ((string)$this->configuration['cache.namespace']) . '_' : 'lc_';

        // append configuration unique id - when it changes (config changes) - cache will be invalidated
        $this->namespace_prefix = $this->namespace_prefix . $this->configuration->getUniqueProjectId() . '_';

        assert(isset($this->namespace_prefix));
    }

    public function shutdown()
    {
        $this->memcache = null;

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
        $this->memcache->clear();

        // internal storage
        $use_internal_storage = $this->should_use_internal_storage;

        if ($use_internal_storage) {
            $this->internal_storage = [];
        }
    }

    public function hasValues()
    {
        throw new lcSystemException('Unimplemented');
    }

    public function getStats()
    {
        return $this->count();
    }

    public function count()
    {
        if (!$stats = $this->memcache->getStats()) {
            return false;
        }

        if (!isset($stats['total_items'])) {
            return false;
        }

        return (int)$stats['total_items'];
    }

    // lifetime passed in seconds!
    // max object size: 1 MB!

    public function getCachingSystem()
    {
        return $this->memcache->getBackend();
    }

    public function setDbCache($namespace, $key, $value = null, $lifetime = null)
    {
        $key = $namespace . ':' . $key;
        return $this->set($key, $value, $lifetime);
    }

    public function set($key, $value = null, $lifetime = null, $flags = null)
    {
        $all_kv = [];

        if (is_array($key)) {

            foreach ($key as $kk) {

                $kk_prev = $kk;
                $kk = (string)$kk;

                if (!$kk) {
                    throw new lcInvalidArgumentException('Invalid params');
                }

                $kk = $this->keyWithNamespace($kk);

                if (strlen($kk) > self::MAX_KEY_SIZE) {
                    throw new lcInvalidArgumentException('Invalid key size for memcached object: ' . $kk);
                }

                $val = (($value && is_array($value) && isset($value[$kk_prev])) ? $value[$kk_prev] : $value);

                $all_kv[$kk] = $val;

                unset($kk, $val);
            }

        } else {
            $key = (string)$key;

            if (!$key) {
                throw new lcInvalidArgumentException('Invalid params');
            }

            $key = $this->keyWithNamespace($key);

            if (strlen($key) > self::MAX_KEY_SIZE) {
                throw new lcInvalidArgumentException('Invalid key size for memcached object: ' . $key);
            }

            $all_kv[$key] = $value;
        }

        /* Set the flags to current unixtime
         * to allow proper caching with nginx / memc module
        * which is able to return 304 Not Modified
        * - if not flags already set!
        */
        // Disabled as it conflicts with memcache internal usage of this flag
        // until we figure out what to do with this...
        //'PHP Error: MemcachePool::set() [memcachepool.set]: The lowest two bytes of the flags array is reserved for pecl/memcache internal use'
        /*if (!$flags)
        {
        $flags = time();
        }*/

        // internal storage
        $use_internal_storage = $this->should_use_internal_storage;

        $res = false;

        foreach ($all_kv as $akey => $avalue) {

            $flags = isset($flags) ? $flags : 0;
            $res = $this->memcache->set($akey, $avalue, (isset($lifetime) ? (time() + $lifetime) : 0), $flags);

            if ($use_internal_storage) {
                $this->internal_storage[$akey] = $avalue;
            }

            unset($akey, $avalue);
        }

        // throw an exception only if debugging
        if (!$res && DO_DEBUG) {
            throw new lcSystemException('Cannot write data to Memcache, key: ' . $key . ', life: ' . $lifetime);
        }

        return $res;
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

        $this->memcache->remove($namespaced_key);

        // internal storage
        $use_internal_storage = $this->should_use_internal_storage;

        if ($use_internal_storage && isset($this->internal_storage[$namespaced_key])) {
            unset($this->internal_storage[$namespaced_key]);
        }
    }

    public function removeDbCacheForNamespace($namespace)
    {
        // cannot be implemented - no namespace support
        return false;
    }

    #pragma mark - Database Caching

    public function getDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        return $this->get($key);
    }

    /**
     * @param string $key
     * @return array|null
     * @throws Exception
     * @throws lcInvalidArgumentException
     */
    public function get($key)
    {
        $use_internal_storage = $this->should_use_internal_storage;

        $keys = [];
        $unnamespaced_keys = [];
        $results = [];

        // allow fetching multiply values with keys
        if (is_array($key)) {
            if (!$key) {
                throw new lcInvalidArgumentException('Invalid params');
            }

            // append namespace prefixes
            foreach ($key as $key1) {
                $namespaced_key = $this->keyWithNamespace($key1);
                $keys[] = $namespaced_key;
                $unnamespaced_keys[$namespaced_key] = $key1;
                unset($key1, $namespaced_key);
            }
        } else {
            $key1 = $this->keyWithNamespace($key);

            if (!$key1) {
                throw new lcInvalidArgumentException('Invalid params');
            }

            $keys = [$key1];
            $unnamespaced_keys[$key1] = $key;
            unset($key1);
        }

        // fetch the data from internal cache first
        if ($use_internal_storage) {
            foreach ($keys as $key1) {
                $value = null;

                // internal storage read
                if (isset($this->internal_storage[$key1])) {
                    $value = $this->internal_storage[$key1];
                }

                $results[$unnamespaced_keys[$key1]] = $value;

                unset($key1, $value);
            }
        }

        // try to fetch from memcache then
        // if we have more than one requests to fetch
        // use getMulti
        try {
            if (count($keys) > 1) {
                if ($this->memcache instanceof iCacheMultiStorage) {
                    // clear out the keys which we already have
                    foreach ($keys as $key1) {
                        if (isset($results[$unnamespaced_keys[$key1]])) {
                            unset($keys[$key1]);
                        }

                        unset($key1);
                    }

                    // fetch from memcached
                    $cas = null;
                    $values = $this->memcache->getMulti($keys);

                    // parse the results
                    if ($values) {
                        foreach ($values as $kk => $vv) {
                            // internal storage writeback
                            if ($use_internal_storage) {
                                $this->internal_storage[$kk] = $vv;
                            }

                            $results[$unnamespaced_keys[$kk]] = $vv;

                            unset($kk, $vv);
                        }
                    }

                    unset($values);
                } else {
                    foreach ($keys as $key1) {

                        $value = $this->memcache->get($key1);

                        // internal storage writeback
                        if ($use_internal_storage) {
                            $this->internal_storage[$key1] = $value;
                        }

                        $results[$unnamespaced_keys[$key1]] = $value;

                        unset($value);
                        unset($key1);
                    }
                }

            } elseif (!isset($results[$unnamespaced_keys[$keys[0]]])) {
                $key1 = $keys[0];

                $value = $this->memcache->get($key1);

                // internal storage writeback
                if ($use_internal_storage) {
                    $this->internal_storage[$key1] = $value;
                }

                $results[$unnamespaced_keys[$key1]] = $value;

                unset($value);
                unset($key1);
            }
        } catch (Exception $e) {
            if (DO_DEBUG) {
                throw $e;
            }
        }

        $ret = null;

        if (is_array($key)) {
            $ret = $results;
        } else {
            $ret = isset($results[$key]) ? $results[$key] : null;
        }

        return $ret;
    }

    public function hasDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        return $this->has($key);
    }

    public function has($key)
    {
        return $this->memcache->has($key);
    }

    protected function makeSafeKey($key)
    {
        return md5($key);
    }
}
