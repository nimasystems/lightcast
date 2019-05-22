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
 * Class lcApp
 * @method lcPluginManager getPluginManager
 * @method lcController getController
 * @method lcI18n getI18n
 * @method lcLogger getLogger
 * @method iCacheStore getCache
 * @method lcDatabaseModelManager getDatabaseModelManager
 */
class lcApp extends lcObj
{
    const FRAMEWORK_CACHE_FILENAME = 'source/assets/misc/autoload/autoload.php';
    const FRAMEWORK_CACHE_VAR_NAME = '_lc_class_cache';
    const FRAMEWORK_CACHE_VERSION_VAR_NAME = '_lc_class_cache_version';

    const SYSTEM_OBJECTS_CACHE_PREFIX = 'sys_';
    const LOADERS_OBJECT_CACHE_PREFIX = 'loader_';

    /**
     * @var lcApp
     */
    private static $app;

    /** @var iAppDelegate */
    protected $delegate;

    /**
     * @var lcProjectConfiguration|lcApplicationConfiguration
     */
    protected $configuration;

    /**
     * @var lcEventDispatcher
     */
    protected $event_dispatcher;

    /**
     * @var lcClassAutoloader
     */
    protected $class_autoloader;

    /**
     * @var lcLocalCacheManager
     */
    protected $local_cache_manager;

    /**
     * @var lcLogger
     */
    protected $logger;

    /**
     * @var lcErrorHandler
     */
    protected $error_handler;

    /**
     * @var lcProfiler
     */
    protected $profiler;

    /**
     * @var lcSysObj[]
     */
    protected $loader_instances;

    /**
     * @var lcSysObj[]
     */
    protected $initialized_objects;

    /**
     * @var array
     */
    protected $platform_capabilities;

    private $initialized;
    private $no_shutdown;

    public static function bootstrap(lcApplicationConfiguration $configuration)
    {
        $app = lcApp::getInstance();
        $app->initialize($configuration);
        return $app;
    }

    public static function getInstance()
    {
        if (!self::$app) {
            self::$app = new lcApp();
        }
        return self::$app;
    }

    public function initialize(lcApplicationConfiguration $configuration)
    {
        if ($this->initialized) {
            return false;
        }

        // init profiler
        $this->profiler = new lcProfiler();
        $this->profiler->start();

        /**
         * It is important that we register our own shutdown function here
         * and not rely on __destruct of this class as we cannot control
         * the order of destruction (for example autoloaders get destructed before
         * this class would get destructed).
         * This way we assure this class will be shutdown first!
         */
        register_shutdown_function([$this, 'shutdown']);

        $this->configuration = $configuration;
        $this->configuration->initDefaultSystemObjects();

        // assign the delegate
        $this->delegate = $this->configuration->getAppDelegate();

        $this->event_dispatcher = $configuration->getEventDispatcher();
        $this->class_autoloader = $configuration->getClassAutoloader();

        if (!$this->event_dispatcher || !$this->configuration) {
            throw new lcSystemException('Invalid configuration / event dispatcher');
        }

        // set back to configuration
        $this->configuration->setEventDispatcher($this->event_dispatcher);
        $this->configuration->setClassAutoloader($this->class_autoloader);

        // inform the delegate
        if ($this->delegate) {
            $this->delegate->willBeginInitializingApp($this);
        }

        // init general defines
        $this->initDefines();

        //$this->recreateFrameworkAutoloadCache();

        // init event dispatcher / class autoloader
        $this->event_dispatcher->initialize();

        // order of initialization below IS important!

        // init error_handler
        $this->initErrorHandler();

        // init cache / local cache manager
        $this->initCache();

        // init class autoloader
        $this->initClassAutoloader();

        // initialize config objects
        $this->createSystemObjects();

        // init and read configuration
        $this->initConfiguration();

        // initialize database model manager
        $this->initDatabaseModelManager();

        // initialize controller factory
        $this->initSystemComponentFactory();

        // init plugin manager and plugins before loaders
        $this->initPluginManager();

        // register db models
        $this->registerDbModels();

        // start the plugins which should be initialized right away
        $this->startRuntimePlugins();

        // create all loaders - but do not initialize them yet!
        $this->createLoaders();

        // get a logger instance - if available
        $this->logger = $this->getLogger();

        // pass logger onto system objects
        $this->setSystemObjectsLogger();

        // initialize all system objects now
        $this->initializeSystemObjects();

        $this->initialized = true;

        if (DO_DEBUG) {
            if ($this->logger) {
                $this->logger->debug('[' . $this->configuration->getUniqueId() . '] initialized completely');
            }
        }

        // register data providers
        // TODO: Are these obsolete?
        $this->event_dispatcher->registerProvider('app.error_handler', $this, 'getErrorHandler');
        $this->event_dispatcher->registerProvider('loader.plugin_manager', $this, 'getPluginManager');

        // first let plugins initialize themselves - then the rest of the listeners
        $this->notifyPluginsOfAppInitialization();

        $this->configuration->getEventDispatcher()->notify(new lcEvent('app.startup', $this));

        // inform the delegate
        if ($this->delegate) {
            $this->delegate->didInitializeApp($this);
        }

        return true;
    }

