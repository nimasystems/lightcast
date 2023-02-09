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

use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use Symfony\Component\Dotenv\Dotenv;

/**
 * @method getProjectDir()
 * @method getProjectName()
 * @method getConfigEnvironment()
 * @method getDefaultTimezone()
 * @method getProjectAppName($getApplicationName)
 */
abstract class lcApplicationConfiguration extends lcConfiguration implements iSupportsAutoload
{
    /**
     * @var lcProjectConfiguration
     */
    protected lcProjectConfiguration $project_configuration;

    protected array $use_classes = [];

    protected ?string $unique_id_suffix = null;

    protected bool $should_load_plugins = true;
    protected bool $should_use_default_loaders = false;
    protected bool $should_disable_loaders = false;
    protected bool $should_disable_models = false;
    protected bool $should_disable_databases = false;
    private string $project_dir;

    private array $secure_env_data = [];

    /**
     * @param $project_dir
     * @param lcProjectConfiguration|null $project_configuration
     */
    public function __construct($project_dir = null, lcProjectConfiguration $project_configuration = null)
    {
        $this->project_dir = $project_dir;

        // create the default instance of project configuration which
        // may be overriden before initialization
        $this->project_configuration = $project_configuration ?: new lcProjectConfiguration();
        $this->project_configuration->setProjectDir($project_dir);

        $this->initVendor();

        parent::__construct();
    }

    /**
     * @param $method
     * @param array|null $params
     * @return mixed
     * @throws Exception
     */
    public function __call($method, array $params = null)
    {
        // up to 5 params use the fast calls, more than that - use
        // call_user_func_array which is slower
        if (!method_exists($this->project_configuration, $method)) {
            parent::__call($method, $params);
        }

        switch (count($params)) {
            case 0 :
                return $this->project_configuration->$method();
            case 1 :
                return $this->project_configuration->$method($params[0]);
            case 2 :
                return $this->project_configuration->$method($params[0], $params[1]);
            case 3 :
                return $this->project_configuration->$method($params[0], $params[1], $params[2]);
            case 4 :
                return $this->project_configuration->$method($params[0], $params[1], $params[2], $params[3]);
            case 5 :
                return $this->project_configuration->$method($params[0], $params[1], $params[2], $params[3], $params[4]);
            default :
                /** @noinspection PhpParamsInspection */
                /** @noinspection PhpVariableVariableInspection */
                return call_user_func_array($this->project_configuration->$method, $params);
        }
    }

    public function initialize()
    {
        if (!$this->project_dir) {
            throw new lcInvalidArgumentException('Invalid project directory');
        }

        // initialize project configuration first
        $this->project_configuration->initialize();

        // pass the project base dir
        $this->base_config_dir = $this->project_configuration->getBaseConfigDir();

        $this->prepareEnv();

        parent::initialize();
    }

    protected function updateSharedEnvVars()
    {
        if ($_SERVER) {
            foreach ($_SERVER as $key => $val) {
                if (is_array($val)) {
                    continue;
                }

                self::$shared_config_parser_vars['env(' . $key . ')'] = $val;
                unset($key, $val);
            }
        }

        if ($_ENV) {
            foreach ($_ENV as $key => $val) {
                if (is_array($val)) {
                    continue;
                }

                self::$shared_config_parser_vars['env(' . $key . ')'] = $val;
                unset($key, $val);
            }
        }

        foreach ($this->secure_env_data as $key => $val) {
            if (is_array($val)) {
                continue;
            }

            self::$shared_config_parser_vars['env(' . $key . ')'] = $val;
            unset($key, $val);
        }
    }

    protected function initVendor()
    {
        /** @noinspection PhpIncludeInspection */
        include_once($this->getProjectDir() . DS . 'vendor' . DS . 'autoload.php');
    }

    /**
     * @param bool $force
     * @return void
     * @throws lcConfigException
     * @throws lcSystemException
     */
    public function loadData(bool $force = false)
    {
        parent::loadData($force);

        $this->project_configuration->executeAfterDataLoaded();
    }

