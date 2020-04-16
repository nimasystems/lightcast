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

    public function getBackend()
    {
        if (!$this->backend) {
            $config_servers = isset($this->options['servers']) ? (array)$this->options['servers'] : [];
            $servers = array_merge((array)$this->servers, $config_servers);
            $this->backend = new Predis\Client(count($servers) > 1 ? $servers : $servers[0],
                (isset($this->options['backend_options']) ? $this->options['backend_options'] : null));
        }
        return $this->backend;
    }

    public function set($key, $value = null, array $options = [])
    {
        $lifetime = isset($options['lifetime']) ? $options['lifetime'] : null;
        return $this->getBackend()->setex($key, $lifetime, $value ? serialize($value) : '');
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