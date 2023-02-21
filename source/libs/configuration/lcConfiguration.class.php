<?php
declare(strict_types=1);

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
 *
 */
abstract class lcConfiguration extends lcSysObj implements ArrayAccess, iCacheable, iDebuggable
{
    public const DEFAULT_CONFIG_DATA_PROVIDER = 'lcYamlConfigDataProvider';

    /*
     * Default configuration environment
     */
    protected string $environment = lcEnvConfigHandler::ENV_PROD;

    /*
     * Default configuration environments
     */
    protected $configuration = [];
    protected $base_config_dir;
    private array $environments = [
        lcEnvConfigHandler::ENV_DEV,
        lcEnvConfigHandler::ENV_PROD,
        lcEnvConfigHandler::ENV_TEST,
    ];

    protected static array $shared_config_parser_vars = [];

    public function initialize()
    {
        parent::initialize();

        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'providers' . DS . 'iConfigDataProvider.class.php';
        require_once ROOT . DS . 'source' . DS . 'libs' . DS . 'configuration' . DS . 'providers' . DS . 'lcYamlConfigDataProvider.class.php';

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

    /**
     * @param bool $force
     * @return void
     * @throws lcConfigException
     * @throws lcSystemException
     */
    public function loadData(bool $force = false)
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

    /**
     * @return array|false|mixed
     * @throws lcConfigException
     * @throws lcSystemException
     */
    protected function loadConfigurationData()
    {
        return $this->loadConfigurationFromHandleMap($this->getConfigHandleMap());
    }

    /**
     * @param array $config_handle_map
     * @return array|false|mixed
     * @throws lcConfigException
     * @throws lcSystemException
     */
    protected function loadConfigurationFromHandleMap(array $config_handle_map)
    {
        $map = $config_handle_map;

        if (!$map) {
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
                /*$this->getConfigDir(),*/
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
        $vars = $this->getConfigParserVars();

        foreach ($vars as $key => $val) {
            $vars_prepared['{{' . $key . '}}'] = $val;
        }

        return $vars_prepared + self::$shared_config_parser_vars;
    }

    /**
     * @return array
     */
    abstract public function getConfigParserVars(): array;

    /**
     * @return mixed
     */
    public function getBaseConfigDir()
    {
        return $this->base_config_dir;
    }

    /**
     * @param $config_dir
     * @return void
     */
    public function setBaseConfigDir($config_dir)
    {
        $this->base_config_dir = $config_dir;
    }

    /**
     * @return mixed
     */
    abstract public function getProjectConfigDir();

    /**
     * @return mixed
     */
    abstract public function getConfigDir();

    /**
     * @return lcYamlConfigDataProvider
     */
    protected function getConfigDataProviderInstance(): lcYamlConfigDataProvider
    {
        // subclassers may return a different data provider here
        return new lcYamlConfigDataProvider();
    }

    /**
     * @return ?array
     */
    public function getConfigHandleMap(): ?array
    {
        return null;
    }

    public function shutdown()
    {
        $this->configuration = null;

        parent::shutdown();
    }

    /**
     * @return array|array[]
     */
    public function getDebugInfo(): array
    {
        return ['configuration' => $this->configuration,];
    }

    /**
     * @return array
     */
    public function getShortDebugInfo(): array
    {
        return ['environment' => $this->environment,];
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @param $environment
     * @return void
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return array
     */
    public function getEnvironments(): array
    {
        return $this->environments;
    }

    public function setEnvironments(array $environments = null)
    {
        $this->environments = $environments;
    }

    /**
     * @return array|mixed
     */
    public function getData()
    {
        return $this->configuration;
    }

    /**
     * @return array|mixed
     */
    public function getConfigurationData()
    {
        return $this->getConfiguration();
    }

    /**
     * @return array|mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return array|mixed
     */
    public function getAll()
    {
        return $this->configuration;
    }

    /**
     * @param $offset
     * @return null
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param $offset
     * @return null
     */
    public function has($offset)
    {
        $tmp = null;

        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection OnlyWritesOnParameterInspection */
        $configuration = $this->configuration;

        $arr_str = '$configuration[\'' . str_replace('.', '\'][\'', $offset) . '\']';

        $eval_str = '$tmp = isset(' . $arr_str . ');';

        eval($eval_str);

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $tmp;
    }

    // @codingStandardsIgnoreStart

    /**
     * @param $offset
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    // @codingStandardsIgnoreEnd

    /**
     * @param $offset
     * @return null
     */
    public function get($offset)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        /** @noinspection OnlyWritesOnParameterInspection */
        $configuration = $this->configuration;

        $arr_str = '$configuration[\'' . str_replace('.', '\'][\'', $offset) . '\']';
        $arr_str = '(isset(' . $arr_str . ') ? ' . $arr_str . ' : null)';

        $tmp = null;
        $eval_str = '$tmp = ' . $arr_str . ';';

        eval($eval_str);

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $tmp;
    }

    /**
     * @param $offset
     * @param $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * @param $offset
     * @param $value
     * @return mixed
     */
    public function set($offset, /** @noinspection PhpUnusedParameterInspection */
                        $value = null)
    {
        $arr_str = '$this->configuration[\'' . str_replace('.', '\'][\'', $offset) . '\']';
        $eval_str = $arr_str . ' = $value;';

        return eval($eval_str);
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function remove($name)
    {
        $arr_str = '$this->configuration[\'' . str_replace('.', '\'][\'', $name) . '\']';
        $eval_str = 'unset(' . $arr_str . ');';
        return eval($eval_str);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $cfg = $this->configuration;

        if (null === $cfg) {
            return '';
        }

        return (string)e($cfg, true);
    }

    /**
     * @return array|array[]
     */
    public function writeClassCache(): array
    {
        return ['configuration' => $this->configuration];
    }

    public function readClassCache(array $cached_data)
    {
        $this->configuration = $cached_data['configuration'] ?? null;
    }
}