    public function initializeEnvironment()
    {
        // protect against older app versions which still use the boot_config file
        if (defined('CONFIG_ENV')) {
            return;
        }

        $env_filename = $this->project_configuration->getEnvFilename();

//        $predefined_env = null;
//
//        if (defined('CONFIG_ENV')) {
//            $predefined_env = CONFIG_ENV;
//        } else if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV']) {
//            $predefined_env = $_ENV['APP_ENV'];
//        }
//
//        //$env_filename .= $predefined_env ? '.' . $predefined_env : '';

        // Load cached env vars if the .env.local.php file exists
        // Run "composer dump-env prod" to create it (requires symfony/flex >=1.2)
        /** @noinspection PhpIncludeInspection */
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        if (is_array($env = @include $this->getProjectDir() . DS . '.env.local.php')) {
            $_SERVER += $env;
            $_ENV += $env;
        } else {
            // load all the .env files
            $dotenv = new Dotenv();
            // loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
            $dotenv->loadEnv($env_filename, null, lcEnvConfigHandler::ENV_DEV, []);

            $this->secure_env_data = $this->parseSecureEnvData();
        }

        //

        $env = $_ENV['APP_ENV'] ?? lcEnvConfigHandler::ENV_PROD;
        $is_debugging = $env == lcEnvConfigHandler::ENV_DEV || (isset($_ENV[lcProjectConfiguration::ENV_APP_DEBUG]) &&
                $_ENV[lcProjectConfiguration::ENV_APP_DEBUG]);

        if (!defined('DO_DEBUG')) {
            define('DO_DEBUG', $is_debugging);
        }

        define('CONFIG_ENV', $env);
        define('CONFIG_VARIATION', 'default');

        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null) ?: 'dev';
        $_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'prod' !== $_SERVER['APP_ENV'];
        $_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int)$_SERVER['APP_DEBUG'] || filter_var($_SERVER['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) ? '1' : '0';

        $this->environment = CONFIG_ENV;

        $this->setIsDebugging($is_debugging);
        $this->project_configuration->setConfigEnvironment(CONFIG_ENV);
        $this->project_configuration->setConfigVariation(CONFIG_VARIATION);
    }

    protected function parseSecureEnvData(): array
    {
        $secure_env_filename = $this->project_configuration->getSecureEnvFilename();
        $key_filename = $this->project_configuration->getEncryptionKeyFilename();

        // load all the .env files
        $dotenv = new Dotenv();

        // load secure envs
        if (!$secure_env_filename || !$key_filename ||
            !file_exists($secure_env_filename) || !is_readable($secure_env_filename) ||
            !file_exists($key_filename) || !is_readable($key_filename)) {
            return [];
        }

        $data = $dotenv->parse(file_get_contents($secure_env_filename));
        $encryption_key = KeyFactory::loadEncryptionKey($key_filename);

        $ndata = [];

        foreach ($data as $key => $val) {
            $ndata[$key] = Symmetric::decrypt($val, $encryption_key)->getString();
            unset($key, $val);
        }

        return $ndata;
    }

    protected function prepareEnv()
    {
        $this->updateSharedEnvVars();
    }

    public function shutdown()
    {
        // shutdown project_configuration
        $this->project_configuration->shutdown();

        parent::shutdown();
    }

    /**
     * @return array|array[]|null
     */
    public function getConfigHandleMap(): ?array
    {
        // we load the project's config map ourselves
        return $this->project_configuration->getConfigHandleMap();
    }

    public function executeBefore()
    {
        // subclassers may override this method to execute code before the
        // initialization of the config
    }

    public function executeAfter()
    {
        // subclassers may override this method to execute code after the
        // initialization of the config
    }

    /**
     * @return array
     */
    public function getAutoloadClasses(): array
    {
        // subclassers may override this method to return an array of classes
        // which should
        // be autoloaded upon initialization
        return $this->use_classes;
    }

    /**
     * @deprecated Not to be used any more - use core base_url website config
     */
    public function getPathInfoPrefix()
    {
        // subclassers may override this method to return a web path prefix which
        // should be used in the
        // construction of urls
    }

    /**
     * @return array|array[]
     */
    public function getDebugInfo(): array
    {
        $debug_parent = parent::getDebugInfo();

        $debug = ['application_name' => $this->getApplicationName()];

        return array_merge($debug_parent, $debug);
    }

    /**
     * @return mixed
     */
    abstract public function getApplicationName();

    public function getNamespacedClass(string $class = null): string
    {
        return $this->getProjectConfiguration()->getNamespacedClass(lcInflector::camelize($this->getApplicationName()) .
            ($class ? '\\' . $class : ''));
    }

    /**
     * @param bool $debug
     * @return void
     */
    public function setIsDebugging(bool $debug = true)
    {
        $this->project_configuration->setIsDebugging($debug);
    }

    /**
     * @param $environment
     * @return void
     */
    public function setEnvironment($environment)
    {
        $this->project_configuration->setEnvironment($environment);
        $this->environment = $environment;
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    public function getShouldDisableModels(): bool
    {
        return $this->should_disable_models;
    }

    /**
     * @return bool
     */
    public function getShouldUseDefaultLoaders(): bool
    {
        return $this->should_use_default_loaders;
    }

    /**
     * @return bool
     */
    public function getShouldDisableLoaders(): bool
    {
        return $this->should_disable_loaders;
    }

    /**
     * @param bool $use_default_loaders
     * @return void
     */
    public function setShouldUseDefaultLoaders(bool $use_default_loaders = false)
    {
        $this->should_use_default_loaders = $use_default_loaders;
    }

    /**
     * @return lcEventDispatcher|null
     */
    public function getEventDispatcher(): ?lcEventDispatcher
    {
        return $this->project_configuration->getEventDispatcher();
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->project_configuration->setEventDispatcher($event_dispatcher);
    }

    /**
     * @return lcClassAutoloader|null
     */
    public function getClassAutoloader(): ?lcClassAutoloader
    {
        return $this->project_configuration->getClassAutoloader();
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
        $this->project_configuration->setClassAutoloader($class_autoloader);
    }

    /**
     * @return lcProjectConfiguration
     */
    public function getProjectConfiguration(): lcProjectConfiguration
    {
        return $this->project_configuration;
    }

    public function setProjectConfiguration(lcProjectConfiguration $project_configuration)
    {
        $this->project_configuration = $project_configuration;

        if (!$this->project_configuration->getProjectDir()) {
            $this->project_configuration->setProjectDir($this->project_dir);
        }
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        $email = $this->get('admin_email');
        $email = !$email ? $this->get('settings.admin_email') : $email;
        $email = !$email ? ini_get('sendmail_from') : $email;
        $email = !$email ? get_current_user() . '@' . php_uname('n') : $email;
        return !$email ? 'root@localhost' : $email;
    }

    /**
     * @return string
     */
    public function getDefaultEmailSender(): string
    {
        $email = ini_get('sendmail_from');
        $email = !$email ? get_current_user() . '@' . php_uname('n') : $email;
        return !$email ? 'root@localhost' : $email;
    }

    /**
     * @return string
     */
    public function getUniqueProjectId(): string
    {
        // default unique id is composed of project_name, application_name,
        // is_debugging setting
        // it is used as the unique cache key
        $ret = $this->getProjectName() .
            ($this->project_configuration->getVersion()) .
            $this->getEnvironment() .
            $this->getConfigEnvironment() .
            $this->project_configuration->getProjectDir() .
            ($this->unique_id_suffix ?: null);

        return md5($ret);
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        // default unique id is composed of project_name, application_name,
        // is_debugging setting
        // it is used as the unique cache key
        $ret = $this->getProjectAppName($this->getApplicationName()) .
            ($this->project_configuration->getVersion()) .
            $this->getEnvironment() .
            $this->getConfigEnvironment() .
            $this->project_configuration->getProjectDir() .
            ($this->unique_id_suffix ?: null);

        return md5($ret);
    }

    /**
     * @return string|null
     */
    public function getUniqueIdSuffix(): ?string
    {
        return $this->unique_id_suffix;
    }

    /**
     * @param $unique_id_suffx
     * @return void
     */
    public function setUniqueIdSuffix($unique_id_suffx)
    {
        // override the automatically generated unique_id with an appended suffix
        // necessary in cases where the website will be used multiply times on
        // the same
        // machine and the need of separate caches is in place
        $this->unique_id_suffix = $unique_id_suffx;
    }

    /**
     * @return string
     */
    public function getApplicationCacheDir(): string
    {
        return $this->project_configuration->getCacheDir() . DS . 'applications' . DS . $this->getApplicationName();
    }

    /**
     * @return mixed
     */
    public function getEnabledPlugins()
    {
        return $this['plugins.enabled'];
    }

    /**
     * @return array
     */
    public function writeClassCache(): array
    {
        $parent_cache = parent::writeClassCache();
        $project_cache = $this->project_configuration->writeClassCache();

        return [
            'parent_cache' => $parent_cache,
            'project_cache' => $project_cache,
        ];
    }

    public function readClassCache(array $cached_data)
    {
        if (isset($cached_data['parent_cache'])) {
            parent::readClassCache($cached_data['parent_cache']);
        }

        if (isset($cached_data['project_cache'])) {
            $this->project_configuration->readClassCache($cached_data['project_cache']);
        }
    }

    // TODO: Remove this when Configurations are combined
    // it is here because it's a frequently accessed method and it is slow
    // to call it with magic
    /**
     * @return string
     */
    public function getGenDir(): string
    {
        return $this->project_configuration->getGenDir();
    }

    /**
     * @return array
     */
    public function getConfigParserVars(): array
    {
        return [];
    }
}
