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
class lcProjectConfiguration extends lcConfiguration implements iSupportsDbModels, iSupportsDbModelOperations,
    iSupportsAutoload, iAppDelegate, iSupportsVersions
{
    public const DEFAULT_BASE_CONFIG_DIR = 'default';
    public const DEFAULT_CONFIG_ENV = lcEnvConfigHandler::ENV_PROD;

    public const DEFAULT_PLUGINS_LOCATION = 'Plugins';
    public const DEFAULT_PROJECT_NAME = 'Project';
    public const DEFAULT_PROJECT_NAMESPACE = 'Project';
    public const CLASS_CACHE_RESET_KEY_SUFFIX = '_should_reset';

    public const ENCRYPTION_KEY_FILENAME = 'secrets/.key';
    public const SECURE_UNENCRYPTED_FILENAME = '.env.secure.unencrypted';
    public const SECURE_ENCRYPTED_FILENAME = '.env.secure';

    public const VAR_DIR_NAME = 'var';
    public const MODELS_DIR_NAME = 'models';
    public const CACHE_DIR_NAME = 'cache';

    public const ENV_APP_DEBUG = 'APP_DEBUG';

    /** @var ?iAppDelegate */
    protected ?iAppDelegate $app_delegate = null;

    /** @var ?lcClassAutoloader */
    protected $class_autoloader;

    /** @var ?lcEventDispatcher */
    protected $event_dispatcher;

    /** @var ?lcPluginManager */
    protected $plugin_manager;

    /** @var ?iErrorHandler */
    protected ?iErrorHandler $error_handler = null;

    /** @var ?iCacheStore */
    protected ?iCacheStore $cache = null;

    /** @var ?lcLocalCacheManager */
    protected ?lcLocalCacheManager $local_cache_manager = null;

    /** @var ?lcSystemComponentFactory */
    protected ?lcSystemComponentFactory $system_component_factory = null;

    /** @var ?iDatabaseModelManager */
    protected ?iDatabaseModelManager $database_model_manager = null;

    protected array $use_models = [];
    protected array $use_classes = [];

    protected bool $use_class_cache = true;

    /** @var array */
    protected array $project_db_models = [];

    protected ?string $config_variation = null;
    protected string $config_environment;

    protected ?string $app_root_dir = null;
    protected string $root_dir;
    protected ?string $var_dir = null;

    protected bool $debugging = false;

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

        $this->var_dir = !$this->var_dir ? $this->getProjectDir() . DS . self::VAR_DIR_NAME : $this->var_dir;

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

    /**
     * @return string|null
     */
    public function getProjectDir(): ?string
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
            null;

        $this->use_models =
        $this->project_db_models =
            [];

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

    /**
     * @return string|null
     */
    public function getConfigVariation(): ?string
    {
        return $this->config_variation;
    }

    /**
     * @param $variation
     * @return void
     */
    public function setConfigVariation($variation)
    {
        $this->config_variation = $variation;
    }

    /**
     * @return bool
     */
    public function getShouldUseCachedConfigurationData(): bool
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

    /**
     * @return lcClassAutoloader
     */
    public function getDefaultClassAutoloader(): lcClassAutoloader
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'autoload' . DS . 'lcClassAutoloader.class.php');

        return new lcClassAutoloader();
    }

    /**
     * @return lcEventDispatcher
     */
    public function getDefaultEventDispatcher(): lcEventDispatcher
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEvent.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'iEventObserver.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'events' . DS . 'lcEventDispatcher.class.php');

        return new lcEventDispatcher();
    }

    /**
     * @param bool $skip_cli_check
     * @return lcAPC|lcXCache|null
     */
    public function getDefaultCacheInstance(bool $skip_cli_check = false)
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

    /**
     * @return lcPluginManager
     */
    public function getDefaultPluginManager(): lcPluginManager
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'plugins' . DS . 'lcPluginManager.class.php');

        return new lcPluginManager();
    }

    /**
     * @return lcErrorHandler
     */
    public function getDefaultErrorHandler(): lcErrorHandler
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'iErrorHandler.class.php');
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'error_handler' . DS . 'lcErrorHandler.class.php');

        return new lcErrorHandler();
    }

    /**
     * @return lcLocalCacheManager
     */
    public function getDefaultLocalCacheManager(): lcLocalCacheManager
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'caching' . DS . 'lcLocalCacheManager.class.php');

        return new lcLocalCacheManager();
    }

    /**
     * @return lcSystemComponentFactory
     */
    public function getDefaultSystemComponentFactory(): lcSystemComponentFactory
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'app' . DS . 'lcSystemComponentFactory.class.php');

        return new lcSystemComponentFactory();
    }

    /**
     * @return lcDatabaseModelManager
     */
    public function getDefaultModelManager(): lcDatabaseModelManager
    {
        require_once(ROOT . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'lcDatabaseModelManager.class.php');

        return new lcDatabaseModelManager();
    }

    /**
     * @return array
     */
    public function getDebugInfo(): array
    {
        $debug_parent = parent::getDebugInfo();

        $debug = [
            'class_autoloader' => ($this->class_autoloader ? get_class($this->class_autoloader) : null),
            'error_handler' => ($this->error_handler ? get_class($this->error_handler) : null),
            'cache' => ($this->cache ? get_class($this->cache) : null),
            'root_dir' => $this->root_dir,
            'app_root_dir' => $this->app_root_dir,
            'is_debugging' => $this->debugging,
            'project_name' => $this->getProjectName(),
        ];

        return array_merge($debug_parent, $debug);
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        // may be overriden by subclassers
        return self::DEFAULT_PROJECT_NAME;
    }

    /**
     * @return string
     */
    public function getProjectNamespace(): string
    {
        // may be overriden by subclassers
        return self::DEFAULT_PROJECT_NAMESPACE;
    }

    /**
     * @return string[]
     */
    public function getShortDebugInfo(): array
    {
        return [
            'project_name' => $this->getProjectName(),
        ];
    }

    /**
     * @return array
     */
    public function getSystemObjectInstances(): array
    {
        $instances = [];

        $system_objects = $this->getSystemObjectNames();

        foreach ($system_objects as $name) {
            /** @noinspection PhpVariableVariableInspection */
            $instances[$name] = $this->$name;
        }

        return $instances;
    }

    /**
     * @return string[]
     */
    public function getSystemObjectNames(): array
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

    /**
     * @return null
     */
    public function getProjectConfigDir()
    {
        return null;
    }

    /**
     * @return array[]
     */
    public function getConfigHandleMap(): array
    {
        // maps the configuration values to handlers
        return [
            ['handler' => 'project', 'dirs' => [$this->getBaseConfigDir(), $this->getConfigDir()], 'config_key' => 'project'],
            ['handler' => 'databases', 'dirs' => [$this->getBaseConfigDir(), $this->getConfigDir()], 'config_key' => 'databases'],
        ];
    }

    /**
     * @return string
     */
    public function getBaseConfigDir(): string
    {
        return $this->getConfigDir() . DS . ($this->config_variation ?: 'config');
    }

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->getProjectDir() . DS . 'config';
    }

    /**
     * @return string
     * @deprecated
     */
    public function getConfigVersion(): string
    {
        return $this->getVersion();
    }

    /**
     * @return int
     * @deprecated
     */
    public function getRevisionVersion(): int
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

    /**
     * @return null
     */
    public function getTargetFrameworkVersion()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getMinimumFrameworkVersion()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->getMajorVersion() . '.' .
            $this->getMinorVersion() . '.' .
            $this->getBuildVersion();
    }

    /**
     * @return int
     */
    public function getMajorVersion(): int
    {
        // subclassers may override this method to return the major version of the project
        return 1;
    }

    /**
     * @return int
     */
    public function getMinorVersion(): int
    {
        // subclassers may override this method to return the minor version of the project
        return 0;
    }

    /**
     * @return int
     */
    public function getBuildVersion(): int
    {
        // subclassers may override this method
        return 0;
    }

    /**
     * @return string
     */
    public function getStabilityCode(): string
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

    /**
     * @return array
     */
    public function getAutoloadClasses(): array
    {
        // subclassers may override this method to return an array of classes which should
        // be autoloaded upon initialization
        return $this->use_classes;
    }

    /**
     * @return array|null
     */
    public function getDbModels(): ?array
    {
        // subclassers may override this method to return a different set of models

        if (!$this->project_db_models) {
            // initialize the models - scan the models folder
            $t = lcDirs::searchDir($this->getModelsDir());
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

    /**
     * @return string
     */
    public function getModelsDir(): string
    {
        return $this->getProjectDir() . DS . self::MODELS_DIR_NAME;
    }

    /**
     * @return bool
     */
    public function getUseClassCache(): bool
    {
        return $this->use_class_cache;
    }

    /**
     * @param bool $use_class_cache
     * @return void
     */
    public function setUseClassCache(bool $use_class_cache = true)
    {
        $this->use_class_cache = $use_class_cache;
    }

    /**
     * @return ?iAppDelegate
     */
    public function getAppDelegate(): ?iAppDelegate
    {
        return $this->app_delegate;
    }

    public function setAppDelegate(iAppDelegate $app_delegate)
    {
        $this->app_delegate = $app_delegate;
    }

    /**
     * @return ?lcSystemComponentFactory
     */
    public function getSystemComponentFactory(): ?lcSystemComponentFactory
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory)
    {
        $this->system_component_factory = $component_factory;
    }

    /**
     * @return ?lcPluginManager
     */
    public function getPluginManager(): ?lcPluginManager
    {
        return $this->plugin_manager;
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    /**
     * @return ?lcEventDispatcher
     */
    public function getEventDispatcher(): ?lcEventDispatcher
    {
        return $this->event_dispatcher;
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    /**
     * @return ?iCacheStore
     */
    public function getCache(): ?iCacheStore
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

    /**
     * @return ?lcLocalCacheManager
     */
    public function getLocalCacheManager(): ?lcLocalCacheManager
    {
        return $this->local_cache_manager;
    }

    public function setLocalCacheManager(lcLocalCacheManager $local_cache_manager)
    {
        $this->local_cache_manager = $local_cache_manager;
    }

    /**
     * @return ?lcClassAutoloader
     */
    public function getClassAutoloader(): ?lcClassAutoloader
    {
        return $this->class_autoloader;
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
    }

    /**
     * @return ?iErrorHandler
     */
    public function getErrorHandler(): ?iErrorHandler
    {
        return $this->error_handler;
    }

    public function setErrorHandler(iErrorHandler $error_handler)
    {
        $this->error_handler = $error_handler;
    }

    /**
     * @param $app_name
     * @return mixed|string
     */
    public function getProjectAppName($app_name)
    {
        // cache it
        static $_app_name;

        if (!$_app_name) {
            $_app_name = $this->getProjectName() . '_' . $app_name;
        }

        return $_app_name;
    }

    /**
     * @return array[]
     */
    public function getApplicationLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $this->getSrcDir('Applications'),
                'namespace' => $this->getNamespacedClass('\\Applications'),
            ],
        ];
    }

    /**
     * @return array|false
     */
    public function getPluginLocations()
    {
        $locations = isset($this->configuration['plugins']['locations']) && is_array($this->configuration['plugins']['locations']) ?
            $this->configuration['plugins']['locations'] : [self::DEFAULT_PLUGINS_LOCATION];

        if (!$locations) {
            return false;
        }

        $locations_new = [];

        $ns = $this->getNamespacedClass('Plugins');

        foreach ($locations as $path) {
            $path = lcMisc::isPathAbsolute($path) ? $path : $this->getSrcDir($path);

            // TODO: Think how to allow assets_path in configuration so it is not hardcoded to this
            $locations_new[] = [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'path' => $path,
                'namespace' => $ns,
                // deprecated - no longer present
                //                'web_path' => '/addons/plugins/',
            ];

            unset($path);
        }

        return $locations_new;
    }

    /**
     * @return array[]
     */
    public function getActionFormLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'namespace' => '\\Lightcast\\Assets\\Forms',
                'path' => $this->getAssetsDir() . DS . 'Forms',
            ],
            /* app modules to be overriden in the inherited app config class */
        ];
    }

    /**
     * @return string
     */
    public function getAssetsDir(): string
    {
        return $this->getSourceDir() . DS . 'assets';
    }

    /**
     * @return string
     */
    public function getSourceDir(): string
    {
        return $this->getRootDir() . DS . 'source';
    }

    /**
     * @return false|string
     */
    public function getRootDir()
    {
        return $this->root_dir;
    }

    /**
     * @param $root_dir
     * @return void
     */
    public function setRootDir($root_dir)
    {
        $this->root_dir = $root_dir;
    }

    /**
     * @return array[]
     */
    public function getModuleLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'namespace' => '\\Lightcast\\Assets\\Modules',
                'path' => $this->getAssetsDir() . DS . 'Modules',
            ],
            /* app modules to be overriden in the inherited app config class */
        ];
    }

    /**
     * @return array[]
     */
    public function getControllerComponentLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'namespace' => '\\Lightcast\\Assets\\Components',
                'path' => $this->getAssetsDir() . DS . 'Components',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'namespace' => $this->getNamespacedClass('Components'),
                'path' => $this->getSrcDir('Components'),
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getControllerTaskLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'namespace' => '\\Lightcast\\Assets\\Tasks',
                'path' => $this->getAssetsDir() . DS . 'Tasks',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'namespace' => $this->getNamespacedClass('Tasks'),
                'path' => $this->getSrcDir('Tasks'),
            ],
        ];
    }

    public function getNamespacedClass(string $class): string
    {
        return '\\' . $this->getProjectNamespace() . '\\' . $class;
    }

    /**
     * @return array[]
     */
    public function getControllerWebServiceLocations(): array
    {
        return [
            [
                'context_type' => lcSysObj::CONTEXT_FRAMEWORK,
                'namespace' => '\\Lightcast\\Assets\\WebServices',
                'path' => $this->getAssetsDir() . DS . 'WebServices',
            ],
            [
                'context_type' => lcSysObj::CONTEXT_PROJECT,
                'context_name' => $this->getProjectName(),
                'namespace' => $this->getNamespacedClass('WebServices'),
                'path' => $this->getSrcDir('WebServices'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function getUsedDbModels(): array
    {
        if (!$this->use_models) {
            if ($this->getDatabaseModelManager()) {
                return $this->getDatabaseModelManager()->getRegisteredModelNames();
            }
        }

        return $this->use_models;
    }

    /**
     * @return iDatabaseModelManager
     */
    public function getDatabaseModelManager(): iDatabaseModelManager
    {
        return $this->database_model_manager;
    }

    // TODO: Deprecated. Remove in 1.5

    public function setDatabaseModelManager(iDatabaseModelManager $manager)
    {
        $this->database_model_manager = $manager;
    }

    /**
     * @param $project_dir
     * @return void
     */
    public function setProjectDir($project_dir)
    {
        $this->app_root_dir = $project_dir;
    }

    /**
     * @return string|null
     */
    public function getAppRootDir(): ?string
    {
        return $this->getProjectDir();
    }

    /**
     * @param string $dir
     * @return void
     */
    public function setVarDir(string $dir)
    {
        $this->var_dir = $dir;
    }

    /**
     * @return string|null
     */
    public function getVarDir(): ?string
    {
        return $this->var_dir;
    }

    /**
     * @return string
     */
    public function getMediaDir(): string
    {
        return $this->getDataDir() . DS . 'media';
    }

    /**
     * @return string
     */
    public function getDataDir(): string
    {
        return $this->getProjectDir() . DS . 'data';
    }

    /**
     * @return string
     */
    public function getGenDir(): string
    {
        return $this->getProjectDir() . DS . 'gen';
    }

    /**
     * @return string
     */
    public function getTempDir(): string
    {
        return $this->getVarDir() . DS . 'temp';
    }

    /**
     * @param $environment
     * @return string
     */
    public function getCacheDir($environment = null): string
    {
        $environment = $environment ?: $this->getConfigEnvironment();
        return $this->getVarDir() . DS . self::CACHE_DIR_NAME . DS . $environment;
    }

    /**
     * @return string
     */
    public function getConfigEnvironment(): string
    {
        return $this->config_environment;
    }

    /**
     * @param $environment
     * @return void
     */
    public function setConfigEnvironment($environment)
    {
        $this->config_environment = $environment;
    }

    /**
     * @param $environment
     * @return string
     */
    public function getSpoolDir($environment = null): string
    {
        $environment = $environment ?: $this->getConfigEnvironment();
        return $this->getVarDir() . DS . 'spool' . DS . $environment;
    }

    /**
     * @param string|null $path
     * @return string
     */
    public function getSrcDir(?string $path = null): string
    {
        return $this->getProjectDir() . DS . 'src' . ($path ? DS . $path : '');
    }

    /**
     * @return string
     */
    public function getShellDir(): string
    {
        return $this->getProjectDir() . DS . 'shell';
    }

    /**
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->getVarDir() . DS . 'logs';
    }

    /**
     * @return string
     */
    public function getLocksDir(): string
    {
        return $this->getVarDir() . DS . 'locks';
    }

    /**
     * @return string
     */
    public function getSessionDir(): string
    {
        return $this->getVarDir() . DS . 'sessions';
    }

    /**
     * @return string
     */
    public function getWebPath(): string
    {
        return '/';
    }

    /**
     * @return string
     */
    public function getAssetsPath(): string
    {
        return '/';
    }

    /**
     * @return string
     */
    public function getStylesheetPath(): string
    {
        return '/css/';
    }

    /**
     * @return string
     */
    public function getJavascriptPath(): string
    {
        return '/js/';
    }

    /**
     * @return string
     */
    public function getImgPath(): string
    {
        return '/img/';
    }

    /**
     * @return string
     */
    public function getStylesheetDir(): string
    {
        return $this->getWebDir() . DS . 'css';
    }

    /**
     * @return string
     */
    public function getWebDir(): string
    {
        return $this->getProjectDir() . DS . 'webroot';
    }

    /**
     * @return string
     */
    public function getJavascriptDir(): string
    {
        return $this->getWebDir() . DS . 'js';
    }

    /**
     * @return string
     */
    public function getImgDir(): string
    {
        return $this->getWebDir() . DS . 'img';
    }

    /**
     * @return string
     */
    public function getTestDir(): string
    {
        return $this->getProjectDir() . DS . 'sandbox';
    }

    /**
     * @return string
     */
    public function getLibsDir(): string
    {
        return $this->getSourceDir() . DS . 'libs';
    }

    /**
     * @return string
     */
    public function getBinDir(): string
    {
        return $this->getSourceDir() . DS . 'bin';
    }

    /**
     * @return string
     */
    public function getThirdPartyDir(): string
    {
        return $this->getSourceDir() . DS . '3rdparty';
    }

    /**
     * @return null
     */
    public function getSupportedLocales()
    {
        return null;
    }

    /**
     * @param $environment
     * @return void
     */
    public function setEnvironment($environment)
    {
        parent::setEnvironment($environment);

        // set debugging if environment = debug
        $this->setIsDebugging($environment == lcEnvConfigHandler::ENV_DEV);
    }

    /**
     * @param bool $debug
     * @return void
     */
    public function setIsDebugging(bool $debug = true)
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

        ini_set('display_errors', (string)$display_errors);

        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_BAIL, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_ACTIVE, $display_errors);
    }

    /**
     * @return bool
     */
    public function isDebugging(): bool
    {
        return $this->debugging;
    }

    public function readClassCache(array $cached_data)
    {
        $this->project_db_models = $cached_data['project_db_models'] ?? null;

        parent::readClassCache($cached_data);
    }

    /**
     * @return array
     */
    public function writeClassCache(): array
    {
        $parent_cache = parent::writeClassCache();
        $project_cache = [
            'project_db_models' => $this->project_db_models,
        ];
        return array_merge($parent_cache, $project_cache);
    }

    /**
     * @return void
     */
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
