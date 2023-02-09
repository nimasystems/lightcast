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
class lcPluginManager extends lcSysObj implements iCacheable, iDebuggable, iEventObserver
{
    public const DEFAULT_PLUGIN_CONFIG_CLASS_NAME = 'lcPluginConfiguration';

    protected bool $should_load_plugins = true;

    /**
     * @var ?lcSystemComponentFactory
     */
    protected ?lcSystemComponentFactory $system_component_factory = null;

    /**
     * @var ?lcDatabaseModelManager
     */
    protected ?lcDatabaseModelManager $database_model_manager = null;

    /**
     * @var ?lcPlugin[]
     */
    protected ?array $plugins = null;

    /**
     * @var ?lcPluginConfiguration[]
     */
    protected ?array $plugin_configurations = null;

    /**
     * @var ?iSupportsAutoload[]
     */
    protected ?array $plugin_autoload_configurations = null;

    protected array $runtime_plugins = [];
    protected array $enabled_plugins = [];

    /**
     * @var ?lcRouting
     */
    protected ?lcRouting $routing = null;
    /**
     * @var ?lcApp
     */
    protected ?lcApp $app_context = null;

    private array $included_plugin_classes = [];
    private array $plugin_autostart_events = [];

    private ?array $autoload_class_map_file_exists_map = null;

    private ?string $plugin_webpath = null;

    public function initialize()
    {
        parent::initialize();

        $this->plugins = [];
        $this->plugin_configurations = [];

        $this->plugin_webpath = $this->configuration['plugins.webpath'];

        if ($this->plugin_webpath) {
            // fix it
            if (substr($this->plugin_webpath, strlen($this->plugin_webpath) - 1, strlen($this->plugin_webpath)) != '/') {
                $this->plugin_webpath .= '/';
            }
        }

        $this->enabled_plugins = array_unique((array)$this->configuration->getEnabledPlugins());

        $this->loadAutoloadPluginConfig();
        $this->initializeEnabledPlugins();

        // register local cache manager through event dispatcher
        //$this->event_dispatcher->notify(new lcEvent('local_cache.register', $this, array('key' => 'plugins')));

        $this->event_dispatcher->connect('response.send_response', $this, 'onSendResponse');
        $this->event_dispatcher->connect('router.load_configuration', $this, 'onRouterLoadConfiguration');

        $this->event_dispatcher->notify(new lcEvent('plugin_manager.startup', $this));
    }

    protected function loadAutoloadPluginConfig()
    {
        $available_plugins = $this->system_component_factory->getAvailableSystemPlugins();

        // new autoload files
        foreach ($available_plugins as $plugin_name => $plugin_details) {
            $path = $plugin_details['path'];

            // include and store the autoload configuration
            $this->tryIncludePluginAutoloadClassMapFile($path, $plugin_name);

            unset($plugin_name, $path, $plugin_details);
        }
    }

    /**
     * @param $root_dir
     * @param $plugin_name
     * @return false|iSupportsAutoload|string[]|null
     */
    protected function tryIncludePluginAutoloadClassMapFile($root_dir, $plugin_name)
    {
        if (isset($this->plugin_autoload_configurations[$plugin_name])) {
            return $this->plugin_autoload_configurations[$plugin_name];
        }

        $filename = $root_dir . DS . 'Config' . DS . 'autoload.php';

        $autoload_file_exists_cached = isset($this->autoload_class_map_file_exists_map[$plugin_name]) &&
            $this->autoload_class_map_file_exists_map[$plugin_name];

        if (!$autoload_file_exists_cached && !file_exists($filename)) {
            return false;
        }

        if (!$autoload_file_exists_cached) {
            $this->autoload_class_map_file_exists_map[$plugin_name] = true;
        }

        /** @noinspection PhpIncludeInspection */
        $ret = include_once($filename);

        if (!$ret) {
            return null;
        }

        $camelized_class_name = $plugin_name . '_autoload_plugin_configuration';
        $class_name = lcInflector::camelize($camelized_class_name, false);

        if (class_exists($class_name, false)) {
            $obj = new $class_name();

            if ($obj instanceof iSupportsAutoload) {
                $this->plugin_autoload_configurations[$plugin_name] = $obj;

                $this->getSystemComponentFactory()->getClassAutoloader()->addFromObject($obj, $root_dir);

                return $obj;
            }
        }

        return null;
    }

