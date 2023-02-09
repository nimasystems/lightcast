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
class lcSystemComponentFactory extends lcSysObj implements iCacheable
{
    /** @var ?array */
    protected ?array $controllers = null;

    /** @var ?array */
    protected ?array $web_services = null;

    /** @var ?array */
    protected ?array $tasks = null;

    /** @var ?array */
    protected ?array $components = null;

    /** @var ?array */
    protected ?array $action_forms = null;

    // store the ones from configuration separately and merge them later
    // so they can be cached (as we need to scan folder / files to acquire them the first time)

    /** @var ?array */
    private ?array $config_system_loaders = null;

    /** @var ?array */
    private ?array $config_system_plugins = null;

    /** @var ?array */
    private ?array $config_controller_modules = null;

    /** @var ?array */
    private ?array $config_controller_web_services = null;

    /** @var ?array */
    private ?array $config_controller_tasks = null;

    /** @var ?array */
    private ?array $config_controller_components = null;

    /** @var ?array */
    private ?array $config_controller_action_forms = null;

    private array $plugin_roots = [];

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
                $namespace = $location['namespace'];
                $found = lcComponentLocator::getPluginsInPath($path, $namespace, $location);
                $plugins = array_merge($plugins, $found);
                unset($location, $found, $path);
            }
        }

        $this->config_system_plugins = $plugins;
    }

    private function initConfigControllerModules()
    {
        assert(!$this->config_controller_modules);

        $controllers = [];

        $locations = $this->configuration->getModuleLocations();

        if ($locations) {
            foreach ($locations as $location) {
                $path = $location['path'];
                $namespace = $location['namespace'];

                $found = lcComponentLocator::getControllerModulesInPath($path, $namespace, $location);

                $controllers = array_merge($controllers, $found);

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
                $namespace = $location['namespace'];
                $found = lcComponentLocator::getControllerWebServicesInPath($path, $namespace, $location);
                $controllers = array_merge($controllers, $found);

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
                $found = lcComponentLocator::getControllerTasksInPath($location['path'],
                    $location['namespace'], $location);
                $controllers = array_merge($controllers, $found);
                unset($location, $found);
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
                $namespace = $location['namespace'];
                $found = lcComponentLocator::getControllerComponentsInPath($path, $namespace, $location);
                $controllers = array_merge($controllers, $found);
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
                $namespace = $location['namespace'];
                $found = lcComponentLocator::getActionFormsInPath($path, $namespace, $location);
                $forms = array_merge($forms, $found);
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
        $plugin = $event->params['plugin_instance'] ?? null;

        if ($plugin) {
            // use the plugin's models
            $models = [];

            // check the configuration
            $plcfg = $plugin->getPluginConfiguration();

            if ($plcfg instanceof iSupportsDbModels) {
                $models = (array)$plcfg->getDbModels();
            }

            unset($models);

            // instantiate the plugin's components
        }
    }

    public function onPluginConfigurationLoaded(lcEvent $event)
    {
        $plugin_name = $event->params['name'];
        $plugin_namespace = $event->params['namespace'];
        $plugin_config = $event->params['configuration'] ?? null;
        $is_enabled = isset($event->params['is_enabled']) ? (bool)$event->params['is_enabled'] : null;

        if (!$plugin_name || !$plugin_namespace || !$plugin_config) {
            return;
        }

        $this->registerPluginClasses($plugin_name, $plugin_namespace, $is_enabled, $plugin_config);
    }

    /**
     * @param $plugin_name
     * @param string $plugin_namespace
     * @param $is_enabled
     * @param lcPluginConfiguration $plugin_config
     * @return void
     * @throws lcIOException
     * @throws lcLogicException
     * @throws lcSystemException
     */
    protected function registerPluginClasses($plugin_name, string $plugin_namespace,
                                             $is_enabled, lcPluginConfiguration $plugin_config)
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
                            throw new lcIOException('Plugin autoload class (' . $class . ') with filename: ' .
                                $filename_ . ' does not exist');
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
                $web_modules = $plugin_config->getControllerModules();

                if ($web_modules && is_array($web_modules)) {
                    foreach ($web_modules as $web_module) {
                        $web_module_camelized = !ctype_upper($web_module[0]) ? lcInflector::camelize($web_module) :
                            $web_module;
                        $path = $plugin_dir . DS . lcPlugin::MODULES_PATH . DS . $web_module_camelized;
                        $details = lcComponentLocator::getControllerModuleContextInfo($web_module_camelized,
                            $plugin_namespace . '\\Modules',
                            $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerModule($web_module_camelized, $details);

                        unset($path, $details, $web_module, $web_module_camelized);
                    }
                }

                unset($web_modules);
            }

            // plugin components
            if ($plugin_config instanceof iComponentProvider) {
                $provided_components = $plugin_config->getControllerComponents();

                if ($provided_components && is_array($provided_components)) {
                    foreach ($provided_components as $provided_component) {
                        $provided_component_camelized = !ctype_upper($provided_component[0]) ? lcInflector::camelize($provided_component) :
                            $provided_component;
                        $path = $plugin_dir . DS . lcPlugin::COMPONENTS_PATH . DS . $provided_component_camelized;
                        $details = lcComponentLocator::getControllerComponentContextInfo($provided_component_camelized,
                            $plugin_namespace . '\\Components',
                            $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerComponent($provided_component_camelized, $details);

                        unset($path, $details, $provided_component, $provided_component_camelized);
                    }
                }

                unset($provided_components);
            }

            // plugin tasks
            if ($plugin_config instanceof iConsoleTaskProvider) {
                /** @var iConsoleTaskProvider $plugin_config */
                $provided_tasks = $plugin_config->getControllerTasks();

                if ($provided_tasks) {
                    foreach ($provided_tasks as $provided_task) {
                        $provided_task_camelized = !ctype_upper($provided_task[0]) ? lcInflector::camelize($provided_task) :
                            $provided_task;
                        $path = $plugin_dir . DS . lcPlugin::TASKS_PATH;
                        $details = lcComponentLocator::getControllerTaskContextInfo($provided_task_camelized,
                            $plugin_namespace . '\\Tasks',
                            $path);
                        $details['context_type'] = lcSysObj::CONTEXT_PLUGIN;
                        $details['context_name'] = $plugin_name;

                        $this->addControllerTask($provided_task_camelized, $details);

                        unset($path, $details, $provided_task, $provided_task_camelized);
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
                        $details = lcComponentLocator::getControllerWebServiceContextInfo($provided_web_service,
                            $plugin_namespace . '\\WebServices',
                            $path);
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
                $action_forms = $plugin_config->getActionForms();

                if ($action_forms && is_array($action_forms)) {
                    foreach ($action_forms as $action_form) {
                        $path = $plugin_dir . DS . lcPlugin::ACTION_FORMS_PATH . DS . $action_form;
                        $details = lcComponentLocator::getActionFormContextInfo($action_form,
                            $plugin_namespace . '\\Forms',
                            $path);
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

    /**
     * @param $loader_name
     * @param array $details
     * @return void
     */
    public function addSystemLoader($loader_name, array $details)
    {
        if (isset($this->config_system_loaders[$loader_name])) {
            return;
        }

        $this->config_system_loaders[$loader_name] = $details;
    }

    /**
     * @param $controller_name
     * @param array $details
     * @return void
     */
    public function addControllerModule($controller_name, array $details)
    {
        if (isset($this->controllers[$controller_name])) {
            return;
        }

        $this->controllers[$controller_name] = $details;
    }

    /**
     * @param $controller_name
     * @param array $details
     * @return void
     * @throws lcSystemException
     */
    public function addControllerComponent($controller_name, array $details)
    {
        if (isset($this->components[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->components[$controller_name] = $details;
    }

    /**
     * @param $controller_name
     * @param array $details
     * @return void
     * @throws lcSystemException
     */
    public function addControllerTask($controller_name, array $details)
    {
        if (isset($this->tasks[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->tasks[$controller_name] = $details;
    }

    /**
     * @param $controller_name
     * @param array $details
     * @return void
     * @throws lcSystemException
     */
    public function addControllerWebService($controller_name, array $details)
    {
        if (isset($this->web_services[$controller_name])) {
            throw new lcSystemException('Duplicate controller being added: ' . $controller_name);
        }

        $this->web_services[$controller_name] = $details;
    }

    /**
     * @param $form_name
     * @param array $details
     * @return void
     * @throws lcSystemException
     */
    public function addActionForm($form_name, array $details)
    {
        if (isset($this->action_forms[$form_name])) {

            if (DO_DEBUG) {
                throw new lcSystemException('Action form is already registered: ' . $form_name);
            }

            return;
        }

        $this->action_forms[$form_name] = $details;
    }

    /**
     * @return array|array[]
     * @throws lcInvalidArgumentException
     */
    public function getProjectContext(): array
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

    /**
     * @return array
     * @throws lcInvalidArgumentException
     */
    public function getAvailableProjectApplications(): array
    {
        $app_locations = $this->configuration->getApplicationLocations();
        $applications = [];

        if ($app_locations) {
            foreach ($app_locations as $location) {
                $path = $location['path'];
                $namespace = $location['namespace'];

                $found_apps = lcComponentLocator::getProjectApplicationsInPath($path, $namespace);

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

    /**
     * @return array
     */
    public function getSystemPluginDetails(): array
    {
        return (array)$this->config_system_plugins;
    }

    /**
     * @return array
     */
    public function getProjectControllerModuleDetails(): array
    {
        return (array)$this->config_controller_modules;
    }

    /**
     * @return array
     */
    public function getActionFormDetails(): array
    {
        return array_merge((array)$this->config_controller_action_forms, (array)$this->action_forms);
    }

    /**
     * @return array
     */
    public function getControllerModuleDetails(): array
    {
        return array_merge((array)$this->config_controller_modules, (array)$this->controllers);
    }

    /**
     * @return array
     */
    public function getProjectControllerComponentDetails(): array
    {
        return (array)$this->config_controller_components;
    }

    /**
     * @return array
     */
    public function getControllerComponentDetails(): array
    {
        return array_merge((array)$this->config_controller_components, (array)$this->components);
    }

    /**
     * @return array
     */
    public function getProjectControllerTaskDetails(): array
    {
        return (array)$this->config_controller_tasks;
    }

    /**
     * @return array
     */
    public function getControllerTaskDetails(): array
    {
        return array_merge((array)$this->config_controller_tasks, (array)$this->tasks);
    }

    /**
     * @return array
     */
    public function getProjectControllerWebServiceDetails(): array
    {
        return (array)$this->config_controller_web_services;
    }

    /**
     * @return array
     */
    public function getControllerWebServiceDetails(): array
    {
        return array_merge((array)$this->config_controller_web_services, (array)$this->web_services);
    }

    /**
     * @return array|null
     */
    public function getSystemLoaderDetails(): ?array
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
    public function getControllerModuleInstance($controller_name, $context_type = null, $context_name = null): ?lcWebController
    {
        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        // first check config, then others
        $details = $this->config_controller_modules[$controller_name] ?? ($this->controllers[$controller_name] ?? null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        // check type
        if (!($instance instanceof lcWebController)) {
            throw new lcSystemException('Invalid web controller');
        }

        return $instance;
    }

    /**
     * @param array $details
     * @return lcBaseController
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    protected function getController(array $details): lcBaseController
    {
        // include / validate component
        $filename = $details['path'] . DS . $details['filename'];
        $class_name = $details['class'];
        $controller_name = $details['name'];
        $assets_path = $details['assets_path'] ?? null;
        $assets_webpath = $details['assets_webpath'] ?? null;
        $context_type = $details['context_type'] ?? null;
        $context_name = $details['context_name'] ?? null;

        // add to class autoloader
        if (!$this->class_autoloader) {
            throw new lcNotAvailableException('Class autoloader not available');
        }

        $this->class_autoloader->addClass($class_name, $filename);

        if (!class_exists($class_name)) {
            throw new lcSystemException('Controller class not available (' . $class_name . ')');
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
    public function getActionFormInstance($form_name): ?lcBaseActionForm
    {
        // first check config, then others
        $details = $this->config_controller_action_forms[$form_name] ?? ($this->action_forms[$form_name] ?? null);

        if (!$details) {
            return null;
        }

        return $this->getActionForm($details);
    }

    /**
     * @param array $details
     * @return lcBaseActionForm
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    protected function getActionForm(array $details): lcBaseActionForm
    {
        // include / validate component
        $filename = $details['path'] . DS . $details['filename'];
        $class_name = $details['class'];
        $context_type = $details['context_type'] ?? null;
        $context_name = $details['context_name'] ?? null;

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
    public function getControllerWebServiceInstance($controller_name, $context_type = null, $context_name = null): ?lcWebServiceController
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        // first check config, then others
        $details = $this->config_controller_web_services[$controller_name] ?? ($this->web_services[$controller_name] ?? null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

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
    public function getControllerTaskInstance($controller_name, $context_type = null, $context_name = null): ?lcTaskController
    {
        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        $camelized_controller_name = !ctype_upper($controller_name[0]) ? lcInflector::camelize($controller_name) :
            $controller_name;

        // first check config, then others
        $details = $this->config_controller_tasks[$camelized_controller_name] ?? ($this->tasks[$camelized_controller_name] ?? null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        // check type
        if (!($instance instanceof lcTaskController)) {
            throw new lcSystemException('Invalid task controller');
        }

        return $instance;
    }

    /**
     * @return array|null
     */
    public function getAvailableSystemPlugins(): ?array
    {
        return $this->config_system_plugins;
    }

    /**
     * @param $plugin_name
     * @param $context_type
     * @param $context_name
     * @return lcPlugin|null
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    public function getSystemPluginInstance($plugin_name, $context_type = null, $context_name = null): ?lcPlugin
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$plugin_name) {
            throw new lcInvalidArgumentException('Invalid plugin name');
        }

        // first check config, then others
        $details = $this->config_system_plugins[$plugin_name] ?? null;

        if (!$details) {
            return null;
        }

        return $this->getSystemPlugin($details);
    }

    /**
     * @param array $details
     * @return string|null
     */
    protected function getPluginRoot(array $details): ?string
    {
        $path = $details['path'];
        $plugin_class = $details['class'];

        $filenames = [$details['filename']];

        if (isset($details['additional_filenames'])) {
            $filenames = array_merge($details['additional_filenames'], $filenames);
        }

        $found_filename = null;

        foreach ($filenames as $filename) {
            $p = $path . DS . $filename;
            if (file_exists($p)) {
                $found_filename = $p;
                break;
            }
            unset($filename);
        }

        $this->plugin_roots[$plugin_class] = $found_filename;

        return $found_filename;
    }

    /**
     * @param array $details
     * @return lcPlugin
     * @throws lcNotAvailableException
     * @throws lcSystemException
     */
    protected function getSystemPlugin(array $details): lcPlugin
    {
        $class_name = $details['class'];
        $controller_name = $details['name'];
        $context_type = $details['context_type'] ?? null;
        $context_name = $details['context_name'] ?? null;
        $filename = $this->getPluginRoot($details);

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
    public function getControllerComponentInstance($controller_name, $context_type = null, $context_name = null): ?lcComponent
    {
        // TODO: LC 1.6 implementation pending - ability to specify controllers from specific contexts (plugins, etc)

        if (!$controller_name) {
            throw new lcInvalidArgumentException('Invalid controller name');
        }

        // first check config, then others
        $details = $this->config_controller_components[$controller_name] ?? ($this->components[$controller_name] ?? null);

        if (!$details) {
            return null;
        }

        $instance = $this->getController($details);

        // check type
        if (!($instance instanceof lcComponent)) {
            throw new lcSystemException('Invalid component controller');
        }

        return $instance;
    }

    /**
     * @return array
     */
    public function writeClassCache(): array
    {
        return [
            'config_controller_modules' => $this->config_controller_modules,
            'config_controller_action_forms' => $this->config_controller_action_forms,
            'config_controller_web_services' => $this->config_controller_web_services,
            'config_controller_tasks' => $this->config_controller_tasks,
            'config_controller_components' => $this->config_controller_components,
            'config_system_plugins' => $this->config_system_plugins,
            'plugin_roots' => $this->plugin_roots,
        ];
    }

    public function readClassCache(array $cached_data)
    {
        $this->config_controller_modules = $cached_data['config_controller_modules'] ?? null;
        $this->config_controller_action_forms = $cached_data['config_controller_action_forms'] ?? null;
        $this->config_controller_web_services = $cached_data['config_controller_web_services'] ?? null;
        $this->config_controller_tasks = $cached_data['config_controller_tasks'] ?? null;
        $this->config_controller_components = $cached_data['config_controller_components'] ?? null;
        $this->config_system_plugins = $cached_data['config_system_plugins'] ?? null;
        $this->plugin_roots = $cached_data['plugin_roots'] ?? [];
    }
}
