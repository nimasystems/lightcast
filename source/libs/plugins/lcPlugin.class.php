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

abstract class lcPlugin extends lcAppObj implements iDebuggable, iSupportsDbModelOperations, iSupportsComponentOperations
{
    const ASSETS_PATH = 'web';
    const CONFIG_PATH = 'config';
    const MODELS_PATH = 'models';
    const MODULES_PATH = 'modules';
    const ACTION_FORMS_PATH = 'forms';
    const COMPONENTS_PATH = 'components';
    const WEB_SERVICES_PATH = 'ws';
    const TASKS_PATH = 'tasks';

    /** @var lcApp */
    protected $app_context;

    protected $app_initialize_done;

    /** @var lcDatabaseModelManager */
    protected $database_model_manager;

    /** @var lcSystemComponentFactory */
    protected $system_component_factory;

    /** @var lcPluginConfiguration */
    protected $plugin_configuration;

    /** @var array */
    protected $use_models;

    /** @var array */
    protected $use_components;

    /** @var lcComponent[] */
    protected $loaded_components;

    protected $controller_name;
    protected $controller_filename;

    protected $context_type;
    protected $context_name;

    private $included_javascripts = [];
    private $included_stylesheets = [];

    public function initialize()
    {
        parent::initialize();

        // call the plugins initialization method
        $this->execute($this->event_dispatcher, $this->configuration);
    }

    public function execute(lcEventDispatcher $event_dispatcher, lcConfiguration $configuration)
    {
        // subclassers may override this method
    }

    public function initializeWebComponents()
    {
        // subclassers may override this method to initialize their web-based components
    }

    public function initializeConsoleComponents()
    {
        // subclassers may override this method to initialize their console-based components
    }

    public function initializeWebServiceComponents()
    {
        // subclassers may override this method to initialize their web-service-based components
    }

