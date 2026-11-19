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
 * Redis cache backend on top of predis/predis.
 *
 * Works with predis ^1.1 and ^2.0: everything used here (the Client constructor taking connection
 * parameters + an options array, get / set / setex / del / flushall) has the same signature and
 * return types in both majors (predis 2 requires PHP >= 7.2; its breaking changes - removed
 * Autoloader / Profile classes, PHP 5 support, deprecated commands - do not touch this class).
 * Security audit 2026-08-14, V15.2.1 (F108): predis 1 is end-of-life.
 */
class lcRedis extends lcCacheStore
{
    /**
     * @var string[]
     */
    protected $servers = [];

    /**
     * @var string[]
     */
    protected $options = [];

    /** @var Predis\Client */
    protected $backend;

    /**
     * @param string[] $options
     * @return lcRedis
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    public function addServer($connect_string)
    {
        return $this->servers[] = $connect_string;
    }

    /**
     * Returns the predis client, created on first use.
     *
     * A single server is passed as one connection string; several are passed as an array, which
     * predis 1 and 2 both treat as a client-side sharded cluster.
     *
     * @return Predis\Client
     * @throws lcConfigException When no Redis server is configured.
     */
    public function getBackend()
    {
        if (!$this->backend) {
            $config_servers = isset($this->options['servers']) ? (array)$this->options['servers'] : [];
            $servers = array_values(array_filter(array_merge((array)$this->servers, $config_servers)));

            if (!$servers) {
                throw new lcConfigException('No Redis server configured (cache.servers)');
            }

            $this->backend = new Predis\Client(count($servers) > 1 ? $servers : $servers[0],
                (isset($this->options['backend_options']) ? $this->options['backend_options'] : null));
        }
        return $this->backend;
    }

    /**
     * Stores a value.
     *
     * @param string $key Cache key.
     * @param mixed $value Value (serialized; falsy values are stored as an empty string).
     * @param array $options `lifetime` in seconds; missing or <= 0 means no expiry.
     * @return mixed The predis status response (truthy on success).
     */
    public function set($key, $value = null, array $options = [])
    {
        $lifetime = isset($options['lifetime']) ? (int)$options['lifetime'] : 0;
        $value = $value ? serialize($value) : '';

        // SETEX rejects a non-positive TTL ("invalid expire time"), which used to make every
        // write without a lifetime fail; store those without an expiry instead
        if ($lifetime > 0) {
            return $this->getBackend()->setex($key, $lifetime, $value);
        }

        return $this->getBackend()->set($key, $value);
    }

    public function remove($key)
    {
        return $this->getBackend()->del($key);
    }

    public function has($key)
    {
        return $this->get($key) != null;
    }

    public function get($key)
    {
        $v = $this->getBackend()->get($key);
        return $v ? @unserialize($v) : $v;
    }

    public function clear()
    {
        return $this->getBackend()->flushall();
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
        return $this->getBackend();
    }
}
