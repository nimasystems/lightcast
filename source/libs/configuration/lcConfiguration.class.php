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

abstract class lcConfiguration extends lcSysObj implements ArrayAccess, iCacheable, iDebuggable
{
    const DEFAULT_CONFIG_DATA_PROVIDER = 'lcYamlConfigDataProvider';

    /*
     * Default configuration environment
     */
    protected $environment = lcEnvConfigHandler::ENV_PROD;

    /*
     * Default configuration environments
     */
    protected $configuration = [];
    protected $base_config_dir;
    private $environments = [
        lcEnvConfigHandler::ENV_DEV,
        lcEnvConfigHandler::ENV_PROD,
        lcEnvConfigHandler::ENV_TEST,
    ];

    protected static $shared_config_parser_vars = [];

    public function initialize()
    {
        parent::initialize();

        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'config_providers' . DS . 'iConfigDataProvider.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'config_providers' . DS . 'lcYamlConfigDataProvider.class.php';

        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcEnvConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcAppPluginsConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcAppSecurityConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcAppSettingsConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcConsoleConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcDatabasesConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcLoadersConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcPluginConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcPluginViewConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcProjectConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcRoutingConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcViewConfigHandler.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'handlers' . DS . 'lcWebServiceConfigHandler.class.php';

        // read the configuration (unless already loaded - by class cache for
        // example)
        if (!$this->configuration) {
            $this->configuration = $this->loadConfigurationData();
        }
    }

    public function loadData($force = false)
    {
        // read the configuration (unless already loaded - by class cache for
        // example)
        if (!$this->configuration || $force) {
            $this->configuration = $this->loadConfigurationData();
        }

        $this->executeAfterDataLoaded();
    }

    protected function executeAfterDataLoaded()
    {
        //
    }

    protected function loadConfigurationData()
    {
        return $this->loadConfigurationFromHandleMap($this->getConfigHandleMap());
    }

    protected function loadConfigurationFromHandleMap(array $config_handle_map)
    {
        $map = $config_handle_map;

        if (!$map || !is_array($map)) {
            return false;
        }

        $configuration = [];

        $base_config_dir = $this->getBaseConfigDir();

        foreach ($map as $options) {
            if (!is_array($options) || !isset($options['handler'], $options['config_key'])) {
                throw new lcConfigException('Invalid configuration (' . get_class($this) . ') - missing handler/config_key');
            }

            $config_handler_type = (string)$options['handler'];

            $handler = lcConfigHandler::getConfigHandler($config_handler_type);

            $config_key = (string)$options['config_key'];
            $defaults = isset($options['defaults']) ? (array)$options['defaults'] : null;

            $project_config_dir = $this->getProjectConfigDir();
            $project_config_key_dir = $project_config_dir ? ($project_config_dir[0] == '/' ? $project_config_dir :
                ($base_config_dir ? $base_config_dir . DS . $project_config_dir : null)) : null;

            // merge some additional configuration based options
            $nd = array_filter([
                $project_config_key_dir,
                $this->getConfigDir(),
            ]);
            $dirs = isset($options['dirs']) && is_array($options['dirs']) ? array_merge(array_values($options['dirs']), array_values($nd)) : $nd;
            $options['dirs'] = $dirs;

            $handler->setOptions($options);
            $handler->setDataProvider($this->getConfigDataProviderInstance());
            $handler->setEnvironments($this->environments);

            try {
                $handler_configuration = $handler->getConfigurationData($config_key, $this->environment, $defaults, $this->getPreparedConfigParserVars());
            } catch (Exception $e) {
                throw new lcConfigException('Error while loading configuration from handler: ' . $config_handler_type . ', Config Key: ' . $config_key . ': ' . $e->getMessage(), $e->getCode(), $e);
            }

            if (null !== $handler_configuration && !is_array($handler_configuration)) {
                assert(false);
                continue;
            }

            // merge with current configuration
            $configuration = lcArrays::mergeRecursiveDistinct($configuration, $handler_configuration);

            unset($options, $handler, $handler_configuration, $config_key);
        }

        unset($map);

        // special overriding vars
        $configuration['debug'] = (bool)DO_DEBUG;

        return $configuration;
    }

    protected function getPreparedConfigParserVars(): array
    {
        $vars_prepared = [];
        $vars = (array)$this->getConfigParserVars();

        foreach ($vars as $key => $val) {
            $vars_prepared['{{' . $key . '}}'] = $val;
        }

        return $vars_prepared + self::$shared_config_parser_vars;
    }

    /**
     * @return array
     */
    abstract public function getConfigParserVars();

    public function getBaseConfigDir()
    {
        return $this->base_config_dir;
    }

    public function setBaseConfigDir($config_dir)
    {
        $this->base_config_dir = $config_dir;
    }

    abstract public function getProjectConfigDir();

    abstract public function getConfigDir();

    protected function getConfigDataProviderInstance()
    {
        // subclassers may return a different data provider here
        return new lcYamlConfigDataProvider();
    }

    public function getConfigHandleMap()
    {
        return null;
    }

    public function shutdown()
    {
        $this->configuration = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        return ['configuration' => $this->configuration,];
    }

    public function getShortDebugInfo()
    {
        return ['environment' => $this->environment,];
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    public function getEnvironments()
    {
        return $this->environments;
    }

    public function setEnvironments(array $environments = null)
    {
        $this->environments = $environments;
    }

    public function getData()
    {
        return $this->configuration;
    }

    public function getConfigurationData()
    {
        return $this->getConfiguration();
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getAll()
    {
        return $this->configuration;
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function has($name)
    {
        $tmp = null;

        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection OnlyWritesOnParameterInspection */
        $configuration = $this->configuration;

        $arr_str = '$configuration[\'' . str_replace('.', '\'][\'', $name) . '\']';

        $eval_str = '$tmp = isset(' . $arr_str . ');';

        eval($eval_str);

        return $tmp;
    }

    // @codingStandardsIgnoreStart

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    // @codingStandardsIgnoreEnd

    public function get($name)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection OnlyWritesOnParameterInspection */
        $configuration = $this->configuration;

        $arr_str = '$configuration[\'' . str_replace('.', '\'][\'', $name) . '\']';
        $arr_str = '(isset(' . $arr_str . ') ? ' . $arr_str . ' : null)';

        $tmp = null;
        $eval_str = '$tmp = ' . $arr_str . ';';

        eval($eval_str);

        return $tmp;
    }

    public function offsetSet($name, $value)
    {
        return $this->set($name, $value);
    }

    public function set($name, /** @noinspection PhpUnusedParameterInspection */
                        $value = null)
    {
        $arr_str = '$this->configuration[\'' . str_replace('.', '\'][\'', $name) . '\']';
        $eval_str = $arr_str . ' = $value;';

        return eval($eval_str);
    }

    public function offsetUnset($name)
    {
        return $this->remove($name);
    }

    public function remove($name)
    {
        $arr_str = '$this->configuration[\'' . str_replace('.', '\'][\'', $name) . '\']';
        $eval_str = 'unset(' . $arr_str . ');';
        return eval($eval_str);
    }

    public function __toString()
    {
        $cfg = $this->configuration;

        if (null === $cfg) {
            return '';
        }

        return (string)e($cfg, true);
    }

    public function writeClassCache()
    {
        return ['configuration' => $this->configuration];
    }

    public function readClassCache(array $cached_data)
    {
        $this->configuration = isset($cached_data['configuration']) ? $cached_data['configuration'] : null;
    }
}
