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

abstract class lcWebConfiguration extends lcApplicationConfiguration
{
    const CONTROLLER_ASSETS_DIR = 'templates';
    const DEFAULT_CHARSET = 'utf-8';

    protected $unique_id_suffix;
    protected $app_dir;

    public function initialize()
    {
        $this->app_dir = $this->project_configuration->getProjectDir() . DS . 'applications' . DS . $this->getApplicationName();

        parent::initialize();

        // set charset
        $charset = isset($this['view.charset']) ? (string)$this['view.charset'] : self::DEFAULT_CHARSET;
        ini_set('default_charset', $charset);
    }

    public function shutdown()
    {
        $this->app_dir = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = ['app_dir' => $this->app_dir];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getConfigDir()
    {
        return $this->getAppConfigDir();
    }

    public function getAppConfigDir()
    {
        return $this->app_dir . DS . 'config';
    }

    public function getProjectConfigDir()
    {
        return 'applications' . DS . $this->getApplicationName();
    }

    public function getConfigHandleMap()
    {
        $parent_map = (array)parent::getConfigHandleMap();

        // maps the configuration values to handlers
        $config_map = [
            [
                'handler' => 'loaders',
                'config_key' => 'loaders'
            ],
            [
                'handler' => 'routing',
                'config_key' => 'routing'
            ],
            [
                'handler' => 'app_security',
                'config_key' => 'security'
            ],
            [
                'handler' => 'app_plugins',
                'config_key' => 'plugins'
            ],
            [
                'handler' => 'app_settings',
                'config_key' => 'settings'
            ],
            [
                'handler' => 'view',
                'config_key' => 'view'
            ]
        ];

        $app_map = array_merge($parent_map, $config_map);

        unset($parent_map, $config_map);

        return $app_map;
    }

    public function getClientsideJavascript()
    {
        return (bool)$this['view.clientside_js'];
    }

    public function getActionFormLocations()
    {
        $parent_locations = $this->project_configuration ? $this->project_configuration->getActionFormLocations() : [];

        // app modules
        $controller_locations = [[
            'context_type' => lcSysObj::CONTEXT_APP,
            'context_name' => $this->getApplicationName(),
            'path' => $this->app_dir . DS . 'forms'
        ],];

        $locations = array_merge((array)$parent_locations, $controller_locations);

        return $locations;
    }

    public function getControllerModuleLocations()
    {
        $parent_locations = $this->project_configuration ? $this->project_configuration->getControllerModuleLocations() : [];

        // app modules
        $controller_locations = [[
            'context_type' => lcSysObj::CONTEXT_APP,
            'context_name' => $this->getApplicationName(),
            'path' => $this->app_dir . DS . 'modules'
        ],];

        $locations = array_merge((array)$parent_locations, $controller_locations);

        return $locations;
    }

    public function getAppDir()
    {
        return $this->app_dir;
    }

    public function getLayoutsDir()
    {
        return $this->app_dir . DS . 'layouts';
    }

    protected function loadConfigurationData()
    {
        // read the configuration
        $config_data = parent::loadConfigurationData();

        // reset loaders to their defaults in case the property is set
        if ($this->should_use_default_loaders) {
            $lhandler = new lcLoadersConfigHandler();
            $ldata = $lhandler->getDefaultValues();

            if ($ldata && is_array($ldata) && isset($config_data['loaders'])) {
                $config_data['loaders'] = $ldata;
            }

            unset($lhandler, $ldata);
        }

        return $config_data;
    }
}
