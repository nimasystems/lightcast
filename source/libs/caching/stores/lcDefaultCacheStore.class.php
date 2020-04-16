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

class lcDefaultCacheStore extends lcCacheStore implements iDatabaseCacheProvider, ArrayAccess, iDebuggable
{
    const DEFAULT_CACHE_BACKEND = 'memcache';

    /** @var lcCacheStore */
    protected $cache_backend;

    private $namespace_prefix;

    private $should_use_internal_storage;
    private $internal_storage;

    public function initialize()
    {
        parent::initialize();

        $cache_backend = isset($this->configuration['cache.backend']) ? (string)$this->configuration['cache.backend'] : self::DEFAULT_CACHE_BACKEND;

        if (!$cache_backend) {
            throw new lcConfigException('Invalid memcache store set in configuration', 1);
        }

        $cache_backend_cls = 'lc' . lcInflector::camelize($cache_backend);

        if (!class_exists($cache_backend_cls)) {
            throw new lcConfigException('Invalid cache backend set in configuration', 2);
        }

        $this->cache_backend = new $cache_backend_cls();

        if (!($this->cache_backend instanceof lcCacheStore)) {
            throw new lcConfigException('Invalid cache backend set in configuration', 3);
        }

        // internal storage
        $this->internal_storage = [];
        $this->should_use_internal_storage = isset($this->configuration['cache.use_internal_storage']) ?
            (bool)$this->configuration['cache.use_internal_storage'] : false;

        // init from the current configuration
        $this->cache_backend->setOptions((array)$this->configuration['cache']);

        // global application / project namespace prefix
        $this->namespace_prefix = isset($this->configuration['cache.namespace']) ? ((string)$this->configuration['cache.namespace']) . '_' : 'lc_';

        // append configuration unique id - when it changes (config changes) - cache will be invalidated
        $this->namespace_prefix = $this->namespace_prefix . $this->configuration->getUniqueProjectId() . '_';

        assert(isset($this->namespace_prefix));
    }

    public function shutdown()
    {
        $this->cache_backend = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        return [
            'namespace_prefix' => $this->namespace_prefix,
        ];
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function clear()
    {
        $this->cache_backend->clear();

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

    public function getCachingSystem()
    {
        return $this->cache_backend->getBackend();
    }

    public function setDbCache($namespace, $key, $value = null, $lifetime = null)
    {
        $key = $namespace . ':' . $key;
        return $this->set($key, $value, [
            'lifetime' => $lifetime,
        ]);
    }

    public function set($key, $value = null, array $options = [])
    {
        $all_kv = [];

        if ($this->default_lifetime && !isset($options['lifetime'])) {
            $options['lifetime'] = $this->default_lifetime;
        }

        if (is_array($key)) {

            foreach ($key as $kk) {

                $kk_prev = $kk;
                $kk = (string)$kk;

                if (!$kk) {
                    throw new lcInvalidArgumentException('Invalid params');
                }

                $kk = $this->keyWithNamespace($kk);

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

            $all_kv[$key] = $value;
        }

        // internal storage
        $use_internal_storage = $this->should_use_internal_storage;

        $res = false;

        foreach ($all_kv as $akey => $avalue) {

            $flags = isset($flags) ? $flags : 0;
            $res = $this->cache_backend->set($akey, $avalue, $options);

            if ($use_internal_storage) {
                $this->internal_storage[$akey] = $avalue;
            }

            unset($akey, $avalue);
        }

        // throw an exception only if debugging
        if (!$res && DO_DEBUG) {
            throw new lcSystemException('Cannot write data to cache, key: ' . $key);
        }

        return $res;
    }

    protected function keyWithNamespace($key)
    {
        return $this->namespace_prefix . $key;
    }

    public function removeDbCache($namespace, $key)
    {
        $key = $namespace . ':' . $key;
        $this->remove($key);
    }

    public function remove($key)
    {
        $namespaced_key = $this->keyWithNamespace($key);

        $this->cache_backend->remove($namespaced_key);

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

        try {
            if (count($keys) > 1) {
                if ($this->cache_backend instanceof iCacheMultiStorage) {
                    // clear out the keys which we already have
                    foreach ($keys as $key1) {
                        if (isset($results[$unnamespaced_keys[$key1]])) {
                            unset($keys[$key1]);
                        }

                        unset($key1);
                    }

                    // fetch from cache
                    $cas = null;
                    $values = $this->cache_backend->getMulti($keys);

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

                        $value = $this->cache_backend->get($key1);

                        // internal storage writeback
                        if ($use_internal_storage) {
                            $this->internal_storage[$key1] = $value;
                        }

                        $results[$unnamespaced_keys[$key1]] = $value;

                        unset($value);
                        unset($key1);
                    }
                }

            } else if (!isset($results[$unnamespaced_keys[$keys[0]]])) {
                $key1 = $keys[0];

                $value = $this->cache_backend->get($key1);

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
        return $this->cache_backend->has($key);
    }

    protected function makeSafeKey($key)
    {
        return md5($key);
    }

    public function count()
    {
        //
    }
}
