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
 * @changed $Id: lcConfigHandler.class.php 1473 2013-11-17 10:38:32Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */

abstract class lcConfigHandler extends lcObj
{
    protected $data_provider;
    protected $options;
    protected $environments;

    public function getDefaultValues()
    {
        return null;
    }

    protected function preReadConfigData($environment, array $data)
    {
        fnothing($environment);
        return $data;
    }

    protected function postReadConfigData($environment, array $data)
    {
        fnothing($environment);
        return $data;
    }

    public function setDataProvider(iConfigDataProvider $data_provider)
    {
        $this->data_provider = $data_provider;
    }

    public function getDataProvider()
    {
        return $this->data_provider;
    }

    public static function & getConfigHandler($handler_type)
    {
        $handler_type = lcInflector::camelize($handler_type, false);

        assert((bool)$handler_type);

        $clname = 'lc' . $handler_type . 'ConfigHandler';

        if (!class_exists($clname))
        {
            throw new lcSystemException('Config Handler ' . $handler_type . ' does not exist');
        }

        $handler = new $clname;

        // check class type
        if (!$handler instanceof lcConfigHandler)
        {
            throw new lcSystemException('Invalid configuration handler: ' . $handler_type . '. Class does not inherit from lcConfigHandler');
        }

        return $handler;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setEnvironments(array $environments = null)
    {
        $this->environments = $environments;
    }

    public function getConfigurationData($config_key, $environment, array $source_defaults = null)
    {
        if (!$this->data_provider)
        {
            throw new lcConfigException('No data provider set');
        }

        assert(!is_null($environment));

        if ($environment == lcEnvConfigHandler::ENVIRONMENT_ALL)
        {
            throw new lcConfigException('Environment \'all\' is special and cannot be set as the currently active one!');
        }

        if ($environment && (!is_array($this->environments) || !in_array($environment, $this->environments)))
        {
            throw new lcConfigException('Environment \'' . $environment . '\' was set as the currently active one but it is not defined in configuration');
        }

        // get defaults and merge both
        $defaults = (array)$this->getDefaultValues();

        // acquire data from config file
        $data = array();

        $options = $this->options;
        $dirs = isset($options['dirs']) && is_array($options['dirs']) ? $options['dirs'] : null;

        if ($dirs)
        {
            // try to load configurations from specific dirs
            // stop on first successful load
            foreach ($dirs as $dir)
            {
                $opts = $this->options;
                $opts['dir'] = $dir;
                $data = $this->data_provider->readConfigData($config_key, $opts);

                if ($data && is_array($data))
                {
                    break;
                }

                unset($dir, $opts);
            }
        }

        $data = $data ? $data : array();

        // allow subclassers to alter it
        $data = (array)$this->preReadConfigData($environment, $data);

        // merge with source defaults / subclasser defaults
        $data = (array)lcArrays::mergeRecursiveDistinct($source_defaults, $defaults, $data);

        // unset any environment based configs now after we've got them
        if ($this->environments)
        {
            foreach ($this->environments as $env)
            {
                unset($data[$env]);
                unset($env);
            }

            unset($data[lcEnvConfigHandler::ENVIRONMENT_ALL]);
        }

        // allow subclassers to alter it
        $data = (array)$this->postReadConfigData($environment, $data);

        $data = lcArrays::arrayFilterDeep($data, true);
        $data = $data ? $data : null;

        return $data;
    }
}
?>