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
abstract class lcPlugin extends lcAppObj implements iDebuggable, iSupportsComponentOperations
{
    public const ASSETS_PATH = 'web';
    public const CONFIG_PATH = 'Config';
    public const MODELS_PATH = 'Models';
    public const MODULES_PATH = 'Modules';
    public const ACTION_FORMS_PATH = 'Forms';
    public const COMPONENTS_PATH = 'Components';
    public const WEB_SERVICES_PATH = 'WebServices';
    public const TASKS_PATH = 'Tasks';

    /** @var ?lcApp */
    protected ?lcApp $app_context = null;

    protected bool $app_initialize_done = false;

    /** @var ?lcDatabaseModelManager */
    protected ?lcDatabaseModelManager $database_model_manager = null;

    /** @var ?lcSystemComponentFactory */
    protected ?lcSystemComponentFactory $system_component_factory = null;

    /** @var ?lcPluginConfiguration */
    protected ?lcPluginConfiguration $plugin_configuration = null;

    /** @var array */
    protected array $use_components = [];

    /** @var lcComponent[] */
    protected array $loaded_components = [];

    protected ?string $controller_name = null;
    protected ?string $controller_filename = null;

    protected $context_type = null;
    protected $context_name = null;

    private array $included_javascripts = [];
    private array $included_stylesheets = [];

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

    /**
     * @return string
     */
    protected function getRandomIdentifier(): string
    {
        return 'plugin_' . $this->getPluginName() . '_' . $this->getClassName() . '_' . lcStrings::randomString(15);
    }

    /**
     * @return array
     */
    public function getIncludedJavascripts(): array
    {
        return $this->included_javascripts;
    }

    /**
     * @return array
     */
    public function getIncludedStylesheets(): array
    {
        return $this->included_stylesheets;
    }