    private function initDefines()
    {
        $configuration = $this->configuration;

        // app name
        $app_name = $configuration->getApplicationName();

        if (!$app_name) {
            throw new lcSystemException('Invalid application name');
        }

        // app name
        if (!defined('APP_NAME')) {
            define('APP_NAME', $app_name);
        } else {
            assert(APP_NAME == $app_name);
        }

        // project version
        if (!defined('APP_VER')) {
            define('APP_VER', $this->configuration->getVersion());
        }

        // debugging
        if (!defined('DO_DEBUG')) {
            define('DO_DEBUG', $configuration->isDebugging());
        }

        /*
         * Required path contstants
        */
        if (!defined('DIR_APP')) {
            define('DIR_APP', $configuration->getProjectDir());
        }

        if (!defined('TMP_PATH')) {
            define('TMP_PATH', $configuration->getTempDir());
        }

        if (!defined('CACHE_PATH')) {
            define('CACHE_PATH', $configuration->getCacheDir());
        }

        // lib dir path
        set_include_path(get_include_path() . PATH_SEPARATOR . $configuration->getProjectDir() . DS . 'lib');
    }

    private function initErrorHandler()
    {
        // init the error handler if available
        $this->error_handler = $this->configuration->getErrorHandler();

        // attach error handlers
        set_error_handler([$this, 'handlePHPError']);
        set_exception_handler([$this, "handleException"]);

        // Raise assertions only in debug mode
        if ($this->error_handler && $this->error_handler->supportsAssertions()) {
            assert_options(ASSERT_CALLBACK, [$this, 'handleAssertion']);
        }

        if ($this->error_handler) {
            $this->error_handler->setEventDispatcher($this->event_dispatcher);
            $this->error_handler->setConfiguration($this->configuration);
            $this->error_handler->initialize();
        }
    }

    private function initCache()
    {
        $configuration = $this->configuration;

        $cache = $configuration->getCache();
        $local_cache_manager = $configuration->getLocalCacheManager();

        // initialize cache first
        if ($cache) {
            $cache->setEventDispatcher($this->event_dispatcher);
            $cache->setConfiguration($this->configuration);
            $cache->initialize();
        }

        // initialize local cache manager
        if ($local_cache_manager) {
            $local_cache_manager->setEventDispatcher($this->event_dispatcher);
            $local_cache_manager->setConfiguration($this->configuration);
            $local_cache_manager->setCache($cache);
            $local_cache_manager->setCacheEnabled($this->configuration->getUseClassCache());
            $local_cache_manager->initialize();

            $this->local_cache_manager = $local_cache_manager;
        }
    }

    private function initClassAutoloader()
    {
        $class_autoloader = $this->class_autoloader;

        if (!$class_autoloader) {
            return;
        }

        // initialize it
        $class_autoloader->setEventDispatcher($this->event_dispatcher);
        $class_autoloader->setConfiguration($this->configuration);

        // class autoloader caching
        $local_cache_manager = $this->local_cache_manager;

        if ($local_cache_manager && ($class_autoloader instanceof iCacheable)) {
            $local_cache_manager->registerCacheableObject($class_autoloader, self::SYSTEM_OBJECTS_CACHE_PREFIX . 'class_autoloader');
        }

        $class_autoloader->initialize();

        if (!$class_autoloader->getRegisteredClasses()) {
            // if there are no class registrations yet - cache is not in - so reload lightcast classes
            $class_cache_filename = ROOT . DS . self::FRAMEWORK_CACHE_FILENAME;
            $class_cache_varname = self::FRAMEWORK_CACHE_VAR_NAME;
            $class_cache_version_varname = self::FRAMEWORK_CACHE_VERSION_VAR_NAME;

            $registered_classes = [];
            $cache_version = 0;
            $should_recreate_cache = false;

            if (@include($class_cache_filename)) {
                $cache_version = isset($$class_cache_version_varname) ? (int)$$class_cache_version_varname : 0;
                $registered_classes = isset($$class_cache_varname) ? $$class_cache_varname : null;
            } else {
                $should_recreate_cache = true;
            }

            $should_recreate_cache = $should_recreate_cache || $cache_version != LC_VER_REVISION;

            // in debugging mode we check the cache version against the LC revision number
            // if they are different we recreate the cache
            if (DO_DEBUG && $should_recreate_cache) {
                $this->recreateFrameworkAutoloadCache();

                // reread the file
                if (@include($class_cache_filename)) {
                    //$cache_version = isset($$class_cache_version_varname) ? (int)$$class_cache_version_varname : 0;
                    $registered_classes = isset($$class_cache_varname) ? $$class_cache_varname : null;
                }
            }

            if ($registered_classes) {
                $class_autoloader->addClasses($registered_classes);
            }

            unset($class_cache_filename, $class_cache_varname, $class_cache_version_varname, $registered_classes, $registered_classes);
        }

        // add classes from configuration
        if ($this->configuration instanceof iSupportsAutoload) {
            $this->addAutoloadClassesFromObject($this->configuration);
        }

        // add classes from project configuration
        if ($this->configuration->getProjectConfiguration() instanceof iSupportsAutoload) {
            $this->addAutoloadClassesFromObject($this->configuration->getProjectConfiguration());
        }

        // register to listen to 'class_autoloader.class_not_found' event
        $this->event_dispatcher->connect('class_autoloader.class_not_found', $this, 'onClassLoaderFailedLoadingClass');
    }

