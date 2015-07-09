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
 *
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcBaseController.class.php 1595 2015-06-22 11:21:45Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1595 $
 */
abstract class lcBaseController extends lcAppObj implements iProvidesCapabilities, iDebuggable, iCacheable, iPluginContained,
    iSupportsDbModelOperations, iSupportsPluginOperations, iSupportsComponentOperations
{
    const RENDER_NONE = 2;
    const RENDER_VIEW = 3;

    /**
     * @var lcSystemComponentFactory
     */
    protected $system_component_factory;

    /**
     * @var lcPluginManager
     */
    protected $plugin_manager;

    /**
     * @var lcDatabaseModelManager
     */
    protected $database_model_manager;

    /**
     * @var lcView
     */
    protected $view;

    /**
     * @var lcViewFilterChain
     */
    protected $view_filter_chain;

    protected $view_render_type;

    protected $use_models;
    protected $use_components;
    protected $use_plugins;

    protected $dependancies_loaded;

    /**
     * @var lcPlugin[]
     */
    protected $plugins;

    /**
     * @var array
     */
    protected $loaded_components;
    protected $controller_name;
    protected $controller_filename;
    protected $assets_path;
    protected $assets_webpath;
    private $loaded_components_usage;

    abstract public function getProfilingData();

    abstract public function getDefaultViewInstance();

    public function shutdown()
    {
        // shutdown the view
        if ($this->view) {
            $this->view->shutdown();
            $this->view = null;
        }

        // shutdown loaded components
        $this->shutdownComponents();

        $this->system_component_factory =
        $this->view_filter_chain =
        $this->plugin_manager =
        $this->database_model_manager =
        $this->view =
        $this->use_plugins =
        $this->use_models =
        $this->use_components =
        $this->plugins =
        $this->loaded_components =
        $this->loaded_components_usage =
            null;

        parent::shutdown();
    }

    public function shutdownComponents()
    {
        $loaded_components = $this->loaded_components;

        if ($loaded_components) {
            foreach ($loaded_components as $idx => $component) {
                /** @var lcComponent $cinstance */
                $cinstance = $component['instance'];
                $cinstance->shutdown();
                unset($this->loaded_components[$idx]);

                unset($component, $idx);
            }
        }

        $this->loaded_components =
        $this->loaded_components_usage = null;
    }

    public function getCapabilities()
    {
        return array(
            'controller'
        );
    }

    public function getDebugInfo()
    {
        $debug = array(
            'translation_context_type' => $this->translation_context_type,
            'translation_context_name' => $this->translation_context_name,
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function renderView()
    {
        return $this->render();
    }

    public function render()
    {
        return $this->renderControllerView($this, $this->getView());
    }

    abstract protected function renderControllerView(lcBaseController $controller, lcView $view);

    public function getView()
    {
        return $this->view;
    }

    public function setView(lcView $view = null)
    {
        $this->setViewRenderType($view ? lcBaseController::RENDER_VIEW : lcBaseController::RENDER_NONE);

        if ($view !== $this->view) {
            $this->unsetView();
        }

        $this->view = $view;
    }

    public function unsetView()
    {
        if ($this->view) {
            $this->view->shutdown();
            $this->view = null;
        }
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory)
    {
        $this->system_component_factory = $component_factory;
    }

    /*
     * Because of incompatibilities in several utilities (form_helper) from LC 1.4
    * we need to keep this public for the moment.
    */

    public function getPluginManager()
    {
        return $this->plugin_manager;
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager)
    {
        $this->database_model_manager = $database_model_manager;
    }

    public function useModel($model_name)
    {
        $this->database_model_manager->useModel($model_name);
    }

    public function useModels(array $models)
    {
        $this->database_model_manager->useModels($models);
    }

    public function getViewFilterChain()
    {
        return $this->view_filter_chain;
    }

    public function setViewFilterChain(lcViewFilterChain $view_filter_chain = null)
    {
        $this->view_filter_chain = $view_filter_chain;
    }

    public function getViewRenderType()
    {
        return $this->view_render_type;
    }

    protected function setViewRenderType($render_type)
    {
        $this->view_render_type = $render_type;
    }

    public function getAssetsWebpath()
    {
        return $this->assets_webpath;
    }

    public function setAssetsWebpath($assets_webpath)
    {
        $this->assets_webpath = $assets_webpath;
    }

    public function getAssetsPath()
    {
        return $this->assets_path;
    }

    public function setAssetsPath($assets_path)
    {
        $this->assets_path = $assets_path;
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

    public function getControllerDirectory()
    {
        $ret = $this->controller_filename ? dirname($this->controller_filename) : null;
        return $ret;
    }

    public function setFlash($flash)
    {
        return $this->user->setFlash($flash);
    }

    public function sendMail($to, $message, $subject = null, $from = null)
    {
        $mailer = $this->mailer;

        if (!$mailer) {
            throw new lcNotAvailableException('Mailer not available');
        }

        $mailer->clear();

        // mixed input
        if (is_array($to)) {
            foreach ($to as $email) {
                $mailer->addRecipient(new lcMailRecipient($email));
                unset($email);
            }
        } else {
            $mailer->addRecipient(new lcMailRecipient($to));
        }

        $from = $from ? $from : (string)$this->configuration->getAdminEmail();

        $mailer->setBody($message);
        $mailer->setSubject($subject);
        $mailer->setSender(new lcMailRecipient($from));

        try {
            $res = $mailer->send();

            return $res;
        } catch (Exception $e) {
            if (DO_DEBUG) {
                throw $e;
            }

            $this->warning('Could not send email (' . count($to) . ' recipients, subject: ' . $subject . '): ' . $e->getMessage());

            return false;
        }
    }

    public function hasCredential($credential_name)
    {
        return ($this->user ? $this->user->hasCredential($credential_name) : false);
    }

    public function writeClassCache()
    {
        // subclassers may override this to write their caches
    }

    public function readClassCache(array $cached_data)
    {
        // subclassers may override this to read their caches
    }

    protected function validateRequestDataAndThrow(array $config)
    {
        $failures = array();
        $is_valid = $this->validateData($config, $failures);

        if (!$is_valid) {
            $vex = new lcValidationException($this->t('Invalid request data'));
            $vex->setValidationFailures($failures);
            throw $vex;
        }

        return $is_valid;
    }

    protected function validateData(array $config, array &$failed_validations = null)
    {
        if (!$config) {
            return false;
        }

        $failed_validations = array();

        $is_validated = true;

        foreach ($config as $data_name => $options) {
            $validator_name = isset($options['validator']) ? $options['validator'] : null;
            $value = isset($options['value']) ? $options['value'] : null;
            $fail_msg = isset($options['fail']) ? $options['fail'] : null;

            if (!$validator_name || !$data_name) {
                assert(false);
                $is_validated = false;
                continue;
            }

            // find a validator
            $cl_name = 'lc' . lcInflector::camelize($validator_name, false) . 'Validator';

            if (!class_exists($cl_name)) {
                assert(false);
                $is_validated = false;
                continue;
            }

            $validator = new $cl_name();

            if (!($validator instanceof lcValidator)) {
                assert(false);
                $is_validated = false;
                continue;
            }

            $is_valid = $validator->validate($value);

            if (!$is_valid) {
                $failed_validations[] = new lcValidatorFailure($data_name, $fail_msg, $validator);
                $is_validated = false;
            }

            unset($value, $cl_name, $validator, $is_valid, $fail_msg);
        }

        return $is_validated;
    }

    protected function initComponent($component_name)
    {
        return $this->getComponent($component_name);
    }

    public function getComponent($component_name)
    {
        if (!$component_name) {
            throw new lcInvalidArgumentException('Invalid component name');
        }

        $component_instance = null;

        try {
            $usage_count = isset($this->loaded_components_usage[$component_name]) ? (int)count($this->loaded_components_usage[$component_name]) : null;

            // check if component is loaded and has been initialized once (dependancies)
            if (is_null($usage_count)) {
                throw new lcRequirementException('Component has not been required');
            }

            // check if component used once (the already precreated first component upon initialization)
            // if used - create a new instance and return it
            if ($usage_count) {
                // yes - already used - create a new instance
                $component_instance = $this->getComponentControllerInstance($component_name);

                if (!$component_instance) {
                    throw new lcNotAvailableException('Component not available');
                }

                // initialize it
                $component_instance->initialize();

                $usage_count++;

                $this->loaded_components[] = array(
                    'name' => $component_name,
                    'instance' => $component_instance
                );

                $this->loaded_components_usage[$component_name] = $usage_count;
            } else {
                // never used - pass the first initialized instance
                $loaded_components = $this->loaded_components;

                foreach ($loaded_components as $instance) {
                    if ($instance['name'] == $component_name) {
                        $component_instance = $instance['instance'];
                        break;
                    }

                    unset($instance);
                }
            }

            if (!$component_instance) {
                throw new lcNotAvailableException('Component not available');
            }
        } catch (Exception $e) {
            throw new lcComponentException('Could not initialize component \'' . $component_name . '\': ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        return $component_instance;
    }

    /**
     * @param $component_name
     * @param null $context_type
     * @param null $context_name
     * @return lcComponent
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     * @throws lcRequirementException
     * @throws lcSystemException
     */
    protected function getComponentControllerInstance($component_name, $context_type = null, $context_name = null)
    {
        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        $controller_instance = $this->system_component_factory->getControllerComponentInstance($component_name, $context_type, $context_name);

        if (!$controller_instance) {
            return null;
        }

        // assign system objects
        $controller_instance->setController($this);

        $this->prepareControllerInstance($controller_instance);

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

    protected function prepareControllerInstance(lcBaseController $controller)
    {
        $controller->setEventDispatcher($this->event_dispatcher);
        $controller->setConfiguration($this->configuration);

        $controller->setRequest($this->request);
        $controller->setResponse($this->response);
        $controller->setRouting($this->routing);
        $controller->setI18n($this->i18n);
        $controller->setDatabaseManager($this->database_manager);
        $controller->setStorage($this->storage);
        $controller->setUser($this->user);
        $controller->setLogger($this->logger);
        $controller->setMailer($this->mailer);
        $controller->setDataStorage($this->data_storage);
        $controller->setCache($this->cache);

        $controller->setViewFilterChain($this->view_filter_chain);

        // translation context
        $controller->setTranslationContext($controller->getContextType(), $controller->getContextName());

        $controller->setClassAutoloader($this->class_autoloader);
        $controller->setPluginManager($this->plugin_manager);
        $controller->setDatabaseModelManager($this->database_model_manager);
        $controller->setSystemComponentFactory($this->system_component_factory);
    }

    public function loadDependancies()
    {
        if ($this->dependancies_loaded) {
            return;
        }

        // plugin dependancies
        if ($this->plugin_manager) {
            $plugins = array();
            $context_plugin_name = $this->context_name;
            $my_plugin = null;

            // check if the controller is contained within a plugin
            // load it first - so all it's dependancies are loaded prior doing anything else
            if ($this->context_type == self::CONTEXT_PLUGIN) {
                assert(!is_null($context_plugin_name));

                if ($context_plugin_name) {
                    try {
                        $my_plugin = $this->plugin_manager->getPlugin($context_plugin_name);

                        if (!$my_plugin) {
                            throw new lcNotAvailableException('Plugin not available');
                        }
                    } catch (Exception $e) {
                        throw new lcRequirementException('Parent plugin not available (' . $context_plugin_name . '): ' .
                            $e->getMessage(),
                            $e->getCode(),
                            $e);
                    }

                    $plugins[$context_plugin_name] = &$my_plugin;
                }
            }

            $used_plugins = $this->getUsedPlugins();

            if ($used_plugins && is_array($used_plugins)) {
                foreach ($used_plugins as $plugin_name) {
                    // skip the context plugin name so we do not double call this
                    if ($context_plugin_name && $context_plugin_name == $plugin_name) {
                        continue;
                    }

                    try {
                        $plugin = $this->plugin_manager->getPlugin($plugin_name);

                        if (!$plugin) {
                            throw new lcNotAvailableException('Plugin not available');
                        }
                    } catch (Exception $e) {
                        throw new lcRequirementException('Plugin dependancy not available (' . $plugin_name . '): ' .
                            $e->getMessage(),
                            $e->getCode(),
                            $e);
                    }

                    $plugins[$plugin_name] = &$plugin;

                    unset($plugin_name, $plugin);
                }
            }

            $this->plugins = $plugins;
            $this->parent_plugin = $my_plugin;

            unset($context_plugin_name, $my_plugin);
        }

        // component dependancies
        $used_components = $this->getUsedComponents();

        if ($used_components && is_array($used_components)) {
            $loaded_components = array();
            $loaded_components_usage = array();

            foreach ($used_components as $component_name) {
                try {
                    $component_instance = $this->getComponentControllerInstance($component_name);

                    if (!$component_instance) {
                        throw new lcNotAvailableException('Component not available');
                    }

                    // initialize it
                    $component_instance->initialize();

                    $loaded_components[] = array(
                        'name' => $component_name,
                        'instance' => $component_instance
                    );
                    $loaded_components_usage[$component_name] = 0;
                } catch (Exception $e) {
                    throw new lcRequirementException('Component dependancy not available (' . $component_name . '): ' .
                        $e->getMessage(),
                        $e->getCode(),
                        $e);
                }

                unset($component_name);
            }

            $this->loaded_components = $loaded_components;
            $this->loaded_components_usage = $loaded_components_usage;
        }

        // db model dependancies
        if ($this->database_model_manager) {
            $used_models = $this->getUsedDbModels();

            if ($used_models && is_array($used_models)) {
                try {
                    $this->database_model_manager->useModels($used_models);
                } catch (Exception $e) {
                    throw new lcRequirementException('Could not include database model dependancies: ' .
                        $e->getMessage(),
                        $e->getCode(),
                        $e);
                }
            }
        }

        $this->dependancies_loaded = true;
    }

    public function getUsedPlugins()
    {
        return $this->use_plugins;
    }

    public function getUsedComponents()
    {
        return $this->use_components;
    }

    public function initialize()
    {
        parent::initialize();

        // verify if the controller meets the requirements
        $meets_requirements = $this->getMeetsRequirements();

        if (!$meets_requirements) {
            throw new lcRequirementException($this->t('Cannot instantiate controller - requirements not met:') . ' ' . get_class($this));
        }
    }

    protected function getMeetsRequirements()
    {
        // subclassers may override this method and return false
        // when the controller should not be instantiated
        // for example - web management modules should require the base app
        // configuration to match lcWebManagementConfiguration
        return true;
    }

    public function getUsedDbModels()
    {
        return $this->use_models;
    }

    protected function flash($flash)
    {
        if (!$this->user) {
            throw new lcNotAvailableException('User not available');
        }

        return $this->user->setFlash($flash);
    }

    #pragma mark - iCacheable

    /**
     * @param $plugin_name string - The plugin's name
     * @return lcPlugin The plugin's instance
     * @throws lcNotAvailableException
     */
    protected function getPlugin($plugin_name)
    {
        $plugin = isset($this->plugins[$plugin_name]) ? $this->plugins[$plugin_name] : null;

        if (!$plugin) {
            /** @noinspection PhpToStringImplementationInspection */
            throw new lcNotAvailableException('Plugin \'' . $plugin_name . '\' has not been required');
        }

        return $plugin;
    }

    protected function createNotFoundException($message = 'Not Found', Exception $previous_exception = null)
    {
        return new lcNotAvailableException($message, null, $previous_exception);
    }
}