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
 * @changed $Id: lcPluginManager.class.php 1589 2015-05-17 16:50:29Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1589 $
 */
class lcPluginManager extends lcSysObj implements iCacheable, iDebuggable, iEventObserver
{
    const DEFAULT_PLUGIN_CONFIG_CLASS_NAME = 'lcPluginConfiguration';

    protected $should_load_plugins = true;

    /**
     * @var lcSystemComponentFactory
     */
    protected $system_component_factory;

    /**
     * @var lcDatabaseModelManager
     */
    protected $database_model_manager;

    /**
     * @var lcPlugin[]
     */
    protected $plugins;

    /**
     * @var lcPluginConfiguration[]
     */
    protected $plugin_configurations;

    protected $runtime_plugins;
    protected $enabled_plugins;

    /**
     * @var lcRouting
     */
    protected $routing;

    private $plugin_configurations_cached;
    private $included_plugin_classes;
    private $plugin_autostart_events;

    /**
     * @var lcApp
     */
    protected $app_context;

    public function initialize()
    {
        parent::initialize();

        $this->plugins =
            array();

        $this->plugin_configurations = array();

        if ($this->should_load_plugins) {
            $this->initializeEnabledPlugins();
        }

        // register local cache manager through event dispatcher
        //$this->event_dispatcher->notify(new lcEvent('local_cache.register', $this, array('key' => 'plugins')));

        $this->event_dispatcher->connect('response.send_response', $this, 'onSendResponse');
        $this->event_dispatcher->connect('router.load_configuration', $this, 'onRouterLoadConfiguration');

        $this->event_dispatcher->notify(new lcEvent('plugin_manager.startup', $this));
    }