    protected function recreateFrameworkAutoloadCache()
    {
        $fname = ROOT . DS . self::FRAMEWORK_CACHE_FILENAME;

        /** @noinspection PhpIncludeInspection */
        require_once(ROOT . DS . 'source/libs/autoload/lcAutoloadCacheTool.class.php');

        $dirs = [
            ROOT . DS . 'source' . DS . 'libs',
            ROOT . DS . 'source' . DS . '3rdparty' . DS . 'propel' . DS . 'runtime' . DS . 'lib',
        ];

        $tool = new lcAutoloadCacheTool($dirs, $fname, self::FRAMEWORK_CACHE_VAR_NAME, self::FRAMEWORK_CACHE_VERSION_VAR_NAME);
        $tool->setCacheVersion(LC_VER_REVISION);
        // TODO: Investigate the speed difference in requiring files with absolute paths against paths with relative paths
        $tool->setWriteBasePath(false);
        $tool->createCache();
    }

    protected function addAutoloadClassesFromObject(iSupportsAutoload $obj)
    {
        $class_autoloader = $this->class_autoloader;
        $autoload_classes = $obj->getAutoloadClasses();

        if ($autoload_classes && is_array($autoload_classes)) {
            foreach ($autoload_classes as $class_name => $filename) {
                $filename = ($filename{0} == '/') ? $filename : DIR_APP . DS . $filename;
                $class_autoloader->addClass($class_name, $filename);
                unset($class_name, $filename);
            }
        }
    }

    private function createSystemObjects()
    {
        $this->initialized_objects = [];

        $configuration = $this->configuration;
        assert(!is_null($configuration));

        // system objects
        $config_objects = $configuration->getSystemObjectInstances();

        if ($config_objects) {
            // load and initialize all system objects
            foreach ($config_objects as $object_name => $object) {
                if (!$object) {
                    continue;
                }

                try {
                    $this->configureSystemObject($object, $object_name, get_class($object), true);
                } catch (Exception $e) {
                    throw new lcSystemException('Could not initialize system object (' . $object_name . '): ' .
                        $e->getMessage(),
                        $e->getCode(),
                        $e);
                }

                unset($object_name, $object);
            }
        }
    }

    protected function configureSystemObject(lcSysObj $obj, $object_type, $class_name, $add_local_cache = false)
    {
        assert(!is_null($obj) && !is_null($class_name) && !is_null($object_type));

        $configuration = $this->configuration;
        $event_dispatcher = $this->event_dispatcher;

        // set configuration
        $obj->setConfiguration($configuration);

        // set dispatcher
        $obj->setEventDispatcher($event_dispatcher);

        // set class autoloader
        if ($obj !== $this->class_autoloader) {
            $obj->setClassAutoloader($this->class_autoloader);
        }

        // autoload classes
        if ($obj instanceof iSupportsAutoload) {
            $this->addAutoloadClassesFromObject($obj);
        }

        // event observers
        if ($obj instanceof iEventObserver) {
            $event_dispatcher->addObserver($obj);
        }

        // register to class cache
        if ($add_local_cache) {
            if ($this->local_cache_manager && ($obj instanceof iCacheable)) {
                $this->local_cache_manager->registerCacheableObject($obj, self::SYSTEM_OBJECTS_CACHE_PREFIX . $object_type);
            }
        }

        $this->initialized_objects[$object_type] = $obj;

        // send event - initialize
        //$this->event_dispatcher->notify(new lcEvent($object_type . '.initialize', $obj));

        // TODO: Change this
        if ($obj instanceof lcResidentObj) {
            $obj->attachRegisteredEvents();
        }
    }