    public function getSystemComponentFactory(): lcSystemComponentFactory
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory = null)
    {
        $this->system_component_factory = $component_factory;
    }

    protected function initializeEnabledPlugins()
    {
        $available_plugins = $this->system_component_factory->getAvailableSystemPlugins();

        // walk all available plugins and include their configurations
        // they will reside live throughout the entire live of the application
        // we need to do this in two passes as the first one only adds the configuration file
        // to the autoloader - so the cache - which stores them also - will be able to find them after that
        foreach ($available_plugins as $plugin_name => $plugin_details) {
            $path = $plugin_details['path'];

            // include and store the configuration
            $this->tryIncludePluginConfigurationFile($path, $plugin_name);

            unset($plugin_name, $path, $plugin_details);
        }

        // then boot the plugins
        $plugins_to_start = [];

        foreach ($available_plugins as $plugin_name => $plugin_details) {
            try {
                // check if already loaded (can happen as plugins are loaded based on their dependancies below!)
                if (isset($this->plugins[$plugin_name])) {
                    continue;
                }

                $is_plugin_enabled = in_array($plugin_name, $this->enabled_plugins);
                $path = $plugin_details['path'];
                $web_path = $this->plugin_webpath ? $this->plugin_webpath . $plugin_name . '/' :
                    ($plugin_details['web_path'] ?? null);

                // initialize and store plugin configuration
                $plugin_config =
                    $this->plugin_configurations[$plugin_name] ?? $this->getInstanceOfPluginConfiguration($path, $plugin_name, $web_path);

                if (!$plugin_config) {
                    continue;
                }

                $plugin_namespace = $this->configuration
                    ->getProjectConfiguration()
                    ->getNamespacedClass('Plugins\\' . $plugin_name);

                // set / cache it
                $this->plugin_configurations[$plugin_name] = $plugin_config;

                // notify observers
                $this->event_dispatcher->notify(new lcEvent('plugin_manager.plugin_configuration_loaded', $this, [
                    'name' => $plugin_name,
                    'namespace' => $plugin_namespace,
                    'is_enabled' => $is_plugin_enabled,
                    'configuration' => &$plugin_config,
                ]));

                // check if plugin should be started automatically, it should be if:
                // - it's startup type is set to STARTUP_TYPE_AUTOMATIC
                // otherwise the plugin may be started later on - if manually called / automatic startup events are defined and detected.
                $should_start_now =
                    $is_plugin_enabled &&
                    ($plugin_config->getStartupType() == lcPluginConfiguration::STARTUP_TYPE_AUTOMATIC);

                // save autostart events so plugins can be started when the time comes
                if ($is_plugin_enabled && $plugin_config->getStartupType() == lcPluginConfiguration::STARTUP_TYPE_EVENT_BASED) {
                    $autostart_events = $plugin_config->getAutomaticStartupEvents();

                    if ($autostart_events && is_array($autostart_events)) {
                        foreach ($autostart_events as $event_name) {
                            $this->plugin_autostart_events[$event_name][] = $plugin_name;
                            unset($event_name);
                        }
                    }
                }

                // save the plugins which need to be started upon initialization
                // initialize them after this cycle completes
                if ($should_start_now) {
                    $plugins_to_start[] = $plugin_name;
                }

                unset($plugin_name, $plugin_config, $path, $web_path, $should_start_now, $is_plugin_enabled, $plugin_details);
            } catch (Exception $e) {
                throw new lcPluginException('Could not initialize plugin (' . $plugin_name . '): ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }
        }

        // store the plugins to be started later
        $this->runtime_plugins = $plugins_to_start;

        // notify about all plugins loaded
        $this->event_dispatcher->notify(new lcEvent('plugin_manager.plugins_initialized', $this));
    }

    /**
     * @param $root_dir
     * @param $plugin_name
     * @param $verify
     * @return mixed|null
     */
    private function includePluginConfig($root_dir, $plugin_name, $verify)
    {
        $filename = $root_dir . DS . 'Config' . DS . 'Configuration.php';
        $ret = null;

        if (!$verify) {
            /** @noinspection PhpIncludeInspection */
            $ret = include_once($filename);
        } else {
            if (file_exists($filename)) {
                /** @noinspection PhpIncludeInspection */
                $ret = include_once($filename);
            }
        }

        return $ret;
    }

    /**
     * @param $root_dir
     * @param $plugin_name
     * @param $verify
     * @return array|false
     * @throws lcPluginException
     */
    /**
     * @param $root_dir
     * @param $plugin_name
     * @param bool $verify
     * @return array|false
     * @throws lcPluginException
     */
    protected function tryIncludePluginConfigurationFile($root_dir, $plugin_name, bool $verify = false)
    {
        $ret = $this->includePluginConfig($root_dir, $plugin_name, $verify);

        if (!$ret) {
            if (DO_DEBUG) {
                throw new lcPluginException('Plugin cannot be loaded - configuration file missing (' . $plugin_name . ')');
            }
            return false;
        }

        $cls_names = [
            $this->getPluginNamespacedClass($plugin_name . '\\Config\\Configuration'),
        ];

        // cache this so we don't need to call subcamelize several times
        $this->included_plugin_classes[$plugin_name] = $cls_names;

        return $cls_names;
    }

    public function getPluginNamespacedClass(string $class): string
    {
        return $this->configuration->getProjectNamespace() . '\\Plugins\\' . $class;
    }

    /**
     * @param $root_dir
     * @param $plugin_name
     * @param $web_path
     * @return lcPluginConfiguration|null
     * @throws lcPluginException
     * @throws lcSystemException|lcConfigException
     */
    public function getInstanceOfPluginConfiguration($root_dir, $plugin_name, $web_path = null): ?lcPluginConfiguration
    {
        // new autoload files
        // include and store the autoload configuration
        $this->tryIncludePluginAutoloadClassMapFile($root_dir, $plugin_name);

        if (!isset($this->included_plugin_classes[$plugin_name])) {
            // try to include and store the configuration
            $class_name = $this->tryIncludePluginConfigurationFile($root_dir, $plugin_name);

            if (!$class_name) {
                return null;
            }
        } else {
            $class_name = $this->included_plugin_classes[$plugin_name];
        }

        // if the class is not available in the class path it means there is either no custom configuration
        // or some other error occured - in this case - load the default configuration
        if (is_array($class_name)) {
            $existing_class = self::DEFAULT_PLUGIN_CONFIG_CLASS_NAME;

            foreach ($class_name as $cls) {

                if (class_exists($cls, false)) {
                    $existing_class = $cls;
                    break;
                }

                unset($cls);
            }

            $class_name = $existing_class;
        } else {
            $class_name = class_exists($class_name, false) ? $class_name : self::DEFAULT_PLUGIN_CONFIG_CLASS_NAME;
        }

        // create the instance
        $configuration = new $class_name();

        if (!($configuration instanceof lcPluginConfiguration)) {
            throw new lcSystemException('Plugin configuration is invalid - not inherited from lcPluginConfiguration');
        }

//        $configuration = !$configuration ? new lcPluginConfiguration() : $configuration;

        $configuration->setRootDir($root_dir);
        $configuration->setWebPath($web_path);
        $configuration->setName($plugin_name);
        $configuration->setBaseConfigDir($this->configuration->getBaseConfigDir());
        $configuration->setEnvironment($this->configuration->getEnvironment());
        $configuration->setEnvironments($this->configuration->getEnvironments());

        $configuration->initialize();
        $configuration->loadData();

        return $configuration;
    }

    // @codingStandardsIgnoreStart

//    /**
//     * @param $plugin_name
//     * @return lcPackageDatabaseMigrationSchema|lcSysObj|mixed|null
//     * @throws lcSystemException
//     */
//    public function getPluginDatabaseMigrationSchema($plugin_name)
//    {
//        $plugin_config = $this->getPluginConfiguration($plugin_name);
//        $schema = null;
//
//        if ($plugin_config) {
//            // TODO: fixme - undefined
//            $schema = $plugin_config->getDatabaseMigrationSchema();
//
//            if ($schema instanceof lcSysObj) {
//                $schema->setLogger($this->logger);
//                $schema->setI18n($this->i18n);
//                $schema->setConfiguration($this->configuration);
//                $schema->setEventDispatcher($this->event_dispatcher);
//
//                // start it up
//                $schema->initialize();
//            }
//
//            if ($schema instanceof lcPackageDatabaseMigrationSchema) {
//                $schema->setPluginConfiguration($plugin_config);
//            }
//        }
//
//        return $schema;
//    }

    /**
     * @param $plugin_name
     * @return lcPluginConfiguration|null
     * @throws lcSystemException|lcPluginException|lcConfigException
     */
    public function getPluginConfiguration($plugin_name): ?lcPluginConfiguration
    {
        if (!isset($this->plugin_configurations[$plugin_name])) {
            $available_plugins = $this->system_component_factory->getAvailableSystemPlugins();

            foreach ($available_plugins as $plugin_name => $plugin_details) {
                $path = $plugin_details['path'];
                $web_path = $this->plugin_webpath ? $this->plugin_webpath . $plugin_name . '/' :
                    ($plugin_details['web_path'] ?? null);

                // initialize and store plugin configuration
                $this->plugin_configurations[$plugin_name] =
                    $this->plugin_configurations[$plugin_name] ?? $this->getInstanceOfPluginConfiguration($path, $plugin_name, $web_path);
            }
        }
        return $this->plugin_configurations[$plugin_name];
    }

    // @codingStandardsIgnoreEnd

    public function shutdown()
    {
        // shutdown and unset all plugins
        // shutdown in reverse
        $plugins = $this->plugins;

        if ($plugins) {
            $plugins = array_reverse($plugins, true);

            foreach ($plugins as $plugin) {
                /** @var lcPlugin $plugin */
                $name = $plugin->getName();

                try {
                    $plugin->shutdown();

                    // notify
                    $plugin_params = [
                        'name' => $name,
                    ];

                    $this->event_dispatcher->notify(new lcEvent('plugin.' . $name . '.shutdown', $this, $plugin_params));

                    unset($this->plugins[$name], $plugin_params, $plugin);
                } catch (Exception $e) {
                    throw new lcSystemException('Error while shutting down plugin \'' . $name . '\': ' . $e->getMessage(),
                        $e->getCode(), $e);
                }
            }

            unset($plugins);
        }

        // shutdown plugin configurations
        $plugin_configurations = $this->plugin_configurations;

        if ($plugin_configurations) {
            foreach ($plugin_configurations as $plugin_name => $configuration) {
                $configuration->shutdown();
                unset($this->plugin_configurations[$plugin_name]);
                unset($plugin_name, $configuration);
            }
        }

        $this->routing =
        $this->system_component_factory =
        $this->database_model_manager =
        $this->plugins =
        $this->plugin_configurations =
            null;

        $this->runtime_plugins =
        $this->plugin_autostart_events =
        $this->included_plugin_classes =
        $this->enabled_plugins = [];

        parent::shutdown();
    }

    public function getDebugInfo(): array
    {
        // compile debug info
        $dbg = [];
        $plugins = $this->plugins;

        if ($plugins) {
            foreach ($plugins as $name => $plugin) {
                if ($plugin instanceof iDebuggable) {
                    $dbg[$name] = $plugin->getDebugInfo();
                }

                unset($plugin, $implementations);
            }
        }

        return $dbg;
    }

    /**
     * @return array|mixed|null
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getShortDebugInfo()
    {
        return null;
    }

    public function willSendNotification(lcEventDispatcher $event_dispatcher, lcEvent $event, lcObj $invoker = null)
    {
        // boot autostart plugins which are based on events
        if (isset($this->plugin_autostart_events[$event->event_name])) {
            $this->initializeEventBasedPlugins($event->event_name);
        }
    }

    /**
     * @param $event_name
     * @return void
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    protected function initializeEventBasedPlugins($event_name)
    {
        $plugins = $this->plugin_autostart_events[$event_name];

        foreach ($plugins as $idx => $plugin_name) {
            // if already initialized do nothing
            if (!isset($this->plugins[$plugin_name])) {
                // initialize
                $this->initializePlugin($plugin_name);
            }

            // remove from list
            unset($this->plugin_autostart_events[$event_name][$idx]);

            // unset from array if no more launchable plugins exist
            if (!$this->plugin_autostart_events[$event_name]) {
                unset($this->plugin_autostart_events[$event_name]);
            }

            unset($idx, $plugin_name);
        }
    }

    /**
     * @param $plugin_name
     * @param bool $load_dependancies
     * @param bool $throw_if_missing
     * @return bool|lcPlugin|lcResidentObj|null
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    public function initializePlugin($plugin_name, bool $load_dependancies = true, bool $throw_if_missing = true)
    {
        // TODO: rework this! Even if they do not start now the plugins must finish their initialization when manually called by getPlugin()
        if (!$this->should_load_plugins) {
            return null;
        }

        if (!$plugin_name) {
            throw new lcInvalidArgumentException('Invalid plugin');
        }

        try {
            if ($this->hasPlugin($plugin_name)) {
                return true;
            }

            // check if initialized
            $plugin_configuration = $this->plugin_configurations[$plugin_name] ?? null;

            if (!$plugin_configuration) {
                if (!$throw_if_missing) {
                    return false;
                }

                throw new lcNotAvailableException('Plugin not available');
            }

            // check if enabled
            if (!in_array($plugin_name, $this->enabled_plugins)) {
                if (!$throw_if_missing) {
                    return false;
                }

                throw new lcNotAvailableException('Plugin is not enabled');
            }

            // check if plugin meets the framework constraints
            $this->validatePluginConfigMeetsPlatformConstraints($plugin_configuration);

            $system_component_factory = $this->system_component_factory;

            /** @var lcPlugin $plugin_object */
            $plugin_object = $system_component_factory->getSystemPluginInstance($plugin_name);

            if (!$plugin_object) {
                throw new lcNotAvailableException('Plugin cannot be instantiated');
            }

            // set system objects
            $plugin_object->setPluginManager($this);
            $plugin_object->setEventDispatcher($this->event_dispatcher);
            $plugin_object->setConfiguration($this->configuration);
            $plugin_object->setClassAutoloader($this->class_autoloader);
            $plugin_object->setSystemComponentFactory($this->system_component_factory);
            $plugin_object->setDatabaseModelManager($this->database_model_manager);
            $plugin_object->setPluginConfiguration($plugin_configuration);
            $plugin_object->setContextName($this->configuration->getProjectName());
            $plugin_object->setContextType(lcSysObj::CONTEXT_PROJECT);

            $plugin_object->setTranslationContext(lcSysObj::CONTEXT_PLUGIN, $plugin_object->getPluginName());

            // add it now
            $this->plugins[$plugin_name] = $plugin_object;

            // check and load dependancies
            if ($load_dependancies) {
                $this->loadPluginDependancies($plugin_object);
            }

            // add plugin to system
            $plugin_params = [
                'name' => $plugin_object->getPluginName(),
                'path' => $plugin_object->getRootDir(),
                'plugin_instance' => &$plugin_object,
            ];

            if ($plugin_object instanceof lcResidentObj) {
                $plugin_object->attachRegisteredEvents();
            }

            // notify before the initialization
            $this->event_dispatcher->notify(new lcEvent('plugin.will_startup', $this, $plugin_params));

            // initialize it
            $plugin_object->initialize();

            // register object provider
            $camelized_name = 'getPluginCallback';
            $this->event_dispatcher->registerProvider('plugin.' . $plugin_name, $this, $camelized_name);

            // notify about the initialization
            $this->event_dispatcher->notify(new lcEvent('plugin.startup', $this, $plugin_params));
            $this->event_dispatcher->notify(new lcEvent('plugin.' . $plugin_name . '.startup', $this, $plugin_params));

            // now send the app initialization notification
            // it will be handled only if the app is available and fully initialized
            $this->notifyPluginOfAppInitialization($plugin_object);


            return $plugin_object;
        } catch (Exception $e) {
            throw new lcPluginException('Could not initialize plugin: \'' . $plugin_name . '\': ' . $e->getMessage(),
                $e->getCode(),
                $e);
        }
    }

    /**
     * @param $plugin_name
     * @return bool
     */
    public function hasPlugin($plugin_name): bool
    {
        if (!isset($plugin_name)) {
            return false;
        }

        return isset($this->plugins[$plugin_name]);
    }

    public function validatePluginConfigMeetsPlatformConstraints(lcPluginConfiguration $plugin_configuration): void
    {
        // verify if the target / minimum versions are met
        $target_version = $plugin_configuration->getTargetFrameworkVersion();
        $minimum_version = $plugin_configuration->getMinimumFrameworkVersion();

        if ($target_version) {
            if (version_compare($target_version, LC_VER, '>=')) {
                throw new lcUnsupportedException('The application is targeting LC ver ' . $target_version . ' (current LC version: ' . LC_VER . ')');
            }
        }

        if ($minimum_version) {
            if (version_compare($minimum_version, LC_VER, '>=')) {
                throw new lcUnsupportedException('The application requires at least Lightcast ver ' . $minimum_version . ' (current LC version: ' . LC_VER . ')');
            }
        }

        if (!$plugin_configuration->getIdentifier()) {
            /** @noinspection PhpUnreachableStatementInspection */
            throw new lcSystemRequirementException('LC 1.5 plugins are required to define an unique GUID');
        }
    }

    protected function loadPluginDependancies(lcPlugin $plugin_object)
    {
        $plugin_name = $plugin_object->getPluginName();
        $plugin_config = $plugin_object->getPluginConfiguration();

        $requirements = [];

        if ($plugin_config instanceof iPluginRequirements) {
            $requirements = array_merge($requirements,
                (array)$plugin_config->getRequiredPlugins());
        }

        if (!$requirements) {
            return;
        }

        $plugin_configurations = $this->plugin_configurations;

        // process the requirements
        foreach ($requirements as $req) {
            // internal error
            if ($req == $plugin_name) {
                continue;
            }

            // check if requirement is already loaded
            // if not - try to load it first
            if (isset($this->plugins[$req])) {
                continue;
            }

            // not loaded - try to load the dep first!
            try {
                if (!isset($plugin_configurations[$req])) {
                    throw new lcNotAvailableException('Plugin not available');
                }

                $this->initializePlugin($req);
            } catch (Exception $e) {
                throw new lcPluginException('Could not load plugin \'' . $plugin_name . '\' - depending plugin: \'' . $req . '\' could not be initialized: ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }

            unset($req);
        }
    }

    protected function notifyPluginOfAppInitialization(lcPlugin $plugin)
    {
        if (!$this->app_context || !$this->app_context->getIsInitialized()) {
            return;
        }

        if (!$plugin->getHasInitialized()) {
            return;
        }

        // set loader objects onto plugin
        if ($plugin->getHasAppInitialized()) {
            // skip plugins which have already been notified
            // may happen because we initialize plugin dynamically in time now
            return;
        }

        // validate required capabilities
        $platform_capabilities = $this->app_context->getPlatformCapabilities();

        $plugin_configuration = $plugin->getPluginConfiguration();

        if ($plugin_configuration instanceof iRequiresCapabilities) {
            $required_capabilities = $plugin_configuration->getRequiredCapabilities();

            if ($required_capabilities && is_array($required_capabilities)) {
                $has_cap = lcArrays::arrayContainsArrayValues($required_capabilities, $platform_capabilities);

                if (!$has_cap) {
                    throw new lcSystemRequirementException('Plugin \'' . $plugin->getPluginName() . '\' required capabilities not met ' .
                        '(requires: ' . implode(', ', $required_capabilities) . ')');
                }

                unset($has_cap);
            }

            unset($required_capabilities);
        }

        unset($plugin_configuration);

        // initialize
        $this->app_context->setLoadersOntoObject($plugin);

        $plugin->initializeApp($this->app_context);

        // initialize web / console based methods
        if ($this->configuration instanceof lcConsoleConfiguration) {
            $plugin->initializeConsoleComponents();
        } else if ($this->configuration instanceof lcWebConfiguration) {
            $plugin->initializeWebComponents();
        } else if ($this->configuration instanceof lcWebServiceConfiguration) {
            $plugin->initializeWebServiceComponents();
        }
    }

    /**
     * @param lcEventDispatcher $event_dispatcher
     * @param lcEvent $event
     * @param $value
     * @param lcObj|null $invoker
     * @return void
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    public function willFilterValue(lcEventDispatcher $event_dispatcher, lcEvent $event, $value, lcObj $invoker = null)
    {
        // boot autostart plugins which are based on events
        if (isset($this->plugin_autostart_events[$event->event_name])) {
            $this->initializeEventBasedPlugins($event->event_name);
        }
    }

    public function getAppContext(): lcApp
    {
        return $this->app_context;
    }

    public function setAppContext(lcApp $app_context = null)
    {
        $this->app_context = $app_context;
    }

    public function getDatabaseModelManager(): lcDatabaseModelManager
    {
        return $this->database_model_manager;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager = null)
    {
        $this->database_model_manager = $database_model_manager;
    }

    public function getShouldLoadPlugins(): bool
    {
        return $this->should_load_plugins;
    }

    /**
     * @param bool $should_load_plugins
     * @return void
     */
    public function setShouldLoadPlugins(bool $should_load_plugins = true)
    {
        $this->should_load_plugins = $should_load_plugins;
    }

    public function getPluginConfigurations(): array
    {
        return $this->plugin_configurations;
    }

    /**
     * @return array
     */
    public function getEnabledPlugins(): array
    {
        return $this->enabled_plugins;
    }

    /**
     * @return array
     */
    public function getRuntimePlugins(): array
    {
        return $this->runtime_plugins;
    }

    /**
     * @param lcEvent $event
     * @param $value
     * @return mixed
     */
    public function onSendResponse(lcEvent $event, $value)
    {
        $response = $event->getSubject();

        if ($response instanceof lcWebResponse) {
            $plugins = $this->plugins;

            if ($plugins) {
                foreach ($plugins as $plugin) {
                    $this->processPluginResponse($plugin, $response);
                }
            }
        }

        return $value;
    }

    private function processPluginResponse(lcPlugin $plugin, lcWebResponse $response)
    {
        $plugin_configuration = $plugin->getPluginConfiguration();
        $css_assets_webpath = $plugin->getAssetsWebPath('css');
        $js_assets_webpath = $plugin->getAssetsWebPath('js');

        // included stylesheets
        $this->renderIncludedStylesheets($plugin, $plugin->getIncludedStylesheets(), $response, [
            'assets_webpath' => $css_assets_webpath,
        ]);

        // config stylesheets
        $t = $plugin_configuration['view.stylesheets'];

        if ($t) {
            foreach ($t as $media => $files) {
                if ($files && is_array($files)) {
                    foreach ($files as $file) {
                        $src = lcStrings::isAbsolutePath($file) ? $file : $css_assets_webpath . $file;
                        $response->setStylesheet($src, $media);
                        unset($file, $src);
                    }
                }

                unset($media, $files);
            }

            unset($t);
        }

        // included javascripts
        $this->renderIncludedJavascript($plugin, $plugin->getIncludedJavascripts(), $response, [
            'assets_webpath' => $js_assets_webpath,
        ]);

        // javascripts
        $t = $plugin_configuration['view.javascripts'];

        if ($t && is_array($t)) {
            foreach ($t as $file) {
                $src = lcStrings::isAbsolutePath($file) ? $file : $css_assets_webpath . $file;
                $response->setJavascript($src);
                unset($file, $src);
            }

            unset($t);
        }

        // metatags
        $t = $plugin_configuration['view.metatags'];

        if ($t && is_array($t)) {
            foreach ($t as $title => $value1) {
                $response->setMetatag($title, $value1);
                unset($title, $value1);
            }
            unset($t);
        }

        unset($plugin);
    }

    private function renderIncludedStylesheets(lcPlugin $plugin, array $included_stylesheets, lcWebResponse $response, array $options = null)
    {
        $assets_webpath = $options['assets_webpath'] ?? null;

        foreach ($included_stylesheets as $tag => $data) {
            $opts = $data['options'] ?? [];
            $src = !$assets_webpath || lcStrings::isAbsolutePath($data['src']) ? $data['src'] : $assets_webpath . $data['src'];

            $response->setStylesheet($src,
                $opts['media'] ?? null,
                $opts['type'] ?? null
            );
            unset($tag, $data, $src, $opts);
        }
    }

    private function renderIncludedJavascript(lcPlugin $plugin, array $included_javascripts, lcWebResponse $response, array $options = null)
    {
        $assets_webpath = $options['assets_webpath'] ?? null;

        foreach ($included_javascripts as $tag => $data) {
            $opts = $data['options'] ?? [];
            $prepend = isset($opts['prepend']) && $opts['prepend'];
            $src = !$assets_webpath || lcStrings::isAbsolutePath($data['src']) ? $data['src'] : $assets_webpath . $data['src'];

            if ($prepend) {
                $response->prependJavascript($src,
                    $opts['type'] ?? null,
                    $opts['language'] ?? null,
                    $opts['at_end'] ?? null,
                    $opts['attribs'] ?? null
                );
            } else {
                $response->setJavascript($src,
                    $opts['type'] ?? null,
                    $opts['language'] ?? null,
                    $opts['at_end'] ?? null,
                    $opts['attribs'] ?? null
                );
            }

            unset($tag, $data, $opts);
        }
    }

    public function initializePluginsForAppStartup()
    {
        // notify all plugins the app has now fully started
        // (before controller dispatch)
        $plugins = $this->plugins;

        if ($plugins) {
            foreach ($plugins as $name => $plugin) {
                $this->notifyPluginOfAppInitialization($plugin);

                unset($name, $plugin);
            }
        }
    }

    public function onRouterLoadConfiguration(lcEvent $event)
    {
        $this->routing = $event->getSubject();

        // load all routes from configured plugins
        $plugin_configurations = $this->plugin_configurations;

        foreach ($plugin_configurations as $plugin_name => $plugin_configuration) {

            // skip disabled plugins
            if (!in_array($plugin_name, $this->enabled_plugins)) {
                continue;
            }

            $this->registerPluginRoutes($plugin_configuration);

            unset($plugin_name, $plugin_configuration);
        }
    }

    private function registerPluginRoutes(lcPluginConfiguration $plugin_configuration)
    {
        $router = $this->routing;

        if (!($router instanceof iRouteBasedRouting)) {
            return;
        }

        $plugin_routes = $plugin_configuration->getRoutes();

        if (!$plugin_routes || !is_array($plugin_routes)) {
            return;
        }

        $current_app_name = $this->configuration->getApplicationName();

        foreach ($plugin_routes as $name => $details) {
            $apps = isset($details['apps']) ? (array)$details['apps'] : null;
            $requirements = isset($details['requirements']) ? (array)$details['requirements'] : null;
            $url = isset($details['url']) ? (string)$details['url'] : null;
            $params = isset($details['params']) ? (array)$details['params'] : null;
            $options = isset($details['options']) ? (array)$details['options'] : null;

            if (!$url) {
                continue;
            }

            if ($apps) {
                $apps = is_array($apps) ? $apps : [$apps];

                if ($apps && !in_array($current_app_name, $apps)) {
                    continue;
                }
            }

            $route = new lcNamedRoute();
            $route->setRequirements($requirements);
            $route->setRoute($url);
            $route->setName($name);
            $route->setDefaultParams($params);
            $route->setOptions($options);

            $router->prependRoute($route);

            unset($name, $details, $route, $options, $params, $requirements, $url, $apps);
        }
    }

    /**
     * @param $plugin_name
     * @param bool $try_initialize
     * @param bool $throw_if_missing
     * @return lcPlugin|null
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    public function getPlugin($plugin_name, bool $try_initialize = true, bool $throw_if_missing = true): ?lcPlugin
    {
        if (!isset($this->plugins[$plugin_name]) && $try_initialize) {
            // try to initialize it
            $this->initializePlugin($plugin_name, true, $throw_if_missing);
        }

        return $this->plugins[$plugin_name] ?? null;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * @param lcEvent $event
     * @return false|lcPlugin|null
     */
    public function getPluginCallback(lcEvent $event)
    {
        $plugin = $event->event_name;

        if (!isset($plugin)) {
            return false;
        }

        $plugin = substr($plugin, strlen('plugin.'), strlen($plugin));

        if (!isset($plugin)) {
            return false;
        }

        return $this->plugins[$plugin] ?? null;
    }

    public function writeClassCache(): array
    {
        // we need to store them serialized and read them later on - when all classes are made available
        // otherwise when expanding them into objects - they won't be found!
        return [
            'plugin_configurations' => ($this->plugin_configurations ? serialize($this->plugin_configurations) : null),
            'plugin_autoload_configurations' => ($this->plugin_autoload_configurations ?
                serialize($this->plugin_autoload_configurations) : null),
            'autoload_class_map_file_exists_map' => $this->autoload_class_map_file_exists_map,
        ];
    }

    public function readClassCache(array $cached_data)
    {
        $this->plugin_configurations = isset($cached_data['plugin_configurations']) ?
            unserialize($cached_data['plugin_configurations']) : null;
        $this->plugin_autoload_configurations = isset($cached_data['plugin_autoload_configurations']) ?
            unserialize($cached_data['plugin_autoload_configurations']) : null;
        $this->autoload_class_map_file_exists_map = $cached_data['autoload_class_map_file_exists_map'] ?? null;
    }
}
