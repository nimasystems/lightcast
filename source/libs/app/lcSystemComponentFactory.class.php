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
 * @changed $Id: lcSystemComponentFactory.class.php 1475 2013-11-26 16:51:48Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1475 $
 */
class lcSystemComponentFactory extends lcSysObj implements iCacheable
{
    protected $controller_modules;
    protected $controller_web_services;
    protected $controller_tasks;
    protected $controller_components;

    // store the ones from configuration separately and merge them later
    // so they can be cached (as we need to scan folder / files to acquire them the first time)
    private $config_system_loaders;
    private $config_system_plugins;
    private $config_controller_modules;
    private $config_controller_web_services;
    private $config_controller_tasks;
    private $config_controller_components;

    public function initialize()
    {
        parent::initialize();

        // initialize and merge the system configurations
        if (is_null($this->config_system_plugins)) {
            $this->initConfigSystemPlugins();
        }

        if (is_null($this->config_controller_modules)) {
            $this->initConfigControllerModules();
        }

        if (is_null($this->config_controller_web_services)) {
            $this->initConfigControllerWebServices();
        }

        if (is_null($this->config_controller_tasks)) {
            $this->initConfigControllerTasks();
        }

        if (is_null($this->config_controller_components)) {
            $this->initConfigControllerComponents();
        }

        // observe for plugin startups - to obtain their derivatives
        $this->event_dispatcher->connect('plugin.will_startup', $this, 'onPluginWillStartup');
        $this->event_dispatcher->connect('plugin_manager.plugin_configuration_loaded', $this, 'onPluginConfigurationLoaded');
    }

    public function shutdown()
    {
        $this->config_system_plugins =
        $this->config_controller_modules =
        $this->config_controller_web_services =
        $this->config_controller_tasks =
        $this->config_controller_components =
        $this->controller_modules =
        $this->controller_web_services =
        $this->controller_tasks =
        $this->controller_components =
            null;

        parent::shutdown();
    }

    public function onPluginWillStartup(lcEvent $event)
    {
        $plugin = isset($event->params['plugin_instance']) ? $event->params['plugin_instance'] : null;

        if ($plugin) {
            // use the plugin's models
            $models = array();

            // check the configuration
            if ($plugin->getPluginConfiguration() instanceof iSupportsDbModels) {
                $models = (array)$plugin->getPluginConfiguration()->getDbModels();
            }

            // check the plugin itself
            if ($plugin instanceof iSupportsDbModelOperations) {
                $models = array_unique(array_merge($models, (array)$plugin->getUsedDbModels()));
            }

            if ($models && is_array($models)) {
                // notify to anyone who is able to use models
                $this->event_dispatcher->filter(new lcEvent('database_model_manager.use_models', $this), $models);
            }

            unset($models);

            // instantiate the plugin's components
        }
    }

    public function onPluginConfigurationLoaded(lcEvent $event)
    {
        $plugin_name = isset($event->params['name']) ? $event->params['name'] : null;
        $plugin_config = isset($event->params['configuration']) ? $event->params['configuration'] : null;
        $is_enabled = isset($event->params['is_enabled']) ? (bool)$event->params['is_enabled'] : null;

        if (!$plugin_name || !$plugin_config) {
            assert(false);
            return;
        }

        $this->registerPluginClasses($plugin_name, $is_enabled, $plugin_config);
    }

