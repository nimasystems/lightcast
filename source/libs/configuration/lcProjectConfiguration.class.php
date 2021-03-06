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

class lcProjectConfiguration extends lcConfiguration implements iSupportsDbModels, iSupportsDbModelOperations,
    iSupportsAutoload, iAppDelegate, iSupportsVersions
{
    const DEFAULT_BASE_CONFIG_DIR = 'default';
    const DEFAULT_CONFIG_ENV = lcEnvConfigHandler::ENV_PROD;

    const DEFAULT_PLUGINS_LOCATION = 'addons/plugins';
    const DEFAULT_PROJECT_NAME = 'default';
    const CLASS_CACHE_RESET_KEY_SUFFIX = '_should_reset';

    const ENCRYPTION_KEY_FILENAME = 'secrets/.key';
    const SECURE_UNENCRYPTED_FILENAME = '.env.secure.unencrypted';
    const SECURE_ENCRYPTED_FILENAME = '.env.secure';

    const TMP_DIR_NAME = 'tmp';
    const MODELS_DIR_NAME = 'models';
    const CACHE_DIR_NAME = 'cache';

    const ENV_APP_DEBUG = 'APP_DEBUG';

    /** @var iAppDelegate */
    protected $app_delegate;

    /** @var lcClassAutoloader */
    protected $class_autoloader;

    /** @var lcEventDispatcher */
    protected $event_dispatcher;

    /** @var lcPluginManager */
    protected $plugin_manager;

    /** @var lcErrorHandler */
    protected $error_handler;

    /** @var lcCacheStore */
    protected $cache;

    /** @var lcLocalCacheManager */
    protected $local_cache_manager;

    /** @var lcSystemComponentFactory */
    protected $system_component_factory;

    /** @var lcDatabaseModelManager */
    protected $database_model_manager;

    protected $use_models;
    protected $use_classes;

    protected $use_class_cache = true;

    /** @var array|null */
    protected $project_db_models;

    protected $config_variation;
    protected $config_environment;

    protected $app_root_dir;
    protected $root_dir;
    protected $tmp_dir;

    protected $debugging;
    private $is_lc15_targeting;
    private $_is_lc15_targeting_checked;

    public function __construct()
    {
        parent::__construct();

        // setup error reporting early
        //$this->setupErrorReporting();

        // set vars
        $this->root_dir = ROOT;
        $this->config_environment = self::DEFAULT_CONFIG_ENV;
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

    public function getEncryptionKeyFilename(): string
    {
        return $this->getConfigDir() . DS . self::ENCRYPTION_KEY_FILENAME;
    }

    public function getSecureEnvFilename(): string
    {
        return $this->getProjectDir() . DS . self::SECURE_ENCRYPTED_FILENAME;
    }

    public function getEnvFilename(): string
    {
        return $this->getProjectDir() . DS . '.env';
    }

    public function getProjectDir()
    {
        return $this->app_root_dir;
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

    public function getConfigVariation()
    {
        return $this->config_variation;
    }

    public function setConfigVariation($variation)
    {
        $this->config_variation = $variation;
    }

    public function getShouldUseCachedConfigurationData()
    {
        return $this->use_class_cache;
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

    public function getDefaultClassAutoloader()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'autoload' . DS . 'lcClassAutoloader.class.php');

        return new lcClassAutoloader();
    }

    public function getDefaultEventDispatcher()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEvent.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'iEventObserver.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEventDispatcher.class.php');

        return new lcEventDispatcher();
    }

    public function getDefaultCacheInstance($skip_cli_check = false)
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'stores' . DS . 'iCacheStore.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'stores' . DS . 'lcCacheStore.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'stores' . DS . 'iCacheMultiStorage.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'providers' . DS . 'lcAPC.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'providers' . DS . 'lcXCache.class.php');

        $object = null;

        // do not allow calling in CLI
        $in_cli = (0 == strncasecmp(PHP_SAPI, 'cli', 3));

        if ($in_cli && !$skip_cli_check) {
            return null;
        } else if (function_exists('xcache_get')) {
            // xcache
            $object = new lcXCache();
        } else if (function_exists('apc_fetch') || function_exists('apcu_fetch')) {
            // apc
            $object = new lcAPC();
        }

        return $object;
    }

    public function getDefaultPluginManager()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'plugins' . DS . 'lcPluginManager.class.php');

        return new lcPluginManager();
    }

    public function getDefaultErrorHandler()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'iErrorHandler.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'lcErrorHandler.class.php');

        return new lcErrorHandler();
    }

    public function getDefaultLocalCacheManager()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'lcLocalCacheManager.class.php');

        return new lcLocalCacheManager();
    }

    public function getDefaultSystemComponentFactory()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'lcSystemComponentFactory.class.php');

        return new lcSystemComponentFactory();
    }

    public function getDefaultModelManager()
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'lcDatabaseModelManager.class.php');

        return new lcDatabaseModelManager();
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = [
            'class_autoloader' => ($this->class_autoloader ? get_class($this->class_autoloader) : null),
            'error_handler' => ($this->error_handler ? get_class($this->error_handler) : null),
            'cache' => ($this->cache ? get_class($this->cache) : null),
            'root_dir' => $this->root_dir,
            'app_root_dir' => $this->app_root_dir,
            'is_debugging' => $this->debugging,
            'project_name' => $this->getProjectName(),
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getProjectName()
    {
        // may be overriden by subclassers
        return self::DEFAULT_PROJECT_NAME;
    }

    public function getShortDebugInfo()
    {
        return [
            'project_name' => $this->getProjectName(),
        ];
    }

    public function getSystemObjectInstances()
    {
        $instances = [];

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
        return [
            'error_handler',
            'cache',
            'local_cache_manager',
            'database_model_manager',
            'system_component_factory',
            'plugin_manager',
        ];
    }

    public function getProjectConfigDir()
    {
        return null;
    }

    public function getConfigHandleMap()
    {
        // maps the configuration values to handlers
        return [
            ['handler' => 'project', 'dirs' => [$this->getBaseConfigDir(), $this->getConfigDir()], 'config_key' => 'project'],
            ['handler' => 'databases', 'dirs' => [$this->getBaseConfigDir(), $this->getConfigDir()], 'config_key' => 'databases'],
        ];
    }

    public function getBaseConfigDir()
    {
        return $this->getConfigDir() . DS . ($this->config_variation ?: 'config');
    }

    public function getConfigDir()
    {
        return $this->getProjectDir() . DS . 'config';
    }

    /**
     * @return string
     * @deprecated
     */
    public function getConfigVersion()
    {
        return $this->getVersion();
    }

    /**
     * @return int
     * @deprecated
     */
    public function getRevisionVersion()
    {
        return $this->getBuildVersion();
    }

    /**
     * @param $config_version
     * @deprecated
     */
    public function setConfigVersion($config_version)
    {
        //
    }

    public function isTargetingLC15()
    {
        if (!$this->_is_lc15_targeting_checked) {
            $target_version = $this->getTargetFrameworkVersion();

            if ($target_version) {
                $this->is_lc15_targeting = version_compare($target_version, '1.5', '>=');
            }

            $this->_is_lc15_targeting_checked = true;
        }

        return $this->is_lc15_targeting;
    }

    public function getTargetFrameworkVersion()
    {
        return null;
    }

    public function getMinimumFrameworkVersion()
    {
        return null;
    }

    public function getVersion()
    {
        return $this->getMajorVersion() . '.' .
            $this->getMinorVersion() . '.' .
            $this->getBuildVersion();
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

    /**
     * @return int
     */
    public function getBuildVersion()
    {
        // subclassers may override this method
        return 0;
    }

    /**
     * @return string
     */
    public function getStabilityCode()
    {
        // subclassers may override this method
        return iSupportsVersions::STABILITY_CODE_PRODUCTION;
    }

    public function willBeginInitializingApp(lcApp $app)
    {
        // subclassers may override this method to execute code before the initialization of the app
    }

    public function didInitializeApp(lcApp $app)
    {
        // subclassers may override this method to execute code after the initialization of the app
    }

    public function willShutdownApp(lcApp $app)
    {
        // subclassers may override this method to execute code before the shutdown of the app
    }

    public function didShutdownApp(lcApp $app)
    {
        // subclassers may override this method to execute code after the shutdown of the app
        // WARNING: At this stage the configuration object will had been already shutdown!
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
            $models = [];

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

    public function getModelsDir()
    {
        return $this->getProjectDir() . DS . self::MODELS_DIR_NAME;
    }

    public function getUseClassCache()
    {
        return $this->use_class_cache;
    }

    public function setUseClassCache($use_class_cache = true)
    {
        $this->use_class_cache = $use_class_cache;
    }

    public function getAppDelegate()
    {
        return $this->app_delegate;
    }

    public function setAppDelegate(iAppDelegate $app_delegate)
    {
        $this->app_delegate = $app_delegate;
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory)
    {
        $this->system_component_factory = $component_factory;
    }

    public function getPluginManager()
    {
        return $this->plugin_manager;
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(iCacheStore $cache)
    {
        $this->cache = $cache;
    }

    public function unsetCache()
    {
        $this->cache = null;
    }

    public function getLocalCacheManager()
    {
        return $this->local_cache_manager;
    }

    public function setLocalCacheManager(lcLocalCacheManager $local_cache_manager)
    {
        $this->local_cache_manager = $local_cache_manager;
    }

    public function getClassAutoloader()
    {
        return $this->class_autoloader;
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
    }

    public function getErrorHandler()
    {
        return $this->error_handler;
    }

    public function setErrorHandler(iErrorHandler $error_handler)
    {
        $this->error_handler = $error_handler;
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
        return [
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'applications',
            ],
        ];
    }

    public function getPluginLocations()
    {
        $locations = isset($this->configuration['plugins']['locations']) && is_array($this->configuration['plugins']['locations']) ?
            $this->configuration['plugins']['locations'] : [self::DEFAULT_PLUGINS_LOCATION];

        if (!$locations) {
            return false;
        }

        $locations_new = [];

        foreach ($locations as $path) {
            $path = lcMisc::isPathAbsolute($path) ? $path : ($this->app_root_dir . DS . $path);

            // TODO: Think how to allow assets_path in configuration so it is not hardcoded to this
            $locations_new[] = [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $path,
                'web_path' => '/addons/plugins/',
            ];

            unset($path);
        }

        return $locations_new;
    }

    public function getActionFormLocations()
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'forms',
            ],
            /* app modules to be overriden in the inherited app config class */
        ];
    }

    public function getAssetsDir()
    {
        return $this->getSourceDir() . DS . 'assets';
    }

    public function getSourceDir()
    {
        return $this->getRootDir() . DS . 'source';
    }

    public function getRootDir()
    {
        return $this->root_dir;
    }

    public function setRootDir($root_dir)
    {
        $this->root_dir = $root_dir;
    }

    public function getControllerModuleLocations()
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'modules',
            ],
            /* app modules to be overriden in the inherited app config class */
        ];
    }

    public function getControllerComponentLocations()
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'components',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getAddonsDir() . DS . 'components',
            ],
        ];
    }

    public function getAddonsDir()
    {
        return $this->getProjectDir() . DS . 'addons';
    }

    public function getControllerTaskLocations()
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'tasks',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'tasks',
            ],
        ];
    }

    public function getControllerWebServiceLocations()
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'path' => $this->getAssetsDir() . DS . 'ws',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getProjectDir() . DS . 'ws',
            ],
        ];
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

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    // TODO: Deprecated. Remove in 1.5

    public function setDatabaseModelManager(iDatabaseModelManager $manager)
    {
        $this->database_model_manager = $manager;
    }

    public function setProjectDir($project_dir)
    {
        $this->app_root_dir = $project_dir;
    }

    public function getAppRootDir()
    {
        return $this->getProjectDir();
    }

    public function setTempDir($temp_dir)
    {
        $this->tmp_dir = $temp_dir;
    }

    public function getMediaDir()
    {
        return $this->getDataDir() . DS . 'media';
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
        return $this->tmp_dir . DS . 'cache' . DS . $environment;
    }

    public function getConfigEnvironment()
    {
        return $this->config_environment;
    }

    public function setConfigEnvironment($environment)
    {
        $this->config_environment = $environment;
    }

    public function getSpoolDir($environment = null)
    {
        $environment = $environment ? $environment : $this->getConfigEnvironment();
        return $this->tmp_dir . DS . 'spool' . DS . $environment;
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

    public function getStylesheetDir()
    {
        return $this->getWebDir() . DS . 'css';
    }

    public function getWebDir()
    {
        return $this->getProjectDir() . DS . 'webroot';
    }

    public function getJavascriptDir()
    {
        return $this->getWebDir() . DS . 'js';
    }

    public function getImgDir()
    {
        return $this->getWebDir() . DS . 'img';
    }

    public function getTestDir()
    {
        return $this->getProjectDir() . DS . 'sandbox';
    }

    public function getLibsDir()
    {
        return $this->getSourceDir() . DS . 'libs';
    }

    public function getBinDir()
    {
        return $this->getSourceDir() . DS . 'bin';
    }

    public function getThirdPartyDir()
    {
        return $this->getSourceDir() . DS . '3rdparty';
    }

    public function getSupportedLocales()
    {
        return null;
    }

    public function setEnvironment($environment)
    {
        parent::setEnvironment($environment);

        // set debugging if environment = debug
        $this->setIsDebugging($environment == lcEnvConfigHandler::ENV_DEV);
    }

    public function setIsDebugging($debug = true)
    {
        $this->debugging = $debug;

        // setup error reporting
        $this->setupErrorReporting();

        // disable caching the configuration if debugging
        if ($debug) {
            $this->use_class_cache = false;
        } else {
            $this->use_class_cache = true;
        }
    }

    protected function setupErrorReporting()
    {
        // setup error reporting
        $display_errors = $this->isDebugging();

        // setup error reporting
        if ($display_errors) {
            error_reporting(E_ALL | E_STRICT);
        } else {
            error_reporting((E_ALL | E_STRICT) ^ E_DEPRECATED);
        }

        ini_set('display_errors', $display_errors);

        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_BAIL, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_ACTIVE, $display_errors);
    }

    public function isDebugging()
    {
        return $this->debugging;
    }

    public function readClassCache(array $cached_data)
    {
        $this->project_db_models = isset($cached_data['project_db_models']) ? $cached_data['project_db_models'] : null;

        parent::readClassCache($cached_data);
    }

    public function writeClassCache()
    {
        $parent_cache = (array)parent::writeClassCache();
        $project_cache = [
            'project_db_models' => $this->project_db_models,
        ];
        return array_merge($parent_cache, $project_cache);
    }

    protected function loadConfigurationData()
    {
        // do not load the configuration yet - allow application configurations to do it
    }

    /**
     * @return array
     */
    public function getConfigParserVars(): array
    {
        return [];
    }
}
