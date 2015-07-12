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

class lcWebsiteConfigurationKey extends lcObj
{
    /** @var string The configuration key */
    protected $config_key;

    /** @var string type of the configuration (string,int,float,double,bool,enum */
    protected $config_type;

    /** @var string Human readable title */
    protected $title;

    /** @var mixed The default value */
    protected $default_value;

    /** @var bool Yes, if the configuration could be publically exported */
    protected $is_public;

    public static function getInstance($config_key, $config_type, $title, $default_value = null)
    {
        $instance = new lcWebsiteConfigurationKey($config_key, $config_type);
        $instance
            ->setTitle($title)
            ->setDefaultValue($default_value);
        return $instance;
    }

    public function __construct($config_key, $config_type)
    {
        parent::__construct();

        $this->config_key = $config_key;
        $this->config_type = $config_type;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return lcWebsiteConfigurationKey
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return $this->config_key;
    }

    /**
     * @param string $config_key
     * @return lcWebsiteConfigurationKey
     */
    public function setConfigKey($config_key)
    {
        $this->config_key = $config_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfigType()
    {
        return $this->config_type;
    }

    /**
     * @param string $config_type
     * @return lcWebsiteConfigurationKey
     */
    public function setConfigType($config_type)
    {
        $this->config_type = $config_type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default_value;
    }

    /**
     * @param mixed $default_value
     * @return lcWebsiteConfigurationKey
     */
    public function setDefaultValue($default_value)
    {
        $this->default_value = $default_value;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublic()
    {
        return $this->is_public;
    }

    /**
     * @param boolean $is_public
     * @return lcWebsiteConfigurationKey
     */
    public function setIsPublic($is_public)
    {
        $this->is_public = $is_public;
        return $this;
    }
}