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

class lcLocalCacheManager extends lcSysObj implements iDebuggable, iProvidesCapabilities
{
    const DEFAULT_CACHE_TTL = 7200;
    /** @var lcCacheStore */
    protected $cache;
    protected $cache_enabled = true;
    protected $cache_ttl;
    /** @var array */
    protected $cacheable_objects;
    private $cache_written;
    private $use_class_cache = true;

    public function __construct()
    {
        parent::__construct();

        $this->cacheable_objects = array();
        $this->cache_ttl = self::DEFAULT_CACHE_TTL;
    }

    public function initialize()
    {
        parent::initialize();

        $this->event_dispatcher->connect('local_cache.register', $this, 'onRegisterCacheableObject');
        $this->event_dispatcher->connect('local_cache.unregister', $this, 'onUnregisterCacheableObject');
    }

    public function shutdown()
    {
        // write all caches
        $this->writeObjectCaches();

        $this->cacheable_objects =
        $this->cache =
            null;

        parent::shutdown();
    }

    private function writeObjectCaches()
    {
        if ($this->cache_written) {
            return;
        }

        $cacheable_objects = $this->cacheable_objects;
        $cache = $this->cache;

        if (!$cache || !$this->cache_enabled) {
            return;
        }

        // set a marker so we are protected against double writes!
        $this->cache_written = true;

        if ($cacheable_objects && is_array($cacheable_objects)) {
            foreach ($cacheable_objects as $class_name => $data) {
                $key_prefix = $data['key'];

                /** @var iCacheable $object */
                $object = $data['object'];

                $key = $this->getICacheableCacheKey($class_name, $key_prefix);

                try {
                    // callback the object
                    $cached_data = $object->writeClassCache();

                    if ($cached_data && is_array($cached_data)) {
                        $set = $cache->set($key, $cached_data, $this->cache_ttl);

                        if (!$set) {
                            throw new lcIOException('Setting cache to store failed');
                        }

                        if (DO_DEBUG) {
                            $this->debug('Class \'' . $class_name . '\' (key: ' . $key . ') wrote its local caches, array objects: ' . count($cached_data));
                        }
                    }

                    unset($data, $class_name, $object, $cached_data, $key);
                } catch (Exception $e) {
                    if (DO_DEBUG) {
                        throw new lcSystemException('Could not write class cache to storage (' . $key . '): ' .
                            $e->getMessage(),
                            $e->getCode(),
                            $e);
                    }

                    $this->err('Could not write class cache (' . $class_name . '/' . $key . '): ' . $e->getMessage());
                }
            }
        }
    }

    private function getICacheableCacheKey($class_name, $key_prefix)
    {
        $unique_id = $this->configuration->getUniqueId();
        $key = $unique_id . '_' . $key_prefix . '_' . $class_name;

        return $key;
    }

    public function getCapabilities()
    {
        return array(
            'cache'
        );
    }

    public function getDebugInfo()
    {
        $debug = array(
            'cache_type' => ($this->cache ? get_class($this->cache) : null),
            'cacheable_objects' => (is_array($this->cacheable_objects) ? array_keys($this->cacheable_objects) : null)
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getCacheTtl()
    {
        return $this->cache_ttl;
    }

    public function setCacheTtl($ttl = self::DEFAULT_CACHE_TTL)
    {
        $this->cache_ttl = (int)$ttl;
    }

    public function getCacheEnabled()
    {
        return $this->cache_enabled;
    }

    public function setCacheEnabled($enabled = true)
    {
        $this->cache_enabled = $enabled;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(iCacheStorage $cache = null)
    {
        $this->cache = $cache;
    }

    public function getUseClassCache()
    {
        return $this->use_class_cache;
    }

    public function setUseClassCache($use_class_cache = true)
    {
        $this->use_class_cache = $use_class_cache;
    }

    public function onRegisterCacheableObject(lcEvent $event)
    {
        $params = $event->params;
        $key = isset($params['key']) ? (string)$params['key'] : 'default';

        $this->registerCacheableObject($event->getSubject(), $key);
    }

    public function registerCacheableObject(iCacheable $object, $key)
    {
        $class_name = get_class($object);
        $this->cacheable_objects[$class_name] = array('key' => $key, 'object' => $object);

        // read cache
        $key = $this->getICacheableCacheKey($class_name, $key);

        $cache = $this->cache;

        if (!$cache || !$this->cache_enabled) {
            return;
        }

        try {
            $cached_data = $cache->get($key);

            // callback the object
            if ($cached_data && is_array($cached_data)) {
                $ret = $object->readClassCache($cached_data);

                if ($ret) {
                    if (DO_DEBUG) {
                        $this->debug('Class \'' . $class_name . '\' (key: ' . $key . ') read its local caches, array objects: ' . count($cached_data));
                    }
                }
            }

            unset($class_name, $object, $cached_data, $key);
        } catch (Exception $e) {
            if (DO_DEBUG) {
                throw $e;
            }

            $this->err('Could not read class cache (' . $key . '): ' . $e->getMessage());
        }
    }

    public function onUnregisterCacheableObject(lcEvent $event)
    {
        $this->unregisterCacheableObject($event->getSubject());
    }

    public function unregisterCacheableObject(iCacheable $object)
    {
        $class_name = get_class($object);

        if (isset($this->cacheable_objects[$class_name])) {
            unset($this->cacheable_objects[$class_name]);

            return true;
        }

        return false;
    }
}