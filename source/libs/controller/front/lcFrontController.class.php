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
 * @changed $Id: lcFrontController.class.php 1543 2014-06-21 06:09:06Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1543 $
 */
abstract class lcFrontController extends lcAppObj implements iFrontController
{
    const DEFAULT_MAX_FORWARDS = 10;
    const DEFAULT_HAS_LAYOUT = true;

    /** @var lcControllerStack */
    protected $controller_stack;

    /** @var lcActionFilterChain */
    protected $action_filter_chain;

    /** @var lcViewFilterChain */
    protected $view_filter_chain;

    protected $max_forwards;

    /** @var array */
    protected $enabled_modules;

    /** @var array */
    protected $disabled_modules;

    /** @var lcSystemComponentFactory */
    protected $system_component_factory;

    /** @var lcDatabaseModelManager */
    protected $database_model_manager;

    /** @var lcPluginManager */
    protected $plugin_manager;

    protected $default_decorator;

    public function initialize()
    {
        parent::initialize();

        // init action stack
        if (!$this->controller_stack) {
            $this->controller_stack = new lcControllerStack();
            $this->controller_stack->initialize();
        }

        // init controller filter chain
        $this->initControllerFilterChain();

        // init view filter chain
        $this->initViewFilterChain();

        // max forwards
        $this->max_forwards = isset($this->configuration['controller.max_forwards']) ? (int)$this->configuration['controller.max_forwards'] : self::DEFAULT_MAX_FORWARDS;

        // enabled / disabled modules
        $this->enabled_modules = (array)$this->configuration['settings.enabled_modules'];
        $this->disabled_modules = (array)$this->configuration['settings.disabled_modules'];
    }

    protected function initControllerFilterChain()
    {
        if ($this->action_filter_chain) {
            return;
        }

        $this->action_filter_chain = new lcActionFilterChain();
        $this->action_filter_chain->initialize();

        // load filters from configuration
        $config_filters = (array)$this->configuration['controller.filters'];

        if ($config_filters) {
            foreach ($config_filters as $filter_class) {
                if (!class_exists($filter_class)) {
                    throw new lcNotAvailableException('Controller filter \'' . $filter_class . '\' not available');
                }

                $obj = new $filter_class();
                $obj->initialize();

                $this->action_filter_chain->addFilter($obj);

                unset($filter_class, $obj);
            }
        }
    }

    protected function initViewFilterChain()
    {
        if ($this->view_filter_chain) {
            return;
        }

        $this->view_filter_chain = new lcViewFilterChain();
        $this->view_filter_chain->setConfiguration($this->configuration);
        $this->view_filter_chain->setEventDispatcher($this->event_dispatcher);
        $this->view_filter_chain->initialize();

        // load filters from configuration
        $config_filters = (array)$this->configuration['view.filters'];

        if ($config_filters) {
            foreach ($config_filters as $filter_class) {
                if (!class_exists($filter_class)) {
                    throw new lcNotAvailableException('View filter \'' . $filter_class . '\' not available');
                }

                $obj = new $filter_class();
                $obj->setConfiguration($this->configuration);
                $obj->setEventDispatcher($this->event_dispatcher);
                $obj->initialize();

                $this->view_filter_chain->addViewFilter($obj);

                unset($filter_class, $obj);
            }
        }
    }

    public function shutdown()
    {
        // shutdown view filter chain
        if ($this->view_filter_chain) {
            $this->view_filter_chain->shutdown();
            $this->view_filter_chain = null;
        }

        // shutdown action filter chain
        if ($this->action_filter_chain) {
            $this->action_filter_chain->shutdown();
            $this->action_filter_chain = null;
        }

        // shutdown the stack and all controllers
        if ($this->controller_stack) {
            $this->controller_stack->shutdown();
            $this->controller_stack = null;
        }

        $this->max_forwards =
        $this->enabled_modules =
        $this->disabled_modules =
        $this->database_model_manager =
        $this->plugin_manager =
        $this->system_component_factory = null;

        parent::shutdown();
    }

    public function dispatch()
    {
        $request = $this->getRequest();

        if (!$request) {
            throw new lcNotAvailableException('Request not available');
        }

        // allow customized first-time initialization
        $this->beforeDispatch();

        // prepare the dispatch params
        $controller = $request->getParam('module');
        $action = $request->getParam('action');

        $request_params = $this->prepareDispatchParams($request);

        // allow customized functionality before dispatching
        if (!$this->shouldDispatch($controller, $action, $request_params)) {
            return;
        }

        // TODO: security.is_secure = TRUE - if a controller is not found
        // and security is enabled - we must not return the not found error response
        // but an access denied instead.

        // forward the request
        $this->forward($controller, $action, array('request' => $request_params));
    }

    abstract protected function beforeDispatch();

    abstract protected function prepareDispatchParams(lcRequest $request);

    abstract protected function shouldDispatch($controller_name, $action_name, array $params = null);