    public function shutdown()
    {
        // shutdown and unset all plugins
        // shutdown in reverse
        $plugins = $this->plugins;

        if ($plugins && is_array($plugins)) {
            $plugins = array_reverse($plugins, true);

            foreach ($plugins as $plugin) {
                /** @var lcPlugin $plugin */
                $name = $plugin->getName();

                try {
                    $plugin->shutdown();

                    // notify
                    $plugin_params = array(
                        'name' => $name,
                    );

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
        $this->runtime_plugins =
        $this->enabled_plugins =
        $this->plugin_autostart_events =
        $this->plugin_configurations_cached =
        $this->included_plugin_classes =
        $this->system_component_factory =
        $this->database_model_manager =
        $this->plugins =
        $this->plugin_configurations =
            null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        // compile debug info
        $dbg = array();
        $plugins = $this->plugins;

        if ($plugins) {
            foreach ($plugins as $name => $plugin) {
                $implementations = $plugin->getPluginConfiguration()->getImplementations();

                if ($plugin instanceof iDebuggable || in_array('iDebuggable', (array)$implementations)) {
                    $dbg[$name] = $plugin->getDebugInfo();
                }

                unset($plugin, $implementations);
            }
        }

        $debug = $dbg;

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    // @codingStandardsIgnoreStart
    public function willSendNotification(lcEventDispatcher $event_dispatcher, lcEvent $event, lcObj $invoker = null)
    {
        // boot autostart plugins which are based on events
        if (isset($this->plugin_autostart_events[$event->event_name])) {
            $this->initializeEventBasedPlugins($event->event_name);
        }
    }

    public function willFilterValue(lcEventDispatcher $event_dispatcher, lcEvent $event, $value, lcObj $invoker = null)
    {
        // boot autostart plugins which are based on events
        if (isset($this->plugin_autostart_events[$event->event_name])) {
            $this->initializeEventBasedPlugins($event->event_name);
        }
    }

    // @codingStandardsIgnoreEnd

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

    public function setAppContext(lcApp $app_context = null)
    {
        $this->app_context = $app_context;
    }

    public function getAppContext()
    {
        return $this->app_context;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager = null)
    {
        $this->database_model_manager = $database_model_manager;
    }

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory = null)
    {
        $this->system_component_factory = $component_factory;
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    public function setShouldLoadPlugins($should_load_plugins = true)
    {
        $this->should_load_plugins = $should_load_plugins;
    }

    public function getShouldLoadPlugins()
    {
        return $this->should_load_plugins;
    }

    public function getPluginConfigurations()
    {
        return $this->plugin_configurations;
    }

    protected function initializeEnabledPlugins()
    {
        // init enabled plugins
        $this->enabled_plugins = array_unique((array)$this->configuration->getEnabledPlugins());

        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

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

        // expand the cache if available
        if ($this->plugin_configurations_cached) {
            $this->plugin_configurations = @unserialize($this->plugin_configurations_cached);
        }

        // then boot the plugins
        $plugins_to_start = array();

        foreach ($available_plugins as $plugin_name => $plugin_details) {
            try {
                // check if already loaded (can happen as plugins are loaded based on their dependancies below!)
                if (isset($this->plugins[$plugin_name])) {
                    continue;
                }

                $is_plugin_enabled = in_array($plugin_name, $this->enabled_plugins);
                $path = $plugin_details['path'];
                $web_path = isset($plugin_details['web_path']) ? $plugin_details['web_path'] : null;

                // initialize and store plugin configuration
                $plugin_config =
                    isset($this->plugin_configurations[$plugin_name]) ? $this->plugin_configurations[$plugin_name] :
                        $this->getInstanceOfPluginConfiguration($path, $plugin_name, $web_path);

                if (!$plugin_config) {
                    continue;
                }

                // set / cache it
                $this->plugin_configurations[$plugin_name] = $plugin_config;

                // notify observers
                $this->event_dispatcher->notify(new lcEvent('plugin_manager.plugin_configuration_loaded', $this, array(
                    'name' => $plugin_name,
                    'is_enabled' => $is_plugin_enabled,
                    'configuration' => &$plugin_config
                )));

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

    public function getEnabledPlugins()
    {
        return $this->enabled_plugins;
    }

    public function getRuntimePlugins()
    {
        return $this->runtime_plugins;
    }

    public function onSendResponse(lcEvent $event, $value)
    {
        $response = $event->getSubject();

        if ($response instanceof lcWebResponse) {
            $plugins = $this->plugins;

            if ($plugins) {
                foreach ($plugins as $plugin) {
                    $plugin_configuration = $plugin->getPluginConfiguration();

                    // stylesheets
                    if ($t = $plugin_configuration['view.stylesheets']) {
                        foreach ($t as $media => $files) {
                            if ($files && is_array($files)) {
                                foreach ($files as $file) {
                                    $href = $plugin->getAssetsWebpath() . 'css/' . $file;
                                    $response->setStylesheet($href, $media);

                                    unset($file, $href);
                                }
                            }

                            unset($media, $files);
                        }

                        unset($t);
                    }

                    // javascripts
                    $t = $plugin_configuration['view.javascripts'];

                    if ($t && is_array($t)) {
                        foreach ($t as $file) {
                            $response->setJavascript($plugin->getAssetsWebpath() . 'js/' . $file);

                            unset($file);
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
            }
        }

        return $value;
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

    protected function notifyPluginOfAppInitialization(lcPlugin $plugin)
    {
        if (!$this->app_context || !$this->app_context->getIsInitialized()) {
            return;
        }

        // set loader objects onto plugin
        if ($plugin->getHasAppInitialized()) {
            // skip plugins which have already been notified
            // may happen because we initialize plugin dynamically in time now
            return;
        }

        // validate required capabilities
        $platform_capabilities = (array)$this->app_context->getPlatformCapabilities();

        $plugin_configuration = $plugin->getPluginConfiguration();

        $implementations = $plugin_configuration->getImplementations();

        if ($plugin_configuration instanceof iRequiresCapabilities || in_array('iRequiresCapabilities', (array)$implementations)) {
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

        try {
            $plugin->initializeApp($this->app_context);

            // initialize web / console based methods
            if ($this->configuration instanceof lcConsoleConfiguration) {
                $plugin->initializeConsoleComponents();
            } elseif ($this->configuration instanceof lcWebConfiguration) {
                $plugin->initializeWebComponents();
            } elseif ($this->configuration instanceof lcWebServiceConfiguration) {
                $plugin->initializeWebServiceComponents();
            }
        } catch (Exception $e) {
            throw new lcPluginException('Plugin \'' . $plugin->getPluginName() . '\' could not be initialized upon app start: ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
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

        if (!$router || !($router instanceof iRouteBasedRouting)) {
            return;
        }

        $plugin_routes = $plugin_configuration->getRoutes();

        if (!$plugin_routes || !is_array($plugin_routes)) {
            return;
        }

        foreach ($plugin_routes as $name => $details) {
            $requirements = isset($details['requirements']) ? (array)$details['requirements'] : null;
            $url = isset($details['url']) ? (string)$details['url'] : null;
            $params = isset($details['params']) ? (array)$details['params'] : null;
            $options = isset($details['options']) ? (array)$details['options'] : null;

            if (!$url) {
                assert(false);
                continue;
            }

            $route = new lcNamedRoute();
            $route->setRequirements($requirements);
            $route->setRoute($url);
            $route->setName($name);
            $route->setDefaultParams($params);
            $route->setOptions($options);

            $router->prependRoute($route);

            unset($name, $details, $route, $options, $params, $requirements, $url);
        }
    }

    public function hasPlugin($plugin_name)
    {
        if (!isset($plugin_name)) {
            assert(false);
            return false;
        }

        return isset($this->plugins[$plugin_name]);
    }

    /**
     * @param $plugin_name
     * @param bool $try_initialize
     * @return lcPlugin|null
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    public function getPlugin($plugin_name, $try_initialize = true)
    {
        if (!isset($this->plugins[$plugin_name]) && $try_initialize) {
            // try to initialize it
            $this->initializePlugin($plugin_name);
        }

        $plugin_instance = isset($this->plugins[$plugin_name]) ? $this->plugins[$plugin_name] : null;
        return $plugin_instance;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    protected function tryIncludePluginConfigurationFile($root_dir, $plugin_name, $verify = false)
    {
        $filename = $root_dir . DS . 'config' . DS . $plugin_name . '_config.php';

        if ($verify && !file_exists($filename)) {
            return false;
        }

        $ret = @include_once($filename);

        if (!$ret) {
            return false;
        }

        $class_name = lcInflector::camelize($plugin_name . '_config_configuration', false);

        // this is now deprecated
        $class_name_deprecated = lcInflector::subcamelize($plugin_name . '_config', false);

        // cache this so we don't need to call subcamelize several times
        $ret = array($class_name, $class_name_deprecated);
        $this->included_plugin_classes[$plugin_name] = $ret;

        return $ret;
    }

    public function getInstanceOfPluginConfiguration($root_dir, $plugin_name, $web_path)
    {
        $class_name = null;

        if (!isset($this->included_plugin_classes[$plugin_name])) {
            // try to include and store the configuration
            $class_name = $this->tryIncludePluginConfigurationFile($root_dir, $plugin_name, false);

            if (!$class_name) {
                return null;
            }
        } else {
            $class_name = $this->included_plugin_classes[$plugin_name];
        }

        $configuration = null;

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

        $configuration = !$configuration ? new lcPluginConfiguration() : $configuration;

        $configuration->setRootDir($root_dir);
        $configuration->setWebPath($web_path);
        $configuration->setName($plugin_name);
        $configuration->setBaseConfigDir($this->configuration->getBaseConfigDir());
        $configuration->setEnvironment($this->configuration->getEnvironment());
        $configuration->setEnvironments($this->configuration->getEnvironments());

        $configuration->initialize();

        return $configuration;
    }

    public function initializePlugin($plugin_name, $load_dependancies = true)
    {
        if (!$plugin_name) {
            throw new lcInvalidArgumentException('Invalid plugin');
        }

        try {
            if ($this->hasPlugin($plugin_name)) {
                return true;
            }

            // check if initialized
            $plugin_configuration = isset($this->plugin_configurations[$plugin_name]) ? $this->plugin_configurations[$plugin_name] :
                null;

            if (!$plugin_configuration) {
                throw new lcNotAvailableException('Plugin not available');
            }

            // check if enabled
            if (!in_array($plugin_name, $this->enabled_plugins)) {
                throw new lcNotAvailableException('Plugin is not enabled');
            }

            $system_component_factory = $this->system_component_factory;

            if (!$system_component_factory) {
                throw new lcNotAvailableException('System Component Factory not available');
            }

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
            $plugin_params = array(
                'name' => $plugin_object->getPluginName(),
                'path' => $plugin_object->getRootDir(),
                'plugin_instance' => &$plugin_object
            );

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

    protected function loadPluginDependancies(lcPlugin $plugin_object)
    {
        $plugin_name = $plugin_object->getPluginName();
        $plugin_config = $plugin_object->getPluginConfiguration();

        $implementations = $plugin_config->getImplementations();

        if (!($plugin_config instanceof iPluginRequirements && !in_array('iPluginRequirements', (array)$implementations))) {
            return;
        }

        $requirements = (array)$plugin_config->getRequiredPlugins();

        if (!$requirements || !is_array($requirements)) {
            return;
        }

        $plugin_configurations = $this->plugin_configurations;

        // process the requirements
        foreach ($requirements as $req) {
            // internal error
            if ($req == $plugin_name) {
                assert(false);
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

        $instance = isset($this->plugins[$plugin]) ? $this->plugins[$plugin] : null;

        return $instance;
    }

    public function writeClassCache()
    {
        // we need to store them serialized and read them later on - when all classes are made available
        // otherwise when expanding them into objects - they won't be found!
        $cached_data = array(
            'plugin_configurations_serialized' => ($this->plugin_configurations_cached ? $this->plugin_configurations_cached :
                ($this->plugin_configurations ? serialize($this->plugin_configurations) : null))
        );

        return $cached_data;
    }

    public function readClassCache(array $cached_data)
    {
        $this->plugin_configurations_cached = isset($cached_data['plugin_configurations_serialized']) ? $cached_data['plugin_configurations_serialized'] : null;
    }
}