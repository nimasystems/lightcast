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

class lcAPC extends lcCacheStore
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var bool
     */
    private $apc_exists_available;

    /**
     * @var bool
     */
    private $has_apcu;

    /**
     * @throws lcSystemException
     */
    public function __construct()
    {
        parent::__construct();

        // check for apc
        $has_apc = false;

        if (function_exists('apc_fetch')) {
            $has_apc = true;
        } else if (function_exists('apcu_fetch')) {
            $has_apc = true;
            $this->has_apcu = true;
        }

        if (!$has_apc) {
            throw new lcSystemException('APC/APCU is not available');
        }

        // apc_exists is available after (PECL apc >= 3.1.4)
        if (function_exists('apc_exists') ||
            function_exists('apcu_exists')) {
            $this->apc_exists_available = true;
        }

        $this->prefix = 'lc_';
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function getStats()
    {
        return false;
    }

    protected function apcAdd($key, $value, $ttl = null)
    {
        if ($this->has_apcu) {
            return apcu_add($key, $value, $ttl);
        } else {
            return apc_add($key, $value, $ttl);
        }
    }

    protected function apcDelete($key)
    {
        if ($this->has_apcu) {
            return apcu_delete($key);
        } else {
            return apc_delete($key);
        }
    }

    protected function apcFetch($key)
    {
        if ($this->has_apcu) {
            return apcu_fetch($key);
        } else {
            return apc_fetch($key);
        }
    }

    protected function apcExists($key)
    {
        if ($this->has_apcu) {
            return $this->apc_exists_available ? apcu_exists($key) : apcu_fetch($key);
        } else {
            return $this->apc_exists_available ? apc_exists($key) : apc_fetch($key);
        }
    }

    protected function apcClearCache()
    {
        if ($this->has_apcu) {
            return apcu_clear_cache();
        } else {
            return apc_clear_cache();
        }
    }

    /**
     * @param $key
     * @param null $value
     * @param array $options
     * @return bool
     */
    public function set($key, $value = null, array $options = [])
    {
        $key_prefixed = $this->prefix . $key;

        // apc persistently stores the value until it expires or is manually removed
        // so it must be removed first in order to see the live changes on the next load
        if (1) {
            $this->remove($key);
        }

        return $this->apcAdd($key_prefixed, $value,
            isset($options['lifetime']) ? $options['lifetime'] : null
        );
    }

    /**
     * @param $key
     * @return bool|string[]
     */
    public function remove($key)
    {
        $key = $this->prefix . $key;
        return $this->apcDelete($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->prefix . $key;
        return $this->apcFetch($key);
    }

    /**
     * @param $key
     * @return bool|string[]
     */
    public function has($key)
    {
        $key = $this->prefix . $key;
        return $this->apcExists($key);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return $this->apcClearCache();
    }

    /**
     * @return null
     */
    public function getBackend()
    {
        return null;
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
        return false;
    }
}