    /**
     * @param $src
     * @param array|null $options
     * @param null $tag
     * @return $this
     */
    protected function includeJavascript($src, array $options = null, $tag = null): lcPlugin
    {
        $tag = $tag ?: 'js_' . $this->getRandomIdentifier();
        $this->included_javascripts[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    /**
     * @param $src
     * @param array|null $options
     * @param $tag
     * @return $this
     */
    protected function includeStylesheet($src, array $options = null, $tag = null): lcPlugin
    {
        $tag = $tag ?: 'css_' . $this->getRandomIdentifier();
        $this->included_stylesheets[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    /**
     * @param $component_name
     * @param $context_type
     * @param $context_name
     * @return lcComponent|null
     * @throws lcInvalidArgumentException
     * @throws lcLogicException
     * @throws lcNotAvailableException
     * @throws lcRequirementException
     * @throws lcSystemException
     */
    protected function getComponentControllerInstance($component_name, $context_type = null, $context_name = null): ?lcComponent
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
            $controller_instance->loadDependencies();
        } catch (Exception $e) {
            throw new lcRequirementException('Component dependancies could not be loaded (' . $component_name . '): ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // do not initialize the object yet! leave it to the caller

        return $controller_instance;
    }

    /**
     * @return bool
     */
    public function getHasAppInitialized(): bool
    {
        return $this->app_initialize_done;
    }

    public function shutdown()
    {
        // shutdown loaded components
        $loaded_components = $this->loaded_components;

        if ($loaded_components) {
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
            null;

        $this->loaded_components =
        $this->use_components = [];

        parent::shutdown();
    }

    /**
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'name' => $this->controller_name,
            'configuration' => ($this->plugin_configuration instanceof iDebuggable ?
                $this->plugin_configuration->getDebugInfo() : null),
        ];
    }

    /**
     * @return false
     */
    public function getShortDebugInfo(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function getUsedComponents(): array
    {
        return $this->use_components;
    }

    /**
     * @return lcDatabaseModelManager
     */
    public function getDatabaseModelManager(): lcDatabaseModelManager
    {
        return $this->database_model_manager;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager = null)
    {
        $this->database_model_manager = $database_model_manager;
    }

    /**
     * @return lcSystemComponentFactory
     */
    public function getSystemComponentFactory(): lcSystemComponentFactory
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory = null)
    {
        $this->system_component_factory = $component_factory;
    }

    /**
     * @return lcPluginConfiguration
     */
    public function getPluginConfiguration(): lcPluginConfiguration
    {
        return $this->plugin_configuration;
    }

    public function setPluginConfiguration(lcConfiguration $configuration = null)
    {
        $this->plugin_configuration = $configuration;
    }

    /**
     * @return string|null
     */
    public function getControllerName(): ?string
    {
        return $this->controller_name;
    }

    /**
     * @param $controller_name
     * @return void
     */
    public function setControllerName($controller_name)
    {
        $this->controller_name = $controller_name;
    }

    /**
     * @return string|null
     */
    public function getControllerFilename(): ?string
    {
        return $this->controller_filename;
    }

    /**
     * @param $controller_filename
     * @return void
     */
    public function setControllerFilename($controller_filename)
    {
        $this->controller_filename = $controller_filename;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->controller_name;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->getPluginDir();
    }

    /**
     * @return string
     */
    public function getPluginDir(): string
    {
        return $this->plugin_configuration->getPluginDir();
    }

    /**
     * @return string
     */
    public function getComponentsDir(): string
    {
        return $this->getPluginDir() . DS . self::COMPONENTS_PATH;
    }

    /**
     * @return string
     */
    public function getModelsDir(): string
    {
        return $this->getPluginDir() . DS . self::MODELS_PATH;
    }

    /**
     * @return string
     */
    public function getModulesDir(): string
    {
        return $this->getPluginDir() . DS . self::MODULES_PATH;
    }

    /**
     * @return string
     */
    public function getAssetsPath(): string
    {
        return $this->getPluginDir() . DS . self::ASSETS_PATH;
    }

    /**
     * @param $type
     * @return ?string
     */
    public function getAssetsWebPath($type = null): ?string
    {
        $webpath = $this->getWebPath();

        if (!$webpath) {
            return null;
        }

        return $webpath . '/' . ($type ? $type . '/' : null);
    }

    /**
     * @return ?string
     */
    public function getWebPath(): ?string
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

    /**
     * @return lcPluginManager
     */
    protected function getPluginManager(): lcPluginManager
    {
        return $this->plugin_manager;
    }

    /**
     * @return mixed
     */
    public function getContextName()
    {
        return $this->context_name;
    }

    /**
     * @param $context_name
     * @return void
     */
    public function setContextName($context_name)
    {
        $this->context_name = $context_name;
    }

    /**
     * @return int
     */
    public function getContextType(): int
    {
        return $this->context_type;
    }

    /**
     * @param $context_type
     * @return void
     */
    public function setContextType($context_type)
    {
        $this->context_type = $context_type;
    }

    /**
     * @return string|null
     */
    public function getPluginName(): ?string
    {
        return $this->controller_name;
    }

    /**
     * @param $component_name
     * @return lcComponent
     * @throws lcComponentException
     */
    protected function getComponent($component_name): lcComponent
    {
        try {
            if (!$this->app_initialize_done) {
                throw new lcLogicException('Component is not available for usage until the app has been initialized');
            }

            $component_instance = $this->loaded_components[$component_name] ?? null;

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

    /**
     * @param $plugin_name
     * @return lcPlugin|null
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    protected function tryPlugin($plugin_name): ?lcPlugin
    {
        return $this->plugin_manager->getPlugin($plugin_name, true, false);
    }

    /**
     * @param $plugin_name
     * @return lcPlugin|null
     * @throws lcInvalidArgumentException
     * @throws lcPluginException
     */
    protected function getPlugin($plugin_name): ?lcPlugin
    {
        return $this->plugin_manager->getPlugin($plugin_name);
    }
}