    private function initConfiguration()
    {
        $configuration = $this->configuration;

        /** @var lcProjectConfiguration $project_configuration */
        $project_configuration = $configuration->getProjectConfiguration();

        // verify if the target / minimum versions are met
        $target_version = $project_configuration->getTargetFrameworkVersion();
        $minimum_version = $project_configuration->getMinimumFrameworkVersion();

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

        // assign the configuration to be the delegate of the app
        // if it implements iAppDelegate and the current delegate is null
        if (!$this->delegate && ($project_configuration instanceof iAppDelegate)) {
            $this->delegate = $project_configuration;
        }

        // register to class cache
        if ($this->local_cache_manager && ($configuration instanceof iCacheable)) {
            $this->local_cache_manager->registerCacheableObject($configuration, self::SYSTEM_OBJECTS_CACHE_PREFIX . 'configuration');
        }

        // configuration - execute before
        $configuration->executeBefore();

        if ($project_configuration) {
            $project_configuration->executeBefore();
        }

        $configuration->initialize();

        // set timezone from configuration
        $this->setSystemTimezone();

        // set php limits
        if ($time_limit = (int)$this->configuration['settings.time_limit']) {
            set_time_limit($time_limit);
            unset($time_limit);
        }

        if ($memory_limit = (string)$this->configuration['settings.memory_limit']) {
            ini_set('memory_limit', $memory_limit);
            unset($memory_limit);
        }

        if ($project_configuration) {
            $project_configuration->executeAfter();
        }

        // configuration - execute after
        $configuration->executeAfter();
    }

    protected function setSystemTimezone()
    {
        // set timezone
        $tz = $this->configuration['settings.timezone'] ? (string)$this->configuration['settings.timezone'] :
            lcVm::date_default_timezone_get();

        if (!lcVm::date_default_timezone_set($tz)) {
            throw new lcSystemException('Cannot set system timezone: ' . $tz);
        }

        unset($tz);
    }

    private function initDatabaseModelManager()
    {
        $manager = $this->configuration->getDatabaseModelManager();

        if (!$manager) {
            return;
        }

        if ($this->configuration->getShouldDisableModels()) {
            return;
        }

        $this->initializeSystemObject('database_model_manager', $manager);
    }

    private function initializeSystemObject($object_type, lcSysObj $obj)
    {
        // if already initialized - do nothing else
        if ($obj->getHasInitialized()) {
            return;
        }

        $obj->setPluginManager($this->getPluginManager());

        $obj->initialize();

        // notify - startup
        $this->event_dispatcher->notify(new lcEvent($object_type . '.startup', $obj));
        $this->event_dispatcher->attachConnectListener($object_type . '.startup', $obj);
    }

    private function initSystemComponentFactory()
    {
        $factory = $this->configuration->getSystemComponentFactory();

        if (!$factory) {
            return;
        }

        $this->initializeSystemObject('system_component_factory', $factory);
    }

    private function initPluginManager()
    {
        /** @var lcPluginManager $plugin_manager */
        $plugin_manager = isset($this->initialized_objects['plugin_manager']) ? $this->initialized_objects['plugin_manager'] : null;

        if (!$plugin_manager) {
            return;
        }

        $plugin_manager->setAppContext($this);
        $plugin_manager->setSystemComponentFactory($this->configuration->getSystemComponentFactory());
        $plugin_manager->setDatabaseModelManager($this->configuration->getDatabaseModelManager());
        $plugin_manager->setShouldLoadPlugins($this->configuration->getShouldLoadPlugins());

        $this->initializeSystemObject('plugin_manager', $plugin_manager);

        // register capabilities provided by plugins
    }

    protected function registerDbModels()
    {
        // add propel model classes to autoloader
        // for objects which support iSupportsPropelDb
        $db_manager = isset($this->initialized_objects['database_model_manager']) ? $this->initialized_objects['database_model_manager'] : null;

        if (!$db_manager || !($db_manager instanceof iDatabaseModelManager)) {
            return;
        }

        /** @var lcProjectConfiguration $project_configuration */
        $project_configuration = $this->configuration->getProjectConfiguration();

        if ($project_configuration instanceof iSupportsDbModels) {
            try {
                $models = $project_configuration->getDbModels();

                if ($models && is_array($models)) {
                    $db_manager->registerModelClasses($project_configuration->getModelsDir(), $models);
                }
            } catch (Exception $e) {
                throw new lcDatabaseException('Could not register database models from project: ' . $e->getMessage(),
                    $e->getCode(),
                    $e);
            }
        }

        // use application/project-wide models if configuration supports it
        if ($this->configuration instanceof iSupportsDbModelOperations) {
            $db_manager->useModels($this->configuration->getUsedDbModels());
        }
    }

