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
 * @changed $Id: lcProjectConfiguration.class.php 1489 2013-12-12 14:27:36Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1489 $
 */
class lcProjectConfiguration extends lcConfiguration implements iSupportsDbModels, iSupportsDbModelOperations,
    iSupportsAutoload, iAppDelegate, iSupportsVersions
{
    const DEFAULT_BASE_CONFIG_DIR = 'default';

    const DEFAULT_PLUGINS_LOCATION = 'addons/plugins';
    const DEFAULT_PROJECT_NAME = 'default';
    const CLASS_CACHE_RESET_KEY_SUFFIX = '_should_reset';

    const TMP_DIR_NAME = 'tmp';
    const MODELS_DIR_NAME = 'models';
    const CACHE_DIR_NAME = 'cache';

    protected $app_delegate;
    protected $class_autoloader;
    protected $event_dispatcher;
    protected $plugin_manager;
    protected $error_handler;
    protected $cache;
    protected $local_cache_manager;
    protected $system_component_factory;
    protected $database_model_manager;

    protected $use_models;
    protected $use_classes;

    protected $use_class_cache = true;

    protected $project_db_models;

    protected $config_environment;
    protected $config_version;

    protected $app_root_dir;
    protected $root_dir;
    protected $tmp_dir;

    protected $debugging;

    public function __construct()
    {
        parent::__construct();

        // setup error reporting early
        //$this->setupErrorReporting();

        // set vars
        $this->root_dir = ROOT;
        $this->config_environment = self::DEFAULT_BASE_CONFIG_DIR;
    }

    public function initialize()
    {
        parent::initialize();

        if (!$this->app_root_dir) {
            throw new lcConfigException('Project dir not set');
        }

        $this->tmp_dir = !$this->tmp_dir ? $this->getProjectDir() . DS . self::TMP_DIR_NAME : $this->tmp_dir;

        $this->set('settings.debug', $this->debugging);
    }

    public function shutdown()
    {
        if ($this->event_dispatcher) {
            $this->event_dispatcher->shutdown();
        }

        if ($this->class_autoloader) {
            $this->class_autoloader->shutdown();
        }

        if ($this->error_handler) {
            $this->error_handler->shutdown();
        }

        if ($this->cache) {
            $this->cache->shutdown();
        }

        if ($this->local_cache_manager) {
            $this->local_cache_manager->shutdown();
        }

        if ($this->plugin_manager) {
            $this->plugin_manager->shutdown();
        }

        if ($this->system_component_factory) {
            $this->system_component_factory->shutdown();
        }

        if ($this->database_model_manager) {
            $this->database_model_manager->shutdown();
        }

        $this->event_dispatcher =
        $this->class_autoloader =
        $this->error_handler =
        $this->cache =
        $this->local_cache_manager =
        $this->plugin_manager =
        $this->system_component_factory =
        $this->database_model_manager =
        $this->project_db_models =
        $this->use_models =
            null;

        parent::shutdown();
    }

    public function executeBefore()
    {
        // subclassers may override this method to execute code before the initialization of the config
    }

    public function executeAfter()
    {
        // subclassers may override this method to execute code after the initialization of the config
    }

    public function setConfigEnvironment($environment)
    {
        $this->config_environment = $environment;
    }

    public function getConfigEnvironment()
    {
        return $this->config_environment;
    }

    public function getBaseConfigDir()
    {
        $ret = $this->getConfigDir() . DS . $this->config_environment;
        return $ret;
    }

    public function getShouldUseCachedConfigurationData()
    {
        return $this->use_class_cache;
    }

    protected function setupErrorReporting()
    {
        // setup error reporting
        $display_errors = (int)$this->isDebugging();

        // setup error reporting
        error_reporting(E_ALL | E_STRICT | error_reporting());
        ini_set('display_errors', $display_errors);

        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_BAIL, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_ACTIVE, $display_errors);
    }

    public function initDefaultSystemObjects()
    {
        $this->class_autoloader = $this->getDefaultClassAutoloader();
        $this->event_dispatcher = $this->getDefaultEventDispatcher();
        $this->cache = $this->getDefaultCacheInstance();
        $this->plugin_manager = $this->getDefaultPluginManager();
        $this->error_handler = $this->getDefaultErrorHandler();
        $this->local_cache_manager = $this->getDefaultLocalCacheManager();
        $this->system_component_factory = $this->getDefaultSystemComponentFactory();
        $this->database_model_manager = $this->getDefaultModelManager();
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = array(
            'class_autoloader' => ($this->class_autoloader ? get_class($this->class_autoloader) : null),
            'error_handler' => ($this->error_handler ? get_class($this->error_handler) : null),
            'cache' => ($this->cache ? get_class($this->cache) : null),
            'root_dir' => $this->root_dir,
            'app_root_dir' => $this->app_root_dir,
            'is_debugging' => $this->debugging,
            'project_name' => $this->getProjectName(),
        );

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug = array(
            'project_name' => $this->getProjectName(),
        );

        return $debug;
    }

    public function getSystemObjectInstances()
    {
        $instances = array();

        $system_objects = $this->getSystemObjectNames();

        foreach ($system_objects as $name) {
            $instances[$name] = $this->$name;
        }

        return $instances;
    }

    public function getSystemObjectNames()
    {
        // order of system objects IS important
        // they are loaded initially in the same order!
        $config_objects = array(
            'error_handler',
            'cache',
            'local_cache_manager',
            'database_model_manager',
            'system_component_factory',
            'plugin_manager',
        );

        return $config_objects;
    }

    public function getProjectName()
    {
        // may be overriden by subclassers
        return self::DEFAULT_PROJECT_NAME;
    }

    protected function loadConfigurationData()
    {
        // do not load the configuration yet - allow application configurations to do it
    }

    public function getProjectConfigDir()
    {
        return null;
    }

    public function getConfigHandleMap()
    {
        // maps the configuration values to handlers
        $config_map = array(
            array('handler' => 'project', 'dirs' => array($this->getBaseConfigDir(), $this->getConfigDir()), 'config_key' => 'project'),
            array('handler' => 'databases', 'dirs' => array($this->getBaseConfigDir(), $this->getConfigDir()), 'config_key' => 'databases'),
        );

        return $config_map;
    }

    public function getConfigVersion()
    {
        return $this->config_version;
    }

    public function setConfigVersion($config_version)
    {
        $this->config_version = (int)$config_version;
        assert($this->config_version);
    }

    public function getVersion()
    {
        return $this->getMajorVersion() . '.' .
        $this->getMinorVersion() . '.' .
        $this->getBuildVersion() . '.' .
        $this->getRevisionVersion();
    }

    public function getMajorVersion()
    {
        // subclassers may override this method to return the major version of the project
        return 1;
    }

    public function getMinorVersion()
    {
        // subclassers may override this method to return the minor version of the project
        return 0;
    }

    public function getBuildVersion()
    {
        // subclassers may override this method to return the build version of the project
        return iSupportsVersions::BUILD_PRODUCTION;
    }

    public function getRevisionVersion()
    {
        // subclassers may override this method to return the revision version of the project
        return 0;
    }

    public function willBeginInitializingApp(lcApp $app)
    {
        // subclassers may override this method to execute code before the initialization of the app
        fnothing($app);
    }

    public function didInitializeApp(lcApp $app)
    {
        // subclassers may override this method to execute code after the initialization of the app
        fnothing($app);
    }

    public function willShutdownApp(lcApp $app)
    {
        // subclassers may override this method to execute code before the shutdown of the app
        fnothing($app);
    }

    public function didShutdownApp(lcApp $app)
    {
        // subclassers may override this method to execute code after the shutdown of the app
        // WARNING: At this stage the configuration object will had been already shutdown!
        fnothing($app);
    }

    public function getAutoloadClasses()
    {
        // subclassers may override this method to return an array of classes which should
        // be autoloaded upon initialization
        return $this->use_classes;
    }

    public function getDbModels()
    {
        // subclassers may override this method to return a different set of models

        if (!$this->project_db_models) {
            // initialize the models - scan the models folder
            $t = lcDirs::searchDir($this->getModelsDir(), true);
            $models = array();

            if ($t) {
                foreach ($t as $obj) {
                    $r = str_replace('.php', '', $obj['name']);

                    if (lcStrings::endsWith($r, 'Peer') || lcStrings::endsWith($r, 'Query')) {
                        continue;
                    }

                    $models[] = lcInflector::underscore($r);
                    unset($obj, $r);
                }
            }

            $this->project_db_models = $models;
        }

        return $this->project_db_models;
    }

    public function setUseClassCache($use_class_cache = true)
    {
        $this->use_class_cache = $use_class_cache;
    }

    public function getUseClassCache()
    {
        return $this->use_class_cache;
    }

    public function getAppDelegate()
    {
        return $this->app_delegate;
    }

    public function setAppDelegate(iAppDelegate $app_delegate)
    {
        $this->app_delegate = $app_delegate;
    }

    protected function getDefaultPluginManager()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'plugins' . DS . 'lcPluginManager.class.php');

        $plugin_manager = new lcPluginManager();

        return $plugin_manager;
    }

    protected function getDefaultLocalCacheManager()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'lcLocalCacheManager.class.php');

        $cache_manager = new lcLocalCacheManager();

        return $cache_manager;
    }

    protected function getDefaultErrorHandler()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'iErrorHandler.class.php');
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'lcErrorHandler.class.php');

        $error_handler = new lcErrorHandler();

        return $error_handler;
    }

    protected function getDefaultEventDispatcher()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEvent.class.php');
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'iEventObserver.class.php');
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEventDispatcher.class.php');

        $event_dispatcher = new lcEventDispatcher();

        return $event_dispatcher;
    }

    protected function getDefaultClassAutoloader()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'autoload' . DS . 'lcClassAutoloader.class.php');

        $autoloader = new lcClassAutoloader();
        return $autoloader;
    }

    protected function getDefaultModelManager()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'lcDatabaseModelManager.class.php');

        $manager = new lcDatabaseModelManager();
        return $manager;
    }

    protected function getDefaultSystemComponentFactory()
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'lcSystemComponentFactory.class.php');

        $factory = new lcSystemComponentFactory();
        return $factory;
    }

    protected function getDefaultCacheInstance($skip_cli_check = false)
    {
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'stores' . DS . 'iCacheStorage.class.php');
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'providers' . DS . 'lcAPC.class.php');
        require(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'providers' . DS . 'lcXCache.class.php');

        $object = null;

        // do not allow calling in CLI
        $in_cli = (0 == strncasecmp(PHP_SAPI, 'cli', 3));

        if ($in_cli && !$skip_cli_check) {
            return null;
        } elseif (function_exists('xcache_get')) {
            // xcache
            $object = new lcXCache();
        } elseif (function_exists('apc_fetch')) {
            // apc
            $object = new lcAPC();
        }

        return $object;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory)
    {
        $this->system_component_factory = $component_factory;
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function getPluginManager()
    {
        return $this->plugin_manager;
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(iCacheStorage $cache)
    {
        $this->cache = $cache;
    }

    public function unsetCache()
    {
        $this->cache = null;
    }

    public function setLocalCacheManager(lcLocalCacheManager $local_cache_manager)
    {
        $this->local_cache_manager = $local_cache_manager;
    }

    public function getLocalCacheManager()
    {
        return $this->local_cache_manager;
    }

    public function setDatabaseModelManager(iDatabaseModelManager $manager)
    {
        $this->database_model_manager = $manager;
    }

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    public function getClassAutoloader()
    {
        return $this->class_autoloader;
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
    }

    public function setErrorHandler(iErrorHandler $error_handler)
    {
        $this->error_handler = $error_handler;
    }

    public function getErrorHandler()
    {
        return $this->error_handler;
    }

    public function getProjectAppName($app_name)
    {
        // cache it
        static $_app_name;

        if (!$_app_name) {
            $_app_name = $this->getProjectName() . '_' . $app_name;
        }

        return $_app_name;
    }

    public function getApplicationLocations()
    {
        $locations = array(
            array(
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'applications'
            )
        );

        return $locations;
    }

    public function getPluginLocations()
    {
        $locations = isset($this->configuration['plugins']['locations']) && is_array($this->configuration['plugins']['locations']) ?
            $this->configuration['plugins']['locations'] : array(self::DEFAULT_PLUGINS_LOCATION);

        if (!$locations) {
            return false;
        }

        $locations_new = array();

        foreach ($locations as $path) {
            $path = lcMisc::isPathAbsolute($path) ? $path : ($this->app_root_dir . DS . $path);

            // TODO: Think how to allow assets_path in configuration so it is not hardcoded to this
            $locations_new[] = array(
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $path,
                'web_path' => '/addons/plugins/'
            );

            unset($path);
        }

        return $locations_new;
    }

    public function getControllerModuleLocations()
    {
        $locations = array(
            array(
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'modules'
            ),
            /* app modules to be overriden in the inherited app config class */
        );

        return $locations;
    }

    public function getControllerComponentLocations()
    {
        $locations = array(
            array(
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'components'
            ),
            array(
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getAddonsDir() . DS . 'components'
            )
        );

        return $locations;
    }

    public function getControllerTaskLocations()
    {
        $locations = array(
            array(
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'tasks'
            ),
            array(
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'tasks'
            ),
        );

        return $locations;
    }

    public function getControllerWebServiceLocations()
    {
        $locations = array(
            array(
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'ws'
            ),
            array(
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'ws'
            ),
        );

        return $locations;
    }

    public function getUsedDbModels()
    {
        if (!$this->use_models) {
            if ($this->getDatabaseModelManager()) {
                return $this->getDatabaseModelManager()->getRegisteredModelNames();
            }
        }

        return $this->use_models;
    }

    public function getProjectDir()
    {
        return $this->app_root_dir;
    }

    public function setProjectDir($project_dir)
    {
        $this->app_root_dir = $project_dir;
    }

    public function getModelsDir()
    {
        return $this->getProjectDir() . DS . self::MODELS_DIR_NAME;
    }

    // TODO: Deprecated. Remove in 1.5
    public function getAppRootDir()
    {
        return $this->getProjectDir();
    }

    public function getRootDir()
    {
        return $this->root_dir;
    }

    public function setRootDir($root_dir)
    {
        $this->root_dir = $root_dir;
    }

    public function setTempDir($temp_dir)
    {
        $this->tmp_dir = $temp_dir;
    }

    public function getAddonsDir()
    {
        return $this->getProjectDir() . DS . 'addons';
    }

    public function getDataDir()
    {
        return $this->getProjectDir() . DS . 'data';
    }

    public function getGenDir()
    {
        return $this->getProjectDir() . DS . 'gen';
    }

    public function getTempDir()
    {
        return $this->tmp_dir . DS . 'temp';
    }

    public function getCacheDir($environment = null)
    {
        $environment = $environment ? $environment : $this->getConfigEnvironment();
        return $this->tmp_dir . DS . $environment . DS . 'cache';
    }

    public function getSpoolDir($environment = null)
    {
        $environment = $environment ? $environment : $this->getConfigEnvironment();
        return $this->tmp_dir . DS . $environment . DS . 'spool';
    }

    public function getShellDir()
    {
        return $this->getProjectDir() . DS . 'shell';
    }

    public function getLogDir()
    {
        return $this->tmp_dir . DS . 'logs';
    }

    public function getLocksDir()
    {
        return $this->tmp_dir . DS . 'locks';
    }

    public function getSessionDir()
    {
        return $this->tmp_dir . DS . 'sessions';
    }

    public function getWebPath()
    {
        return '/';
    }

    public function getAssetsPath()
    {
        return '/';
    }

    public function getStylesheetPath()
    {
        return '/css/';
    }

    public function getJavascriptPath()
    {
        return '/js/';
    }

    public function getImgPath()
    {
        return '/img/';
    }

    public function getWebDir()
    {
        return $this->getProjectDir() . DS . 'webroot';
    }

    public function getStylesheetDir()
    {
        return $this->getWebDir() . DS . 'css';
    }

    public function getJavascriptDir()
    {
        return $this->getWebDir() . DS . 'js';
    }

    public function getImgDir()
    {
        return $this->getWebDir() . DS . 'img';
    }

    public function getConfigDir()
    {
        return $this->getProjectDir() . DS . 'config';
    }

    public function getTestDir()
    {
        return $this->getProjectDir() . DS . 'sandbox';
    }

    public function getSourceDir()
    {
        return $this->getRootDir() . DS . 'source';
    }

    public function getLibsDir()
    {
        return $this->getSourceDir() . DS . 'libs';
    }

    public function getBinDir()
    {
        return $this->getSourceDir() . DS . 'bin';
    }

    public function getAssetsDir()
    {
        return $this->getSourceDir() . DS . 'assets';
    }

    public function getThirdPartyDir()
    {
        return $this->getSourceDir() . DS . '3rdparty';
    }

    public function isDebugging()
    {
        return $this->debugging;
    }

    public function setIsDebugging($debug = true)
    {
        $this->debugging = $debug;

        // setup error reporting
        $this->setupErrorReporting();

        // disable caching the configuration if debugging
        if ($debug) {
            $this->environment = lcEnvConfigHandler::ENVIRONMENT_DEBUG;
            $this->use_class_cache = false;
        } else {
            $this->environment = lcEnvConfigHandler::ENVIRONMENT_RELEASE;
            $this->use_class_cache = true;
        }
    }

    public function setEnvironment($environment)
    {
        parent::setEnvironment($environment);

        // set debugging if environment = debug
        $this->setIsDebugging(($environment == lcEnvConfigHandler::ENVIRONMENT_DEBUG));
    }

    public function readClassCache(array $cached_data)
    {
        $this->project_db_models = isset($cached_data['project_db_models']) ? $cached_data['project_db_models'] : null;

        parent::readClassCache($cached_data);
    }

    public function writeClassCache()
    {
        $parent_cache = (array)parent::writeClassCache();
        $project_cache = array(
            'project_db_models' => $this->project_db_models
        );
        $cache = array_merge($parent_cache, $project_cache);
        return $cache;
    }
}

?>