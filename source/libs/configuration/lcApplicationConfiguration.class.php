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
 * @changed $Id: lcApplicationConfiguration.class.php 1472 2013-11-16 14:30:20Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1552 $
 */
abstract class lcApplicationConfiguration extends lcConfiguration implements iSupportsDbModelOperations, iSupportsAutoload
{
    protected $project_configuration;

    protected $use_classes;
    protected $use_models;

    protected $should_load_plugins = true;
    protected $should_use_default_loaders = false;
    protected $should_disable_models = false;
    protected $should_disable_databases = false;
    protected $unique_id_suffix;
    private $project_dir;

    public function __construct($project_dir = null, lcProjectConfiguration $project_configuration = null)
    {
        $this->project_dir = $project_dir;

        // create the default instance of project configuration which
        // may be overriden before initialization
        $this->project_configuration = $project_configuration ? $project_configuration : new lcProjectConfiguration();
        $this->project_configuration->setProjectDir($project_dir);

        parent::__construct($project_dir, $project_configuration);
    }

    public function __call($func, array $args = null)
    {
        if ($this->project_configuration) {
            // up to 5 params use the fast calls, more than that - use
            // call_user_func_array which is slower
            if (!method_exists($this->project_configuration, $func)) {
                return parent::__call($func, $args);
            }

            switch (count($args)) {
                case 0 :
                    return $this->project_configuration->$func();
                case 1 :
                    return $this->project_configuration->$func($args[0]);
                case 2 :
                    return $this->project_configuration->$func($args[0], $args[1]);
                case 3 :
                    return $this->project_configuration->$func($args[0], $args[1], $args[2]);
                case 4 :
                    return $this->project_configuration->$func($args[0], $args[1], $args[2], $args[3]);
                case 5 :
                    return $this->project_configuration->$func($args[0], $args[1], $args[2], $args[3], $args[4]);
                default :
                    /** @noinspection PhpParamsInspection */
                    return call_user_func_array($this->project_configuration->$func, $args);
            }
        }

        return parent::__call($func, $args);
    }

    public function initialize()
    {
        if (!$this->project_dir) {
            throw new lcInvalidArgumentException('Invalid project directory');
        }

        // initialize project configuration first
        if ($this->project_configuration) {
            $this->project_configuration->initialize();
        }

        // pass the project base dir
        $this->base_config_dir = $this->project_configuration->getBaseConfigDir();

        parent::initialize();
    }

    public function shutdown()
    {
        // shutdown project_configuration
        if ($this->project_configuration) {
            $this->project_configuration->shutdown();
        }

        $this->use_models = $this->project_configuration = null;

        parent::shutdown();
    }

