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

class lcCookie extends lcObj
{
    const DEFAULT_PATH = '/';

    protected $name;
    protected $value;
    protected $path;
    protected $domain;
    protected $expires;
    protected $secure;

    public function __construct($name, $value = null, $path = self::DEFAULT_PATH, $domain = null, $expires = null, $secure = false)
    {
        parent::__construct();

        $name = (string)$name;
        // $value can be an array?
        $value = isset($value) ? $value : null;
        $path = isset($path) ? (string)$path : self::DEFAULT_PATH;
        $domain = isset($domain) ? (string)$domain : null;
        $expires = isset($expires) ? (int)$expires : null;
        $secure = isset($secure) ? (bool)$secure : false;

        $this->name = $name;
        $this->value = $value;
        $this->path = $path;
        $this->domain = $domain;
        $this->expires = $expires;
        $this->secure = $secure;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getExpiration()
    {
        return $this->expires;
    }

    public function isSecure()
    {
        return $this->secure;
    }

    public function setSecure($secure)
    {
        $this->secure = $secure;
    }

    public function setExpiration($expires)
    {
        $this->expires = (int)$expires;
    }

    public function __toString()
    {
        $str = "lcCookie: \n" .
            "Name: " . $this->name . "\n" .
            "Value: " . $this->value . "\n" .
            "Path: " . $this->path . "\n" .
            "Domain: " . $this->domain . "\n" .
            "Expires: " . $this->expires . "\n" .
            "Is Secure: " . $this->secure . "\n\n";

        return $str;
    }
}
