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
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcAPC.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcAPC extends lcSysObj implements iCacheStorage
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $apc_exists_available;

    /**
     * @throws lcSystemException
     */
    public function __construct()
    {
        parent::__construct();
        
        // check for apc
        if (!function_exists('apc_fetch')) {
            throw new lcSystemException('APC is not available');
        }

        // apc_exists is available after (PECL apc >= 3.1.4)
        if (function_exists('apc_exists')) {
            $this->apc_exists_available = true;
        }

        $this->prefix = 'lc_';
    }

    /**
     * @return bool
     */
    public function getStats()
    {
        return false;
    }

    /**
     * @param $key
     * @param null $value
     * @param null $lifetime
     * @param null $other_flags
     * @return bool
     */
    public function set($key, $value = null, $lifetime = null, $other_flags = null)
    {
        $key_prefixed = $this->prefix . $key;

        // apc persistently stores the value until it expires or is manually removed
        // so it must be removed first in order to see the live changes on the next load
        if (1) {
            $this->remove($key);
        }

        return apc_add($key_prefixed, $value, $lifetime);
    }

    /**
     * @param $key
     * @return bool|string[]
     */
    public function remove($key)
    {
        $key = $this->prefix . $key;
        return apc_delete($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->prefix . $key;
        return apc_fetch($key);
    }

    /**
     * @param $key
     * @return bool|string[]
     */
    public function has($key)
    {
        $apc_exists_available = $this->apc_exists_available;

        $key = $this->prefix . $key;
        $has = $apc_exists_available ? apc_exists($key) : (bool)apc_fetch($key);

        return $has;
    }

    /**
     * @return bool
     */
    public function clear()
    {
        return apc_clear_cache();
    }

    /**
     * @return null
     */
    public function getBackend()
    {
        return null;
    }
}