    public function getConfigHandleMap()
    {
        // we load the project's config map ourselves
        $config_map_project = $this->project_configuration ? $this->project_configuration->getConfigHandleMap() : array();
        return $config_map_project;
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

    public function getAutoloadClasses()
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

    public function getUsedDbModels()
    {
        $project_models = ($this->project_configuration && $this->project_configuration instanceof iSupportsDbModelOperations) ? $this->project_configuration->getUsedDbModels() : array();

        $models = array_unique(array_merge((array)$this->use_models, (array)$project_models));

        return $models;
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = array('application_name' => $this->getApplicationName());

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    abstract public function getApplicationName();

    public function setIsDebugging($debug = true)
    {
        if ($this->project_configuration) {
            $this->project_configuration->setIsDebugging($debug);
        }

        // disable caching the configuration if debugging
        if ($debug) {
            $this->environment = lcEnvConfigHandler::ENVIRONMENT_DEBUG;
        } else {
            $this->environment = lcEnvConfigHandler::ENVIRONMENT_RELEASE;
        }
    }

    public function setEnvironment($environment)
    {
        if ($this->project_configuration) {
            $this->project_configuration->setEnvironment($environment);
        }

        $this->environment = $environment;
    }

    public function getShouldLoadPlugins()
    {
        return $this->should_load_plugins;
    }

    public function setShouldLoadPlugins($should_load_plugins = true)
    {
        $this->should_load_plugins = $should_load_plugins;
    }

    public function getShouldDisableModels()
    {
        return $this->should_disable_models;
    }

    public function getShouldUseDefaultLoaders()
    {
        return $this->should_use_default_loaders;
    }

    public function setShouldUseDefaultLoaders($use_default_loaders = false)
    {
        $this->should_use_default_loaders = $use_default_loaders;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getEventDispatcher()
    {
        return $this->project_configuration->getEventDispatcher();
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
        $this->project_configuration->setEventDispatcher($event_dispatcher);
    }

    public function getClassAutoloader()
    {
        return $this->project_configuration->getClassAutoloader();
    }

    public function setClassAutoloader(lcClassAutoloader $class_autoloader)
    {
        $this->class_autoloader = $class_autoloader;
        $this->project_configuration->setClassAutoloader($class_autoloader);
    }

    public function getProjectConfiguration()
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

    public function getAdminEmail()
    {
        $email = $this->get('admin_email');
        $email = !$email ? $this->get('settings.admin_email') : $email;
        $email = !$email ? ini_get('sendmail_from') : $email;
        $email = !$email ? get_current_user() . '@' . php_uname('n') : $email;
        $email = !$email ? 'root@localhost' : $email;
        return $email;
    }

    public function getDefaultEmailSender()
    {
        $email = ini_get('sendmail_from');
        $email = !$email ? get_current_user() . '@' . php_uname('n') : $email;
        $email = !$email ? 'root@localhost' : $email;
        return $email;
    }

    public function getUniqueProjectId()
    {
        // default unique id is composed of project_name, application_name,
        // is_debugging setting
        // it is used as the unique cache key
        $ret = $this->getProjectName() . ($this->project_configuration ? $this->project_configuration->getConfigVersion() : null) . $this->getEnvironment() . $this->getConfigEnvironment() . ($this->project_configuration ? 'rev' . $this->project_configuration->getRevisionVersion() : null) . ($this->unique_id_suffix ? $this->unique_id_suffix : null);

        $ret = md5($ret);

        return $ret;
    }

    public function getUniqueId()
    {
        // default unique id is composed of project_name, application_name,
        // is_debugging setting
        // it is used as the unique cache key
        $ret = $this->getProjectAppName($this->getApplicationName()) . ($this->project_configuration ? $this->project_configuration->getConfigVersion() : null) . $this->getEnvironment() . $this->getConfigEnvironment() . ($this->project_configuration ? 'rev' . $this->project_configuration->getRevisionVersion() : null) . ($this->unique_id_suffix ? $this->unique_id_suffix : null);

        $ret = md5($ret);

        return $ret;
    }

    public function getUniqueIdSuffix()
    {
        return $this->unique_id_suffix;
    }

    public function setUniqueIdSuffix($unique_id_suffx)
    {
        // override the automatically generated unique_id with an appended suffix
        // necessary in cases where the website will be used multiply times on
        // the same
        // machine and the need of separate caches is in place
        $this->unique_id_suffix = $unique_id_suffx;
    }

    public function getApplicationCacheDir()
    {
        return $this->project_configuration->getCacheDir() . DS . 'applications' . DS . $this->getApplicationName();
    }

    public function getEnabledPlugins()
    {
        return $this['plugins.enabled'];
    }

    public function writeClassCache()
    {
        $parent_cache = (array)parent::writeClassCache();
        $project_cache = ($this->project_configuration && ($this->project_configuration instanceof iCacheable)) ? $this->project_configuration->writeClassCache() : array();

        $cache = array(
            'parent_cache' => $parent_cache,
            'project_cache' => $project_cache
        );

        return $cache;
    }

    public function readClassCache(array $cached_data)
    {
        if (isset($cached_data['parent_cache'])) {
            parent::readClassCache($cached_data['parent_cache']);
        }

        if ($this->project_configuration && ($this->project_configuration instanceof iCacheable)) {
            if (isset($cached_data['project_cache'])) {
                $this->project_configuration->readClassCache($cached_data['project_cache']);
            }
        }
    }

    // TODO: Remove this when Configurations are combined
    // it is here because it's a frequently accessed method and it is slow
    // to call it with magic
    public function getGenDir()
    {
        return $this->project_configuration->getGenDir();
    }
}