    public function getProjectContext()
    {
        $contexts = array(
            lcSysObj::CONTEXT_PROJECT => array(),
            lcSysObj::CONTEXT_APP => array(),
            lcSysObj::CONTEXT_PLUGIN => array(),
            lcSysObj::CONTEXT_FRAMEWORK => array()
        );

        $project_dir = $this->configuration->getProjectDir();

        // framework
        $contexts[lcSysObj::CONTEXT_FRAMEWORK]['framework'] = ROOT;

        // project itself
        $contexts[lcSysObj::CONTEXT_PROJECT][$this->configuration->getProjectName()] = $project_dir;

        // applications
        $apps = $this->getAvailableProjectApplications();

        if ($apps) {
            foreach ($apps as $app_name => $path) {
                $contexts[lcSysObj::CONTEXT_APP][$app_name] = $path;

                unset($app_name, $path);
            }
        }

        unset($apps);

        // plugins
        $plugins = $this->getSystemPluginDetails();

        if ($plugins) {
            foreach ($plugins as $name => $plugin) {
                $contexts[lcSysObj::CONTEXT_PLUGIN][$name] = $plugin['path'];

                unset($plugin, $name);
            }
        }

        unset($plugins);

        return $contexts;
    }

    public function getProjectControllerModuleDetails()
    {
        return (array)$this->config_controller_modules;
    }

    public function getControllerModuleDetails()
    {
        return array_merge((array)$this->config_controller_modules, (array)$this->controller_modules);
    }

    public function getProjectControllerComponentDetails()
    {
        return (array)$this->config_controller_components;
    }

    public function getControllerComponentDetails()
    {
        return array_merge((array)$this->config_controller_components, (array)$this->controller_components);
    }

    public function getProjectControllerTaskDetails()
    {
        return (array)$this->config_controller_tasks;
    }

    public function getControllerTaskDetails()
    {
        return array_merge((array)$this->config_controller_tasks, (array)$this->controller_tasks);
    }

    public function getProjectControllerWebServiceDetails()
    {
        return (array)$this->config_controller_web_services;
    }

    public function getControllerWebServiceDetails()
    {
        return array_merge((array)$this->config_controller_web_services, (array)$this->controller_web_services);
    }

    public function getSystemPluginDetails()
    {
        return (array)$this->config_system_plugins;
    }

    public function getSystemLoaderDetails()
    {
        return $this->config_system_loaders;
    }

