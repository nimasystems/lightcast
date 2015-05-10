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
 * @changed $Id: lcXCache.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcXCache extends lcSysObj implements iCacheStorage
{
    private $prefix;

    public function __construct()
    {
        parent::__construct();

        // check for xcache
        if (!function_exists('xcache_get')) {
            throw new lcSystemException('XCache is not available');
        }

        $this->prefix = 'lc_';
    }

    public function getStats()
    {
        return false;
    }

    public function set($key, $value = null, $lifetime = null, $other_flags = null)
    {
        $key = $this->prefix . $key;
        return xcache_set($key, $value, $lifetime);
    }

    public function remove($key)
    {
        $key = $this->prefix . $key;
        return xcache_unset($key);
    }

    public function get($key)
    {
        $key = $this->prefix . $key;
        return xcache_get($key);
    }

    public function has($key)
    {
        $key = $this->prefix . $key;
        $has = (bool)xcache_get($key) ? true : false;

        return $has;
    }

    public function clear()
    {
        return false;
    }

    public function getBackend()
    {
        return null;
    }
}