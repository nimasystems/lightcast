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

class lcSystemComponentFactory extends lcSysObj implements iCacheable
{
    /** @var array */
    protected $controllers;

    /** @var array */
    protected $web_services;

    /** @var array */
    protected $tasks;

    /** @var array */
    protected $components;

    /** @var array */
    protected $action_forms;

    // store the ones from configuration separately and merge them later
    // so they can be cached (as we need to scan folder / files to acquire them the first time)

    /** @var array */
    private $config_system_loaders;

    /** @var array */
    private $config_system_plugins;

    /** @var array */
    private $config_controller_modules;

    /** @var array */
    private $config_controller_web_services;

    /** @var array */
    private $config_controller_tasks;

    /** @var array */
    private $config_controller_components;

    /** @var array */
    private $config_controller_action_forms;

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

        if (is_null($this->config_controller_action_forms)) {
            $this->initConfigActionForms();
        }

        // observe for plugin startups - to obtain their derivatives
        $this->event_dispatcher->connect('plugin.will_startup', $this, 'onPluginWillStartup');
        $this->event_dispatcher->connect('plugin_manager.plugin_configuration_loaded', $this, 'onPluginConfigurationLoaded');
    }

    private function initConfigSystemPlugins()
    {
        assert(!$this->config_system_plugins);

        $plugins = [];

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

    private function initConfigControllerModules()
    {
        assert(!$this->config_controller_modules);

        $controllers = [];

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

        $controllers = [];

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

        $controllers = [];

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

    private function initConfigControllerComponents()
    {
        assert(!$this->config_controller_components);

        $controllers = [];

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

    private function initConfigActionForms()
    {
        assert(!$this->config_controller_action_forms);

        $forms = [];

        $locations = $this->configuration->getActionFormLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];

                $found = lcComponentLocator::getActionFormsInPath($path, $location);

                $forms = array_merge($forms, (array)$found);

                unset($location, $found, $path);
            }
        }

        $this->config_controller_action_forms = $forms;
    }

    public function shutdown()
    {
        $this->config_system_plugins =
        $this->config_controller_modules =
        $this->config_controller_action_forms =
        $this->config_controller_web_services =
        $this->config_controller_tasks =
        $this->config_controller_components =
        $this->controllers =
        $this->web_services =
        $this->tasks =
        $this->components =
        $this->action_forms =
            null;

        parent::shutdown();
    }

    public function onPluginWillStartup(lcEvent $event)
    {
        /** @var lcPlugin $plugin */
        $plugin = isset($event->params['plugin_instance']) ? $event->params['plugin_instance'] : null;

        if ($plugin) {
            // use the plugin's models
            $models = [];

            // check the configuration
            $plcfg = $plugin->getPluginConfiguration();

            if ($plcfg instanceof iSupportsDbModels) {
                $models = (array)$plcfg->getDbModels();
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

    protected function registerPluginClasses($plugin_name, $is_enabled, lcPluginConfiguration $plugin_config)
    {
        $class_autoloader = $this->class_autoloader;
        $plugin_dir = $plugin_config->getPluginDir();

        // plugin autoload classes - allow even if plugin is disabled
        if ($plugin_config instanceof iSupportsAutoload) {
            $autoload_classes = $plugin_config->getAutoloadClasses();

            if ($autoload_classes && is_array($autoload_classes)) {
                $autoload_classes_ = [];

                foreach ($autoload_classes as $class => $filename) {
                    /** @var lcPluginConfiguration $plugin_config */
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
            /** @var iSupportsDbModels $plugin_config */
            $models = $plugin_config->getDbModels();

            if ($models && is_array($models)) {
                $path_to_models = $plugin_dir . DS . lcPlugin::MODELS_PATH;

                // notify to anyone who is able to register models
                $this->event_dispatcher->filter(new lcEvent('database_model_manager.register_models', $this, [
                    'path_to_models' => $path_to_models,
                ]), $models);

                unset($path_to_models);
            }

            unset($models);
        }

        // plugin loaders - allow even if plugin is disabled
        if ($plugin_config instanceof iSystemLoaderProvider) {
            /** @var iSystemLoaderProvider $plugin_config */
            $loaders = $plugin_config->getSystemLoaders();

            if ($loaders && is_array($loaders)) {
                foreach ($loaders as $loader) {
                    $ld = is_array($loader) ? $loader : [$loader];

                    foreach ($ld as $loader_info) {
                        $details = [
                            'context_type' => lcSysObj::CONTEXT_PLUGIN,
                            'context_name' => $plugin_name,
                        ];

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
                /** @var iWebModuleProvider $plugin_config */
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
                /** @var iComponentProvider $plugin_config */
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
                /** @var iConsoleTaskProvider $plugin_config */
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
                /** @var iWebServiceProvider $plugin_config */
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

            // plugin action forms
            if ($plugin_config instanceof iActionFormProvider) {
                /** @var iActionFormProvider $plugin_config */
                $action_forms = $plugin_config->getActionForms();

                if ($action_forms && is_array($action_forms)) {
                    foreach ($action_forms as $action_form) {
                        $path = $plugin_dir . DS . lcPlugin::ACTION_FORMS_PATH . DS . $action_form;
                        $details = lcComponentLocator::getActionFormContextInfo($action_form, $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addActionForm($action_form, $details);

                        unset($path, $details, $action_form);
                    }
                }

                unset($action_forms);
            }
        }
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
        if (isset($this->controllers[$controller_name])) {
            assert(false);
            return;
        }

        $this->controllers[$controller_name] = $details;
    }

    public function addControllerComponent($controller_name, array $details)
    {
        if (isset($this->components[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->components[$controller_name] = $details;
    }

    public function addControllerTask($controller_name, array $details)
    {
        if (isset($this->tasks[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->tasks[$controller_name] = $details;
    }

    public function addControllerWebService($controller_name, array $details)
    {
        if (isset($this->web_services[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->web_services[$controller_name] = $details;
    }

    public function addActionForm($form_name, array $details)
    {
        if (isset($this->action_forms[$form_name])) {
            assert(false);
            return;
        }

        $this->action_forms[$form_name] = $details;
    }

    public function getProjectContext()
    {
        $contexts = [
            lcSysObj::CONTEXT_PROJECT => [],
            lcSysObj::CONTEXT_APP => [],
            lcSysObj::CONTEXT_PLUGIN => [],
            lcSysObj::CONTEXT_FRAMEWORK => [],
        ];

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

    public function getAvailableProjectApplications()
    {
        $app_locations = $this->configuration->getApplicationLocations();
        $applications = [];

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

    public function getSystemPluginDetails()
    {
        return (array)$this->config_system_plugins;
    }

    public function getProjectControllerModuleDetails()
    {
        return (array)$this->config_controller_modules;
    }

    public function getActionFormDetails()
    {
        return array_merge((array)$this->config_controller_action_forms, (array)$this->action_forms);
    }

    public function getControllerModuleDetails()
    {
        return array_merge((array)$this->config_controller_modules, (array)$this->controllers);
    }

    public function getProjectControllerComponentDetails()
    {
        return (array)$this->config_controller_components;
    }

    public function getControllerComponentDetails()
    {
        return array_merge((array)$this->config_controller_components, (array)$this->components);
    }

    public function getProjectControllerTaskDetails()
    {
        return (array)$this->config_controller_tasks;
    }

    public function getControllerTaskDetails()
    {
        return array_merge((array)$this->config_controller_tasks, (array)$this->tasks);
    }

    public function getProjectControllerWebServiceDetails()
    {
        return (array)$this->config_controller_web_services;
    }

    public function getControllerWebServiceDetails()
    {
        return array_merge((array)$this->config_controller_web_services, (array)$this->web_services);
    }

    public function getSystemLoaderDetails()
    {
        return $this->config_system_loaders;
    }

    /**
     * @param $controller_name
     * @param null $context_type
     * @param null $context_name
     * @return lcWebController
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public function getControllerModuleInstance($controller_name, $context_type = null, $context_name = null)
    {
        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_modules[$controller_name]) ?
            $this->config_controller_modules[$controller_name] :
            (isset($this->controllers[$controller_name]) ? $this->controllers[$controller_name] : null);

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

    /**
     * @param $form_name
     * @return lcBaseActionForm
     * @throws lcNotAvailableException
     * @throws lcSystemException
     * @internal param null $context_type
     * @internal param null $context_name
     */
    public function getActionFormInstance($form_name)
    {
        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_action_forms[$form_name]) ?
            $this->config_controller_action_forms[$form_name] :
            (isset($this->action_forms[$form_name]) ? $this->action_forms[$form_name] : null);

        if (!$details) {
            return null;
        }

        return $this->getActionForm($details);
    }

    protected function getActionForm(array $details)
    {
        // include / validate component
        $filename = $details['path'] . DS . $details['filename'];
        $class_name = $details['class'];
        $context_type = isset($details['context_type']) ? $details['context_type'] : null;
        $context_name = isset($details['context_name']) ? $details['context_name'] : null;

        // add to class autoloader
        if (!$this->class_autoloader) {
            throw new lcNotAvailableException('Class autoloader not available');
        }

        $this->class_autoloader->addClass($class_name, $filename);

        if (!class_exists($class_name)) {
            throw new lcSystemException('Action Form class not available');
        }

        /** @var lcBaseActionForm $instance */
        $instance = new $class_name();

        // check type
        if (!($instance instanceof lcBaseActionForm)) {
            throw new lcSystemException('Invalid action form');
        }

        // set vars
        $instance->setTranslationContext($context_type, $context_name);

        return $instance;
    }

    /**
     * @param $controller_name
     * @param null $context_type
     * @param null $context_name
     * @return lcWebServiceController
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public function getControllerWebServiceInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_web_services[$controller_name]) ?
            $this->config_controller_web_services[$controller_name] :
            (isset($this->web_services[$controller_name]) ? $this->web_services[$controller_name] : null);

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

    /**
     * @param $controller_name
     * @param null $context_type
     * @param null $context_name
     * @return lcTaskController
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public function getControllerTaskInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_tasks[$controller_name]) ?
            $this->config_controller_tasks[$controller_name] :
            (isset($this->tasks[$controller_name]) ? $this->tasks[$controller_name] : null);

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

    public function getSystemPluginInstance($plugin_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

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

    /**
     * @param $controller_name
     * @param null $context_type
     * @param null $context_name
     * @return lcComponent
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public function getControllerComponentInstance($controller_name, $context_type = null, $context_name = null)
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $details = null;

        // first check config, then others
        $details = isset($this->config_controller_components[$controller_name]) ?
            $this->config_controller_components[$controller_name] :
            (isset($this->components[$controller_name]) ? $this->components[$controller_name] : null);

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
        $cached_data = [
            'config_controller_modules' => $this->config_controller_modules,
            'config_controller_action_forms' => $this->config_controller_action_forms,
            'config_controller_web_services' => $this->config_controller_web_services,
            'config_controller_tasks' => $this->config_controller_tasks,
            'config_controller_components' => $this->config_controller_components,
            'config_system_plugins' => $this->config_system_plugins,
        ];

        return $cached_data;
    }

    public function readClassCache(array $cached_data)
    {
        $this->config_controller_modules = isset($cached_data['config_controller_modules']) ? $cached_data['config_controller_modules'] : null;
        $this->config_controller_action_forms = isset($cached_data['config_controller_action_forms']) ? $cached_data['config_controller_action_forms'] : null;
        $this->config_controller_web_services = isset($cached_data['config_controller_web_services']) ? $cached_data['config_controller_web_services'] : null;
        $this->config_controller_tasks = isset($cached_data['config_controller_tasks']) ? $cached_data['config_controller_tasks'] : null;
        $this->config_controller_components = isset($cached_data['config_controller_components']) ? $cached_data['config_controller_components'] : null;
        $this->config_system_plugins = isset($cached_data['config_system_plugins']) ? $cached_data['config_system_plugins'] : null;
    }
}