    public function initializeApp(lcApp $context)
    {
        // subclassers may override this method when it's necessary to know
        // when the platform is fully initialized

        $this->app_context = $context;
        $this->app_initialize_done = true;

        try {
            $this->initializeComponents();
        } catch (Exception $e) {
            throw new lcPluginException('Components could not be initialized: ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }
    }

    protected function initializeComponents()
    {
        $used_components = $this->use_components;

        if (!$used_components) {
            return;
        }

        $loaded_components = [];

        foreach ($used_components as $component_name) {
            try {
                $component_instance = $this->getComponentControllerInstance($component_name);

                if (!$component_instance) {
                    throw new lcNotAvailableException('Not available');
                }

                // initialize it now
                $component_instance->initialize();

                // set it to the local array of initialized components
                $loaded_components[$component_name] = $component_instance;
            } catch (Exception $ee) {
                throw new lcComponentException('Component initialization error (' . $component_name . '): ' .
                    $ee->getMessage(),
                    $ee->getCode(),
                    $ee);
            }

            unset($component_name);
        }

        $this->loaded_components = $loaded_components;
    }

    protected function getRandomIdentifier()
    {
        return 'plugin_' . $this->getPluginName() . '_' . $this->getClassName() . '_' . lcStrings::randomString(15);
    }

    /**
     * @return array
     */
    public function getIncludedJavascripts()
    {
        return $this->included_javascripts;
    }

    /**
     * @return array
     */
    public function getIncludedStylesheets()
    {
        return $this->included_stylesheets;
    }

    /**
     * @param $src
     * @param array|null $options
     * @param null $tag
     * @return $this
     */
    protected function includeJavascript($src, array $options = null, $tag = null)
    {
        $tag = $tag ?: 'js_' . $this->getRandomIdentifier();
        $this->included_javascripts[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    protected function includeStylesheet($src, array $options = null, $tag = null)
    {
        $tag = $tag ?: 'css_' . $this->getRandomIdentifier();
        $this->included_stylesheets[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    protected function getComponentControllerInstance($component_name, $context_type = null, $context_name = null)
    {
        if (!$this->app_context || !$this->app_context->getIsInitialized()) {
            throw new lcLogicException('App not available or not initialized yet');
        }

        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        /** @var lcComponent $controller_instance */
        $controller_instance = $this->system_component_factory->getControllerComponentInstance($component_name, $context_type, $context_name);

        if (!$controller_instance) {
            return null;
        }

        // assign system objects
        $controller_instance->setEventDispatcher($this->event_dispatcher);
        $controller_instance->setConfiguration($this->configuration);
        $controller_instance->setSystemComponentFactory($this->system_component_factory);
        $controller_instance->setDatabaseModelManager($this->database_model_manager);
        $controller_instance->setPluginManager($this->plugin_manager);

        // translation context
        $controller_instance->setTranslationContext($controller_instance->getContextType(), $controller_instance->getContextName());

        // set loaders from app
        $this->app_context->setLoadersOntoObject($controller_instance);

        // resolve dependancies
        try {
            $controller_instance->loadDependancies();
        } catch (Exception $e) {
            throw new lcRequirementException('Component dependancies could not be loaded (' . $component_name . '): ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // do not initialize the object yet! leave it to the caller

        return $controller_instance;
    }

    public function getHasAppInitialized()
    {
        return $this->app_initialize_done;
    }

    public function shutdown()
    {
        // shutdown loaded components
        $loaded_components = $this->loaded_components;

        if ($loaded_components && is_array($loaded_components)) {
            foreach ($loaded_components as $idx => $component) {
                $component->shutdown();
                unset($this->loaded_components[$idx]);
                unset($idx, $component);
            }
        }

        $this->system_component_factory =
        $this->database_model_manager =
        $this->plugin_manager =
        $this->plugin_configuration =
        $this->loaded_components =
        $this->use_components =
        $this->use_models =
            null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        return [
            'name' => $this->controller_name,
            'configuration' => ($this->plugin_configuration && $this->plugin_configuration instanceof iDebuggable ?
                $this->plugin_configuration->getDebugInfo() : null),
        ];
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getUsedDbModels()
    {
        return $this->use_models;
    }

    public function getUsedComponents()
    {
        return $this->use_components;
    }

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager = null)
    {
        $this->database_model_manager = $database_model_manager;
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory = null)
    {
        $this->system_component_factory = $component_factory;
    }

    public function getPluginConfiguration()
    {
        return $this->plugin_configuration;
    }

    public function setPluginConfiguration(lcConfiguration $configuration = null)
    {
        $this->plugin_configuration = $configuration;
    }

    public function getControllerName()
    {
        return $this->controller_name;
    }

    public function setControllerName($controller_name)
    {
        $this->controller_name = $controller_name;
    }

    public function getControllerFilename()
    {
        return $this->controller_filename;
    }

    public function setControllerFilename($controller_filename)
    {
        $this->controller_filename = $controller_filename;
    }

    public function getName()
    {
        return $this->controller_name;
    }

    public function getRootDir()
    {
        return $this->getPluginDir();
    }

    public function getPluginDir()
    {
        return $this->plugin_configuration->getPluginDir();
    }

    public function getComponentsDir()
    {
        return $this->getPluginDir() . DS . self::COMPONENTS_PATH;
    }

    public function getModelsDir()
    {
        return $this->getPluginDir() . DS . self::MODELS_PATH;
    }

    public function getModulesDir()
    {
        return $this->getPluginDir() . DS . self::MODULES_PATH;
    }

    public function getAssetsPath()
    {
        return $this->getPluginDir() . DS . self::ASSETS_PATH;
    }

    public function getAssetsWebPath($type = null)
    {
        return $this->getWebPath() . self::ASSETS_PATH . '/' . ($type ? $type . '/' : null);
    }

    public function getWebPath()
    {
        return $this->plugin_configuration->getWebPath();
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

    public function setPluginManager(lcPluginManager $plugin_manager = null)
    {
        $this->plugin_manager = $plugin_manager;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

    protected function preparePluginSystemObject(lcSysObj $object)
    {
        $object->setPluginManager($this->getPluginManager());
        $object->setEventDispatcher($this->getEventDispatcher());
        $object->setConfiguration($this->getConfiguration());
        $object->setClassAutoloader($this->getClassAutoloader());
        $object->setContextName($this->getContextName());
        $object->setContextType($this->getContextType());
        $object->setLogger($this->getLogger());
        $object->setI18n($this->getI18n());
        $object->setParentPlugin($this);
        $object->setTranslationContext(lcSysObj::CONTEXT_PLUGIN, $this->getPluginName());
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

    protected function getPluginManager()
    {
        return $this->plugin_manager;
    }

    public function getContextName()
    {
        return $this->context_name;
    }

    public function setContextName($context_name)
    {
        $this->context_name = $context_name;
    }

    public function getContextType()
    {
        return $this->context_type;
    }

    public function setContextType($context_type)
    {
        $this->context_type = $context_type;
    }

    public function getPluginName()
    {
        return $this->controller_name;
    }

    protected function getComponent($component_name)
    {
        $component_instance = null;

        try {
            if (!$this->app_initialize_done) {
                throw new lcLogicException('Component is not available for usage until the app has been initialized');
            }

            $component_instance = isset($this->loaded_components[$component_name]) ? $this->loaded_components[$component_name] : null;

            if (!$component_instance) {
                throw new lcNotAvailableException('Component is not available (' . $component_name . ')');
            }
        } catch (Exception $e) {
            throw new lcComponentException('Could not get component\'s instance: ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        return $component_instance;
    }

    protected function tryPlugin($plugin_name)
    {
        return $this->plugin_manager->getPlugin($plugin_name, true, false);
    }

    protected function getPlugin($plugin_name)
    {
        return $this->plugin_manager->getPlugin($plugin_name);
    }
}
