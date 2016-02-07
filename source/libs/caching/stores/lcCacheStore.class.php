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

abstract class lcCacheStore extends lcResidentObj implements ArrayAccess, iCacheStorage
{
    protected $default_lifetime;

    public function initialize()
    {
        parent::initialize();

        // set the default lifetime
        $this->default_lifetime = isset($this->configuration['cache']['default_lifetime']) ?
            (int)$this->configuration['cache']['default_lifetime'] : 0;
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getDefaultLifetime()
    {
        return $this->default_lifetime;
    }

    abstract public function hasValues();

    abstract public function count();

    public function offsetExists($name)
    {
        return $this->get($name) ? true : false;
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetSet($name, $value)
    {
        return $this->set($name, $value);
    }

    public function offsetUnset($name)
    {
        return $this->remove($name);
    }

    public function getBackend()
    {
        return $this->getCachingSystem();
    }

    abstract public function getCachingSystem();
}