    protected function registerPluginClasses($plugin_name, $is_enabled, lcPluginConfiguration $plugin_config)
    {
        $class_autoloader = $this->class_autoloader;
        $plugin_dir = $plugin_config->getPluginDir();

        // plugin autoload classes - allow even if plugin is disabled
        if ($plugin_config instanceof iSupportsAutoload) {
            $autoload_classes = $plugin_config->getAutoloadClasses();

            if ($autoload_classes && is_array($autoload_classes)) {
                $autoload_classes_ = array();

                foreach ($autoload_classes as $class => $filename) {
                    $filename_ = $plugin_config->getPluginDir() . DS . $filename;

                    if (DO_DEBUG) {
                        if (!file_exists($filename_)) {
                            throw new lcIOException('Plugin autoload class (' . $class . ') with filename: ' . $filename_ . ' does not exist');
                        }
                    }

                    // add to class autoloader
                    if ($class_autoloader) {
                        $class_autoloader->addClass($class, $filename_);
                    }

                    $autoload_classes_[$class] = $filename_;

                    unset($filename, $filename, $class);
                }

                unset($autoload_classes_);
            }

            unset($autoload_classes);
        }

        // plugin database models - allow even if plugin is disabled
        if ($plugin_config instanceof iSupportsDbModels) {
            $models = $plugin_config->getDbModels();

            if ($models && is_array($models)) {
                $path_to_models = $plugin_dir . DS . lcPlugin::MODELS_PATH;

                // notify to anyone who is able to register models
                $this->event_dispatcher->filter(new lcEvent('database_model_manager.register_models', $this, array(
                    'path_to_models' => $path_to_models
                )), $models);

                unset($path_to_models);
            }

            unset($models);
        }

        // plugin loaders - allow even if plugin is disabled
        if ($plugin_config instanceof iSystemLoaderProvider) {
            $loaders = $plugin_config->getSystemLoaders();

            if ($loaders && is_array($loaders)) {
                foreach ($loaders as $loader) {
                    $ld = is_array($loader) ? $loader : array($loader);

                    foreach ($ld as $loader_info) {
                        $details = array(
                            'context_type' => lcSysObj::CONTEXT_PLUGIN,
                            'context_name' => $plugin_name,
                        );

                        $this->addSystemLoader($loader_info, $details);

                        unset($loader_info);
                    }

                    unset($details, $loader);
                }
            }

            unset($loaders);
        }

        // the rest are enabled only if the plugin is enabled
        if ($is_enabled) {
            // plugin web modules
            if ($plugin_config instanceof iWebModuleProvider) {
                $web_modules = $plugin_config->getControllerModules();

                if ($web_modules && is_array($web_modules)) {
                    foreach ($web_modules as $web_module) {
                        $path = $plugin_dir . DS . lcPlugin::MODULES_PATH . DS . $web_module;
                        $details = lcComponentLocator::getControllerModuleContextInfo($web_module, $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerModule($web_module, $details);

                        unset($path, $details, $web_module);
                    }
                }

                unset($web_modules);
            }

            // plugin components
            if ($plugin_config instanceof iComponentProvider) {
                $provided_components = $plugin_config->getControllerComponents();

                if ($provided_components && is_array($provided_components)) {
                    foreach ($provided_components as $provided_component) {
                        $path = $plugin_dir . DS . lcPlugin::COMPONENTS_PATH . DS . $provided_component;
                        $details = lcComponentLocator::getControllerComponentContextInfo($provided_component, $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerComponent($provided_component, $details);

                        unset($path, $details, $provided_component);
                    }
                }

                unset($provided_components);
            }

            // plugin tasks
            if ($plugin_config instanceof iConsoleTaskProvider) {
                $provided_tasks = $plugin_config->getControllerTasks();

                if ($provided_tasks && is_array($provided_tasks)) {
                    foreach ($provided_tasks as $provided_task) {
                        $path = $plugin_dir . DS . lcPlugin::TASKS_PATH;
                        $details = lcComponentLocator::getControllerTaskContextInfo($provided_task, $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerTask($provided_task, $details);

                        unset($path, $details, $provided_task);
                    }
                }

                unset($provided_tasks);
            }

            // plugin web services
            if ($plugin_config instanceof iWebServiceProvider) {
                $provided_web_services = $plugin_config->getControllerWebServices();

                if ($provided_web_services && is_array($provided_web_services)) {
                    foreach ($provided_web_services as $provided_web_service) {
                        $path = $plugin_dir . DS . lcPlugin::WEB_SERVICES_PATH;
                        $details = lcComponentLocator::getControllerWebServiceContextInfo($provided_web_service, $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerWebService($provided_web_service, $details);

                        unset($path, $details, $provided_web_service);
                    }
                }

                unset($provided_web_services);
            }
        }
    }

    private function initConfigControllerModules()
    {
        assert(!$this->config_controller_modules);

        $controllers = array();

        $locations = $this->configuration->getControllerModuleLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getControllerModulesInPath($path, $location);

                $controllers = array_merge($controllers, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_controller_modules = $controllers;
    }

    private function initConfigControllerWebServices()
    {
        assert(!$this->config_controller_web_services);

        $controllers = array();

        $locations = $this->configuration->getControllerWebServiceLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getControllerWebServicesInPath($path, $location);

                $controllers = array_merge($controllers, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_controller_web_services = $controllers;
    }

    private function initConfigControllerTasks()
    {
        assert(!$this->config_controller_tasks);

        $controllers = array();

        $locations = $this->configuration->getControllerTaskLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getControllerTasksInPath($path, $location);

                $controllers = array_merge($controllers, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_controller_tasks = $controllers;
    }

    private function initConfigSystemPlugins()
    {
        assert(!$this->config_system_plugins);

        $plugins = array();

        $locations = $this->configuration->getPluginLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getPluginsInPath($path, $location);

                $plugins = array_merge($plugins, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_system_plugins = $plugins;
    }

    private function initConfigControllerComponents()
    {
        assert(!$this->config_controller_components);

        $controllers = array();

        $locations = $this->configuration->getControllerComponentLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getControllerComponentsInPath($path, $location);

                $controllers = array_merge($controllers, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_controller_components = $controllers;
    }

    public function addSystemLoader($loader_name, array $details)
    {
        if (isset($this->config_system_loaders[$loader_name])) {
            assert(false);
            return;
        }

        $this->config_system_loaders[$loader_name] = $details;
    }

    public function addControllerModule($controller_name, array $details)
    {
        if (isset($this->controller_modules[$controller_name])) {
            assert(false);
            return;
        }

        $this->controller_modules[$controller_name] = $details;
    }

    public function addControllerWebService($controller_name, array $details)
    {
        if (isset($this->controller_web_services[$controller_name])) {
            assert(false);
            return;
        }

        $this->controller_web_services[$controller_name] = $details;
    }

    public function addControllerTask($controller_name, array $details)
    {
        if (isset($this->controller_tasks[$controller_name])) {
            assert(false);
            return;
        }

        $this->controller_tasks[$controller_name] = $details;
    }

    public function addControllerComponent($controller_name, array $details)
    {
        if (isset($this->controller_components[$controller_name])) {
            assert(false);
            return;
        }

        $this->controller_components[$controller_name] = $details;
    }

    protected function getController(array $details)
    {
        // include / validate component
        $filename = $details['path'] . DS . $details['filename'];
        $class_name = $details['class'];
        $controller_name = $details['name'];
        $assets_path = isset($details['assets_path']) ? $details['assets_path'] : null;
        $assets_webpath = isset($details['assets_webpath']) ? $details['assets_webpath'] : null;
        $context_type = isset($details['context_type']) ? $details['context_type'] : null;
        $context_name = isset($details['context_name']) ? $details['context_name'] : null;

        // add to class autoloader
        if (!$this->class_autoloader) {
            throw new lcNotAvailableException('Class autoloader not available');
        }

        $this->class_autoloader->addClass($class_name, $filename);

        if (!class_exists($class_name)) {
            throw new lcSystemException('Controller class not available');
        }

        $instance = new $class_name();

        // check type
        if (!($instance instanceof lcBaseController)) {
            throw new lcSystemException('Invalid controller');
        }

        // set vars
        $instance->setControllerName($controller_name);
        $instance->setControllerFilename($filename);
        $instance->setContextType($context_type);
        $instance->setContextName($context_name);
        $instance->setAssetsPath($assets_path);
        $instance->setAssetsWebpath($assets_webpath);

        return $instance;
    }

    protected function getSystemPlugin(array $details)
    {
        // include / validate component
        $filename = $details['path'] . DS . $details['filename'];
        $class_name = $details['class'];
        $controller_name = $details['name'];
        $context_type = isset($details['context_type']) ? $details['context_type'] : null;
        $context_name = isset($details['context_name']) ? $details['context_name'] : null;

        // add to class autoloader
        if (!$this->class_autoloader) {
            throw new lcNotAvailableException('Class autoloader not available');
        }

        $this->class_autoloader->addClass($class_name, $filename);

        if (!class_exists($class_name)) {
            throw new lcSystemException('Plugin class not available');
        }

        $instance = new $class_name();

        // check type
        if (!($instance instanceof lcPlugin)) {
            throw new lcSystemException('Invalid plugin');
        }

        // set vars
        $instance->setContextType($context_type);
        $instance->setContextName($context_name);
        $instance->setControllerName($controller_name);
        $instance->setControllerFilename($filename);

        return $instance;
    }

    public function getControllerModuleInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)
        fnothing($context_type, $context_name);

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_modules[$controller_name]) ?
            $this->config_controller_modules[$controller_name] :
            (isset($this->controller_modules[$controller_name]) ? $this->controller_modules[$controller_name] : null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        if (!$instance) {
            return null;
        }

        // check type
        if (!($instance instanceof lcWebController)) {
            throw new lcSystemException('Invalid web controller');
        }

        return $instance;
    }

    public function getControllerWebServiceInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)
        fnothing($context_type, $context_name);

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_web_services[$controller_name]) ?
            $this->config_controller_web_services[$controller_name] :
            (isset($this->controller_web_services[$controller_name]) ? $this->controller_web_services[$controller_name] : null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        if (!$instance) {
            return null;
        }

        // check type
        if (!($instance instanceof lcWebServiceController)) {
            throw new lcSystemException('Invalid web service controller');
        }

        return $instance;
    }

    public function getControllerTaskInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)
        fnothing($context_type, $context_name);

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_tasks[$controller_name]) ?
            $this->config_controller_tasks[$controller_name] :
            (isset($this->controller_tasks[$controller_name]) ? $this->controller_tasks[$controller_name] : null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        if (!$instance) {
            return null;
        }

        // check type
        if (!($instance instanceof lcTaskController)) {
            throw new lcSystemException('Invalid task controller');
        }

        return $instance;
    }

    public function getAvailableSystemPlugins()
    {
        return $this->config_system_plugins;
    }

    public function getAvailableProjectApplications()
    {
        $app_locations = $this->configuration->getApplicationLocations();
        $applications = array();

        if ($app_locations) {
            foreach ($app_locations as $location) {
                $path = $location['path'];

                $found_apps = lcComponentLocator::getProjectApplicationsInPath($path);

                if ($found_apps) {
                    foreach ($found_apps as $app) {
                        $applications[$app['name']] = $app['path'];
                        unset($app);
                    }
                }

                unset($location, $path, $found_apps);
            }
        }

        return $applications;
    }

    public function getSystemPluginInstance($plugin_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)
        fnothing($context_type, $context_name);

        if (!$plugin_name) {
            throw new lcInvalidArgumentException('Invalid plugin name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_system_plugins[$plugin_name]) ?
            $this->config_system_plugins[$plugin_name] :
            null;

        if (!$details) {
            return null;
        }

        $instance = $this->getSystemPlugin($details);

        if (!$instance) {
            return null;
        }

        return $instance;
    }

    public function getControllerComponentInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)
        fnothing($context_type, $context_name);

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_components[$controller_name]) ?
            $this->config_controller_components[$controller_name] :
            (isset($this->controller_components[$controller_name]) ? $this->controller_components[$controller_name] : null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        if (!$instance) {
            return null;
        }

        // check type
        if (!($instance instanceof lcComponent)) {
            throw new lcSystemException('Invalid component controller');
        }

        return $instance;
    }

    public function writeClassCache()
    {
        $cached_data = array(
            'config_controller_modules' => $this->config_controller_modules,
            'config_controller_web_services' => $this->config_controller_web_services,
            'config_controller_tasks' => $this->config_controller_tasks,
            'config_controller_components' => $this->config_controller_components,
            'config_system_plugins' => $this->config_system_plugins
        );

        return $cached_data;
    }

    public function readClassCache(array $cached_data)
    {
        $this->config_controller_modules = isset($cached_data['config_controller_modules']) ? $cached_data['config_controller_modules'] : null;
        $this->config_controller_web_services = isset($cached_data['config_controller_web_services']) ? $cached_data['config_controller_web_services'] : null;
        $this->config_controller_tasks = isset($cached_data['config_controller_tasks']) ? $cached_data['config_controller_tasks'] : null;
        $this->config_controller_components = isset($cached_data['config_controller_components']) ? $cached_data['config_controller_components'] : null;
        $this->config_system_plugins = isset($cached_data['config_system_plugins']) ? $cached_data['config_system_plugins'] : null;
    }
}

?>