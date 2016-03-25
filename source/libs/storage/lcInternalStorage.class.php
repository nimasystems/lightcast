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

class lcInternalStorage extends lcStorage implements iDebuggable
{
    const DEFAULT_NAMESPACE = 'global';

    protected $storage;

    public function initialize()
    {
        parent::initialize();

        $this->storage = array();
    }

    public function shutdown()
    {
        $this->storage = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        return false;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getSessionId()
    {
        return null;
    }

    public function has($key, $namespace = null)
    {
        return $this->get($key, $namespace) ? true : false;
    }

    /**
     * @param string $key
     * @param string $namespace
     * @return mixed
     */
    public function get($key, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return null;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        $res = isset($this->storage[$n][$key]) ? $this->storage[$n][$key] : null;

        return $res;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $namespace
     * @return mixed
     */
    public function set($key, $value = null, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return null;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        $this->storage[$n][$key] = $value;
    }

    /**
     * @param string $key
     * @param string $namespace
     */
    public function remove($key, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return;
        }

        unset($this->storage[$n][$key]);
    }

    public function clear($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return;
        }

        unset($this->storage[$n]);
    }

    public function clearAll()
    {
        $this->storage = array();
    }

    public function hasValues($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        $has = count($this->storage) ? true : false;

        return $has;
    }

    public function count($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        $count = count($this->storage[$n]);

        return $count;
    }

    public function getAll($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        $res = $this->storage[$n];

        return $res;
    }

    public function getBackendData()
    {
        return $this->storage;
    }

    public function getNamespaces()
    {
        return array_keys($this->storage);
    }
}