    public function forward($controller_name, $action_name, array $action_params = null)
    {
        // validate and throw exception if not possible to forward
        $this->validateForward($action_name, $controller_name);

        // get an instance of the controller
        $controller = ($controller_name ? $this->getControllerInstance($controller_name) : null);

        // if unavailable process and output the error
        if (!$controller) {
            $this->handleControllerNotReachable($controller_name, $action_name, $action_params);
        }

        // prepare it
        //$this->prepareControllerInstance($controller);

        // save the dispatch params so they can be reused and recombined with further forwards later
        $controller->setDispatchParams($action_params);

        try {
            // forward to the action
            $controller->forwardToControllerAction($controller, null, $action_name, $action_params);
        } catch (Exception $e) {
            if ($e instanceof lcControllerNotFoundException || $e instanceof lcActionNotFoundException) {
                $this->handleControllerNotReachable($controller_name, $action_name, $action_params);
            }

            throw $e;
        }
    }

    public function validateForward($action_name, $controller_name, array $custom_params = null)
    {
        $disabled_modules = $this->disabled_modules;
        $enabled_modules = $this->enabled_modules;

        // check if controller is disabled in configuration
        if ($disabled_modules && in_array($controller_name, $disabled_modules)) {
            throw new lcNotAvailableException('Module ' . $controller_name . ' is disabled in configuration');
        }

        if ($enabled_modules && !in_array($controller_name, $enabled_modules)) {
            throw new lcNotAvailableException('Module ' . $controller_name . ' is not enabled in configuration');
        }

        unset($enabled_modules);

        // check max forwards
        $max_forwards = $this->max_forwards;

        assert((int)$max_forwards);

        $controller_stack = $this->controller_stack;

        if ($max_forwards && $controller_stack && $controller_stack->size() >= $max_forwards) {
            throw new lcLogicException('The maximum allowed redirects (' . $max_forwards . ') have been reached');
        }
    }

    protected function handleControllerNotReachable($controller_name, $action_name = null, array $action_params = null)
    {
        // loop protection
        static $already_forwarded;

        if (!$already_forwarded) {
            $already_forwarded = true;

            // notify listeners
            $this->event_dispatcher->notify(new lcEvent('controller.not_found', $this,
                array('controller_name' => $controller_name,
                    'action_name' => $action_name,
                    'action_type' => (isset($action_params['type']) ? $action_params['type'] : null),
                    'action_params' => $action_params,
                )
            ));

            // fetch the not_found config from routing and forward
            // to the controller if specified
            $nf = $this->configuration['routing.not_found_action'];
            $nf_module = isset($nf['module']) ? (string)$nf['module'] : null;
            $nf_action = isset($nf['action']) ? (string)$nf['action'] : null;

            if ($nf_module && $nf_action) {
                $this->info('Forwarding to \'routing.not_found_action\': ' . $nf_module . '/' . $nf_action);
                $this->forward($nf_module, $nf_action, $action_params);
            }

            unset($nf, $nf_module, $nf_action);
        }

        $this->handleControllerNotReachableAfter();
    }

    protected function handleControllerNotReachableAfter()
    {
        // final stop
        throw new lcControllerForwardException('Could not forward to controller action');
    }

    public function getControllerStack()
    {
        return $this->controller_stack;
    }

    public function getTopController()
    {
        if (!$this->controller_stack) {
            return null;
        }

        // get the top controller on the stack
        $controller_instance = $this->controller_stack->first();
        $controller = $controller_instance ? $controller_instance->getControllerInstance() : null;
        return $controller;
    }

    public function getLastController()
    {
        if (!$this->controller_stack) {
            return null;
        }

        // get the top controller on the stack
        $controller_instance = $this->controller_stack->last();
        $controller = $controller_instance ? $controller_instance->getControllerInstance() : null;
        return $controller;
    }

    public function filterForwardParams(array &$forward_params)
    {
        if (!$forward_params) {
            return;
        }

        $reserved = self::forwardReservedParams();

        foreach ($forward_params as $k => $v) {
            if (in_array($k, $reserved)) {
                unset($forward_params[$k]);
            }

            unset($k, $v);
        }
    }

    protected static function forwardReservedParams()
    {
        $ret = array(
            'type',
            'request',
        );
        return $ret;
    }

    public function getSystemComponentFactory()
    {
        return $this->system_component_factory;
    }

    public function setSystemComponentFactory(lcSystemComponentFactory $component_factory)
    {
        $this->system_component_factory = $component_factory;
    }

    public function getDatabaseModelManager()
    {
        return $this->database_model_manager;
    }

    public function setDatabaseModelManager(lcDatabaseModelManager $database_model_manager)
    {
        $this->database_model_manager = $database_model_manager;
    }

    public function getPluginManager()
    {
        return $this->plugin_manager;
    }

    public function setPluginManager(lcPluginManager $plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;
    }

    public function setDecorator($decorator)
    {
        $this->default_decorator = $decorator;
    }

    /*
     * LC 1.4 Compatibility method - initializes a different decorator upon forwarding to the
     * first controller
     */

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

        // translation context
        $controller->setTranslationContext($controller->getContextType(), $controller->getContextName());

        $controller->setClassAutoloader($this->class_autoloader);
        $controller->setPluginManager($this->plugin_manager);
        $controller->setDatabaseModelManager($this->database_model_manager);
        $controller->setSystemComponentFactory($this->system_component_factory);

        $controller->setRootController($this);
        $controller->setViewFilterChain($this->view_filter_chain);
        $controller->setActionFilterChain($this->action_filter_chain);
        $controller->setControllerStack($this->controller_stack);
    }
}
