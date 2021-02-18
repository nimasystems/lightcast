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

abstract class lcConfigHandler extends lcObj
{
    /** @var iConfigDataProvider */
    protected $data_provider;

    /** @var array */
    protected $options = [];

    protected $environments = [];

    /**
     * @param $handler_type
     * @return lcConfigHandler
     * @throws lcSystemException
     */
    public static function & getConfigHandler($handler_type): lcConfigHandler
    {
        $handler_type = lcInflector::camelize($handler_type, false);

        assert((bool)$handler_type);

        $clname = 'lc' . $handler_type . 'ConfigHandler';

        if (!class_exists($clname)) {
            throw new lcSystemException('Config Handler ' . $handler_type . ' does not exist');
        }

        $handler = new $clname;

        // check class type
        if (!$handler instanceof lcConfigHandler) {
            throw new lcSystemException('Invalid configuration handler: ' . $handler_type . '. Class does not inherit from lcConfigHandler');
        }

        return $handler;
    }

    public function getDataProvider(): iConfigDataProvider
    {
        return $this->data_provider;
    }

    public function setDataProvider(iConfigDataProvider $data_provider)
    {
        $this->data_provider = $data_provider;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function setEnvironments(array $environments = [])
    {
        $this->environments = $environments;
    }

    public function getConfigurationData($config_key, $environment, array $source_defaults = null, array $config_vars = []): array
    {
        if (!$this->data_provider) {
            throw new lcConfigException('No data provider set');
        }

        if ($environment == lcEnvConfigHandler::ENV_ALL) {
            throw new lcConfigException('Environment \'all\' is special and cannot be set as the currently active one!');
        }

        if ($environment && (!is_array($this->environments) || !in_array($environment, $this->environments))) {
            throw new lcConfigException('Environment \'' . $environment . '\' was set as the currently active one but it is not defined in configuration');
        }

        // get defaults and merge both
        $defaults = (array)$this->getDefaultValues();

        // acquire data from config file
        $data = [];

        $options = $this->options;
        $dirs = isset($options['dirs']) && is_array($options['dirs']) ? $options['dirs'] : null;

        if ($dirs) {
            // try to load configurations from specific dirs
            // stop on first successful load
            foreach ($dirs as $dir) {
                $opts = $this->options;
                $opts['dir'] = $dir;
                $data = $this->data_provider->readConfigData($config_key, $opts, $config_vars);

                if ($data && is_array($data)) {
                    break;
                }

                unset($dir, $opts);
            }
        }

        $data = $data ? $data : [];

        // allow subclassers to alter it
        $data = (array)$this->preReadConfigData($environment, $data);

        // merge with source defaults / subclasser defaults
        $data = (array)lcArrays::mergeRecursiveDistinct($source_defaults, $defaults, $data);

        // unset any environment based configs now after we've got them
        if ($this->environments) {
            foreach ($this->environments as $env) {
                unset($data[$env]);
                unset($env);
            }

            unset($data[lcEnvConfigHandler::ENV_ALL]);
        }

        // allow subclassers to alter it
        $data = (array)$this->postReadConfigData($environment, $data);

        $data = lcArrays::arrayFilterDeep($data, true);

        return $data;
    }

    public function getDefaultValues()
    {
        return null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function preReadConfigData($environment, array $data)
    {
        return $data;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function postReadConfigData($environment, array $data)
    {
        return $data;
    }
}
