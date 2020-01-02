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

class lcConsoleConfiguration extends lcApplicationConfiguration
{
    const DEFAULT_APP_NAME = 'console';

    public function __construct($project_dir = null, lcProjectConfiguration $project_configuration = null)
    {
        if (!$project_dir) {
            throw new lcInvalidArgumentException('Invalid project dir');
        }

        parent::__construct($project_dir, $project_configuration);

        if ($this->project_configuration) {
            // shortcuts to enable debugging / disable plugins / disable loaders
            // / disable db while in CLI
            if (in_array('--disable-plugins', $_SERVER['argv'])) {
                $this->setShouldLoadPlugins(false);
            }

            if (in_array('--disable-db', $_SERVER['argv'])) {
                $this->should_disable_databases = true;
            }

            if (in_array('--disable-loaders', $_SERVER['argv'])) {
                $this->should_use_default_loaders = true;
            }

            if (in_array('--disable-models', $_SERVER['argv'])) {
                $this->should_disable_models = true;
            }

            if (in_array('--debug', $_SERVER['argv'])) {
                $this->project_configuration->setIsDebugging(true);
            }

            // pick a different environment
            foreach ((array)$_SERVER['argv'] as $v) {
                if (false !== strpos($v, '--config-env=')) {
                    $env = substr($v, strpos($v, '=') + 1, strlen($v));

                    if ($env) {
                        $this->setConfigEnvironment($env);
                    }

                    break;
                }
                unset($v);
            }
        }
    }

    public function getApplicationName()
    {
        return self::DEFAULT_APP_NAME;
    }

    public function getProjectConfigDir()
    {
        return null;
    }

    public function getConfigHandleMap()
    {
        $parent_map = (array)parent::getConfigHandleMap();

        // maps the configuration values to handlers
        $config_map = [[
                           'handler' => 'console',
                           'dirs' => [
                               $this->getBaseConfigDir(),
                               $this->getConfigDir(),
                           ],
                           'config_key' => 'console',
                       ],];

        $app_map = array_merge($parent_map, $config_map);

        unset($parent_map, $config_map);

        return $app_map;
    }

    public function getConfigDir()
    {
        return $this->getProjectDir() . DS . 'config';
    }

    protected function loadConfigurationData()
    {
        // read the configuration
        $config_data = parent::loadConfigurationData();

        // reset loaders to their defaults in case the property is set
        if ($this->should_use_default_loaders) {
            $lhandler = new lcConsoleConfigHandler();
            $ldata = $lhandler->getDefaultValues();

            if ($ldata && is_array($ldata) && isset($config_data['loaders']) && $config_data['loaders']) {
                $config_data['loaders'] = $ldata['loaders'];
            }

            unset($lhandler, $ldata);
        }

        // disable database configuration if requested
        if ($this->should_disable_databases) {
            unset($config_data['db']);
        }

        return $config_data;
    }
}