    protected function startRuntimePlugins()
    {
        /** @var lcPluginManager $plugin_manager */
        $plugin_manager = isset($this->initialized_objects['plugin_manager']) ? $this->initialized_objects['plugin_manager'] : null;

        if (!$plugin_manager) {
            return;
        }

        $runtime_plugins = $plugin_manager->getRuntimePlugins();

        if (!$runtime_plugins || !is_array($runtime_plugins)) {
            return;
        }

        // initialize the plugins which must be started on app startup
        foreach ($runtime_plugins as $plugin_name) {
            try {
                $plugin_manager->initializePlugin($plugin_name);
            } catch (Exception $e) {
                throw new lcPluginException('Could not initialize plugin (' . $plugin_name . '): ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }

            unset($plugin_name);
        }
    }

    protected function createLoaders()
    {
        $local_cache_manager = $this->local_cache_manager;
        $db_manager = $this->configuration->getDatabaseModelManager();
        $loaders = $this->configuration['loaders'];
        $loading_order = lcLoadersConfigHandler::getLoadingOrderConfig();
        $loader_requirements = lcLoadersConfigHandler::getLoaderRequirements();

        if (!$loaders || !is_array($loaders) || !$loading_order) {
            return;
        }

        $component_loaders = $this->configuration->getSystemComponentFactory()->getSystemLoaderDetails();
        $plugin_manager = $this->configuration->getPluginManager();

        $enabled_plugins = (array)$plugin_manager->getEnabledPlugins();

        // load all orders
        foreach ($loading_order as $loader) {
            try {
                $requirements = isset($loader_requirements[$loader]) ? $loader_requirements[$loader] : null;

                $config_enabled_key = isset($requirements['config_enabled_key']) ? $requirements['config_enabled_key'] : null;

                // verify if the loader is required
                if (!isset($loaders[$loader]) && isset($requirements['required']) && $requirements['required'] == true) {
                    throw new lcSystemException('Cannot figure the necessary classname');
                }

                if (!isset($loaders[$loader]) || !is_string($loaders[$loader])) {
                    continue;
                }

                // if the loader should be enabled or not based on configuration
                $loader_enabled = true;

                if ($config_enabled_key) {
                    $loader_enabled = isset($this->configuration[$config_enabled_key]) ? (bool)$this->configuration[$config_enabled_key] : true;
                }

                $class_name = (string)$loaders[$loader];
                $class_name = !$class_name || !$loader_enabled ? null : $class_name;

                if (!$class_name) {
                    continue;
                }

                if (!class_exists($class_name)) {
                    throw new lcSystemException('Class (' . $class_name . ') does not exist');
                }

                /** @var lcResidentObj $obj */
                $obj = new $class_name();

                if (!($obj instanceof lcResidentObj)) {
                    continue;
                }

                $this->configureSystemObject($obj, $loader, $class_name);

                // if it comes from a plugin - assign proper context type / name
                if (isset($component_loaders[$class_name])) {
                    $context_type = $component_loaders[$class_name]['context_type'];
                    $context_name = $component_loaders[$class_name]['context_name'];

                    $obj->setContextType($context_type);
                    $obj->setContextName($context_name);
                    $obj->setPluginManager($plugin_manager);

                    if (!($obj instanceof lcI18n)) {
                        $obj->setTranslationContext($context_type, $context_name);
                    }

                    // if context is plugin - attach the plugin as a parent object
                    if ($context_type == lcSysObj::CONTEXT_PLUGIN) {
                        // check if plugin is enabled first
                        if (!in_array($context_name, $enabled_plugins)) {
                            throw new lcSystemException('System loader (' . $loader . '): \'' . $class_name .
                                '\' requires that plugin: \'' . $context_name . '\' is enabled at runtime');
                        }

                        // if enabled - try go acquire an instance
                        $plugin_instance = $plugin_manager->getPlugin($context_name, true);

                        if (!$plugin_instance) {
                            throw new lcSystemException('System loader (' . $loader . '): \'' . $class_name .
                                '\' is contained within a plugin: \'' . $context_name . '\' which could not be initialized');
                        }

                        $obj->setParentPlugin($plugin_instance);

                        unset($plugin_instance);
                    }

                    unset($context_type, $context_name);
                }

                $this->loader_instances[$loader] = $obj;

                // register into local class cache
                if ($local_cache_manager && ($obj instanceof iCacheable)) {
                    /** @noinspection PhpParamsInspection */
                    $local_cache_manager->registerCacheableObject($obj, self::LOADERS_OBJECT_CACHE_PREFIX . $loader);
                }

                // db models usage
                if ($db_manager && $obj instanceof iSupportsDbModelOperations) {
                    /** @var iSupportsDbModelOperations $obj */
                    $models = $obj->getUsedDbModels();

                    if ($models && is_array($models)) {
                        $db_manager->useModels($models);
                    }

                    unset($models);
                }

                // provided capabilities
                if ($obj instanceof iProvidesCapabilities) {
                    /** @var iProvidesCapabilities $obj */
                    $capabilities = $obj->getCapabilities();

                    if ($capabilities && is_array($capabilities)) {
                        $this->platform_capabilities = array_merge((array)$this->platform_capabilities, $capabilities);
                    }
                }

                // register object provider
                // TODO: Remove these
                $this->event_dispatcher->registerProvider('loader.' . $loader, $this, 'getLoader');

            } catch (Exception $e) {
                throw new lcSystemException('Could not initialize loader (' . $loader . '): ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }

            unset($loader, $class_name, $loader_enabled, $requirements, $config_enabled_key);
        }
    }

    private function setSystemObjectsLogger()
    {
        $logger = $this->logger;

        if (!$logger) {
            return;
        }

        // system objects
        $config_objects = $this->configuration->getSystemObjectInstances();

        if ($config_objects) {
            // load and initialize all system objects
            foreach ($config_objects as $object_name => $object) {
                if (!$object) {
                    continue;
                }

                if ($object instanceof lcSysObj) {
                    $object->setLogger($logger);
                }

                unset($object_name, $object);
            }
        }

        // pass to configuration
        if ($this->configuration instanceof lcSysObj) {
            $this->configuration->setLogger($logger);
        }
    }

    private function initializeSystemObjects()
    {
        // notify all current objects about configuration startup
        $objects = $this->initialized_objects;

        if ($objects) {
            // set loaders onto objects
            foreach ($objects as $type => $obj) {
                // pass required objects to loader
                $this->setLoadersOntoObject($obj);
                unset($type, $obj);
            }

            // initialize the objects now
            foreach ($objects as $type => $obj) {
                try {
                    $this->initializeSystemObject($type, $obj);
                } catch (Exception $e) {
                    throw new lcSystemException('Could not initialize system object (' . $type . '): ' .
                        $e->getMessage(),
                        $e->getCode(),
                        $e);
                }

                unset($type, $obj);
            }
        }

        $this->event_dispatcher->notify(new lcEvent('app.loaders_initialized', $this));
    }

    public function setLoadersOntoObject(lcSysObj $app_obj)
    {
        $loader_instances = $this->loader_instances;
        $loaders = array_keys($loader_instances);

        if (!$loaders || !$loader_instances) {
            return;
        }

        $request = isset($loader_instances['request']) ? $loader_instances['request'] : null;
        $response = isset($loader_instances['response']) ? $loader_instances['response'] : null;
        $routing = isset($loader_instances['router']) ? $loader_instances['router'] : null;
        $database_manager = isset($loader_instances['database_manager']) ? $loader_instances['database_manager'] : null;
        $storage = isset($loader_instances['storage']) ? $loader_instances['storage'] : null;
        $user = isset($loader_instances['user']) ? $loader_instances['user'] : null;
        $logger = isset($loader_instances['logger']) ? $loader_instances['logger'] : null;
        $i18n = isset($loader_instances['i18n']) ? $loader_instances['i18n'] : null;
        $mailer = isset($loader_instances['mailer']) ? $loader_instances['mailer'] : null;
        $data_storage = isset($loader_instances['data_storage']) ? $loader_instances['data_storage'] : null;
        $cache = isset($loader_instances['cache']) ? $loader_instances['cache'] : null;

        // this is a lot faster manually rather than dynamically calling
        // from configuration with inflector!

        if ($app_obj instanceof lcAppObj) {
            $app_obj->setRequest(($app_obj !== $request) ? $request : null);
            $app_obj->setResponse(($app_obj !== $response) ? $response : null);
            $app_obj->setRouting(($app_obj !== $routing) ? $routing : null);
            $app_obj->setDatabaseManager(($app_obj !== $database_manager) ? $database_manager : null);
            $app_obj->setStorage(($app_obj !== $storage) ? $storage : null);
            $app_obj->setUser(($app_obj !== $user) ? $user : null);
            $app_obj->setMailer(($app_obj !== $mailer) ? $mailer : null);
            $app_obj->setDataStorage(($app_obj !== $data_storage) ? $data_storage : null);
            $app_obj->setCache(($app_obj !== $cache) ? $cache : null);
        }

        $app_obj->setLogger(($app_obj !== $logger) ? $logger : null);
        $app_obj->setI18n(($app_obj !== $i18n) ? $i18n : null);
    }

    private function notifyPluginsOfAppInitialization()
    {
        /** @var lcPluginManager $plugin_manager */
        $plugin_manager = isset($this->initialized_objects['plugin_manager']) ? $this->initialized_objects['plugin_manager'] : null;

        if (!$plugin_manager) {
            return;
        }

        $plugin_manager->initializePluginsForAppStartup();
    }

    public function translateInContext($string, $context_type, $context_name, $translation_domain = null)
    {
        if (!$context_type || !$context_name || !$string) {
            return $string;
        }

        /** @var lcI18n $i18n */
        $i18n = lcApp::getInstance()->getI18n();
        return ($i18n ? $i18n->translateInContext($context_type, $context_name, $string, $translation_domain) : $string);
    }

    public function getDelegate()
    {
        return $this->delegate;
    }

    public function setDelegate(iAppDelegate $delegate)
    {
        $this->delegate = $delegate;
    }

    public function onClassLoaderFailedLoadingClass(lcEvent $event)
    {
        static $loop_protect;

        if ($loop_protect) {
            // do not loop in here
            return;
        }

        $loop_protect = true;

        $loaded = false;
        $class_name = $event->params['class_name'];

        // do something to handle it here

        $loop_protect = false;

        // if not loaded - throw an exception so we don't end up with a fatal error
        if (!$loaded) {
            // if debugging reload the framework cache right away so the class 'may'
            // be available on the next reload
            if (DO_DEBUG) {
                $this->recreateFrameworkAutoloadCache();
            }

            throw new lcSystemException('Could not find class: ' . $class_name);
        }
    }

    public function getIsInitialized()
    {
        return $this->initialized;
    }

    public function isDebugging()
    {
        return $this->configuration->isDebugging();
    }

    public function setNoShutdown($no_sh)
    {
        $this->no_shutdown = $no_sh;
    }

    public function shutdown()
    {
        // protect against double calling this method!
        static $already_called;

        if ($already_called) {
            assert(false);
            return;
        }

        $already_called = true;

        try {
            // inform the delegate
            if ($this->delegate) {
                $this->delegate->willShutdownApp($this);
            }

            // shutdown event dispatcher
            try {
                // notify everyone that the app is shutting down
                if ($this->event_dispatcher) {
                    $this->event_dispatcher->notify(new lcEvent('app.shutdown', $this));
                }
            } catch (Exception $e) {
                if (DO_DEBUG) {
                    throw new lcSystemException('Error while notifying event dispatcher on shutdown: ' . $e->getMessage(), $e->getCode(), $e);
                }
            }

            if ($this->no_shutdown) {
                $this->initialized = false;
                return;
            }

            // shutdown all loader objects as we created them
            $this->shutdownLoaderInstances();

            // disconnect all event dispatcher listeners / remove all observers
            if ($this->event_dispatcher) {
                $this->event_dispatcher->removeObservers();
                $this->event_dispatcher->disconnectAllListeners();
            }

            // shutdown configuration
            if ($this->configuration) {
                $this->configuration->shutdown();
            }

            $this->error_handler =
            $this->local_cache_manager =
            $this->initialized_objects =
            $this->loader_instances =
            $this->configuration =
            $this->event_dispatcher =
            $this->class_autoloader =
            $this->logger =
                null;

            // restore error handlers
            restore_error_handler();
            restore_exception_handler();

            $this->initialized = false;

            // inform the delegate
            if ($this->delegate) {
                $this->delegate->didShutdownApp($this);
            }
        } catch (Exception $e) {
            if (DO_DEBUG) {
                throw new lcSystemException('Unhandled exception while shutting down the app: ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }
        }
    }

    protected function shutdownLoaderInstances()
    {
        $loader_instances = $this->loader_instances;

        if (!$loader_instances) {
            return;
        }

        // shutdown in a reversed initialization way
        $ak = array_reverse(array_keys($loader_instances));

        foreach ($ak as $obj_type) {
            try {
                $obj = $loader_instances[$obj_type];

                if (!$obj->getHasInitialized()) {
                    unset($this->loader_instances[$obj_type]);
                    continue;
                }

                // send event - shutdown
                if ($this->event_dispatcher) {
                    $this->event_dispatcher->notify(new lcEvent($obj_type . '.shutdown', $obj));
                }

                // shutdown the object
                $obj->shutdown();
                unset($this->loader_instances[$obj_type]);
            } catch (Exception $e) {
                if (DO_DEBUG) {
                    // this cannot be handled otherwise
                    // we cannot be certain of what will happen after all objects start taking off
                    // and if errorHandler is available at all
                    // so in release mode - we silently skip this error
                    die('Could not shutdown loaders properly (' . $obj_type . '): ' . $e->getMessage() . ': ' . $e->getTraceAsString());
                }
            }

            unset($obj_type, $obj);
        }

        $this->loader_instances = null;
    }

    public function dispatch()
    {
        if (!$this->initialized) {
            throw new lcSystemException('App not initialized');
        }

        /** @var lcFrontController $ctrl */
        $ctrl = isset($this->initialized_objects['controller']) ? $this->initialized_objects['controller'] : null;

        if (!$ctrl) {
            throw new lcNotAvailableException('Controller not found');
        }

        if (!($ctrl instanceof lcFrontController)) {
            throw new lcSystemException('Front controller is not valid');
        }

        $ctrl->setSystemComponentFactory($this->configuration->getSystemComponentFactory());
        $ctrl->setDatabaseModelManager($this->configuration->getDatabaseModelManager());
        $ctrl->setPluginManager($this->configuration->getPluginManager());
        $ctrl->setTranslationContext(lcSysObj::CONTEXT_APP, $this->configuration->getApplicationName());
        $ctrl->dispatch();
    }

    public function handlePHPError($errno, $errmsg, $filename, $linenum, $vars)
    {
        if (!$this->error_handler) {
            throw new lcPHPException($errmsg, $errno, $filename, $linenum);
        }

        $this->error_handler->handlePHPError($errno, $errmsg, $filename, $linenum, $vars);
    }

    public function handleAssertion($file, $line, $code)
    {
        if ($this->error_handler) {
            $this->error_handler->handleAssertion($file, $line, $code);
        }

        // if not handled throw an exception
        throw new lcAssertException($file, $line, $code);
    }

    /**
     * @param Exception|Error $exception
     * @throws Error
     * @throws Exception
     */
    public function handleException($exception)
    {
        // notify listeners - allow them to intercept the exception and
        // do something else
        $should_be_handled = true;
        $event = null;

        try {
            $event = $this->event_dispatcher->filter(new lcEvent('app.exception', $this,
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                    'domain' => (($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN),
                    'code' => $exception->getCode(),
                    'cause' => $exception->getPrevious(),
                    'trace' => $exception->getTraceAsString(),
                    'system_snapshot' => $this->getDebugSnapshot(),
                ]), $should_be_handled);
        } catch (Exception $e) {
            // an exception should not be thrown in the exception handlers
            if ($this->error_handler) {
                $this->error_handler->handleException($e);
            }

            // if not handled throw it
            throw $e;
        }

        if ($event && $event->isProcessed()) {
            $should_be_handled = (bool)$event->getReturnValue();
        }

        if ($should_be_handled) {
            if ($this->error_handler) {
                $this->error_handler->handleException($exception);
            }

            // if not handled throw it
            throw $exception;
        }
    }

    public function getDebugSnapshot($short = false)
    {
        $snapshot = [
            'is_debugging' => DO_DEBUG,
            'lc_version' => LC_VER,
            'app_version' => APP_VER,
            'memory_usage' => lcSys::getMemoryUsage(true),
            'php_ver' => lcSys::getPhpVer(),
            'php_api' => lcSys::get_sapi(),
        ];

        $local_cache = $this->configuration->getCache();
        $local_cache_name = $local_cache ? get_class($local_cache) : null;
        $local_cache_used = $local_cache ? true : false;

        $snapshot['cache_used'] = $local_cache_used;

        if ($local_cache_used) {
            $snapshot['cache_name'] = $local_cache_name;
        }

        unset($local_cache, $local_cache_name, $local_cache_used);

        // fetch config debug info
        $snapshot['config'] = $short ? $this->configuration->getShortDebugInfo() : $this->configuration->getDebugInfo();

        // fetch debugging info from all loaders
        $system_objects = $this->initialized_objects;

        if ($system_objects) {
            foreach ($system_objects as $type => $system_object) {
                if (!$system_object instanceof iDebuggable) {
                    continue;
                }

                $dbg = $short ? $system_object->getShortDebugInfo() : $system_object->getDebugInfo();
                $debug_info = array_filter((array)$dbg);

                $snapshot['system_objects'][$type] = $debug_info;

                unset($system_object, $type, $dbg);
            }
        }

        return $snapshot;
    }

    public function __call($method, array $params = null)
    {
        // get an instance of a system object if available
        $start = substr($method, 0, 3);

        if ($start == 'get') {
            $str = substr($method, 3, strlen($method));
            $obj_name = lcInflector::underscore($str);

            $obj = isset($this->initialized_objects[$obj_name]) ? $this->initialized_objects[$obj_name] : null;
            return $obj;
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return parent::__call($method, $params);
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    public function getProfiler()
    {
        return $this->profiler;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration(lcApplicationConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getPlugin($plugin_name)
    {
        /** @var lcPluginManager $plugin_manager */
        $plugin_manager = $this->getPluginManager();
        return $plugin_manager ? $plugin_manager->getPlugin($plugin_name) : null;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getContext()
    {
        return $this;
    }

    public function getSystemObjects()
    {
        return $this->initialized_objects;
    }

    public function getPlatformCapabilities()
    {
        return $this->platform_capabilities;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getLoaders()
    {
        return $this->initialized_objects;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getLoader(lcEvent $event)
    {
        $loader_name = $event->event_name;

        if (!isset($loader_name)) {
            return false;
        }

        $loader_name = substr($loader_name, strlen('loader.'), strlen($loader_name));

        if (!isset($loader_name)) {
            return false;
        }

        if (!isset($this->initialized_objects[$loader_name])) {
            return false;
        }

        $loader = $this->initialized_objects[$loader_name];

        return $loader;
    }
}
