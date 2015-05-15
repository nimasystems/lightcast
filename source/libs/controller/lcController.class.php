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
 * @changed $Id: lcController.class.php 1544 2014-06-21 06:14:47Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1544 $
 */
abstract class lcController extends lcBaseController implements iDebuggable
{
    const TYPE_ACTION = 'action';
    const TYPE_PARTIAL = 'partial';

    /*
     * Params for quick request validation
     */
    const VPOST = 'is_post';
    const VPUT = 'is_put';
    const VGET = 'is_get';
    const VAJAX = 'is_ajax';
    const VAUTH = 'is_authenticated';
    const VNAUTH = 'not_authenticated';

    /**
     * @var lcControllerStack
     */
    protected $controller_stack;

    /**
     * @var lcActionFilter[]
     */
    protected $action_filters;

    /**
     * @var lcActionFilterChain
     */
    protected $action_filter_chain;

    protected $action_name;
    protected $action_params;
    protected $action_type;

    /**
     * @var array
     */
    protected $dispatch_params;

    protected $action_result;

    /**
     * @var lcController
     */
    protected $root_controller;

    /**
     * @var lcController
     */
    protected $parent_controller;

    protected $render_time;

    /**
     * @var lcView
     */
    protected $layout_view;

    /**
     * @return lcView
     */
    abstract public function getDefaultLayoutViewInstance();

    abstract protected function classMethodForAction($action_name, array $action_params = null);

    abstract protected function actionExists($action_name, array $action_params = null);

    abstract protected function execute($action_name, array $action_params);

    abstract protected function outputViewContents(lcController $controller, $content = null, $content_type = null);

    public function initialize()
    {
        parent::initialize();

        // partial view provider

        /*
         * @deprecated Left for LC 1.4 compatibility
        */
        $this->event_dispatcher->registerProvider('controller.partial_view', $this, 'getPartialViewByEvent');
    }

    public function shutdown()
    {
        if ($this->layout_view) {
            $this->layout_view->shutdown();
            $this->layout_view = null;
        }

        $this->dispatch_params =
        $this->controller_stack =
        $this->root_controller =
        $this->parent_controller =
        $this->action_result =
        $this->action_filters =
            null;

        parent::shutdown();
    }

    protected function beforeExecute()
    {
        // subclassers may override this method to execute code before the initialization of the controller
    }

    protected function afterExecute()
    {
        // subclassers may override this method to execute code after the initialization of the controller
    }

    public function getProfilingData()
    {
        // TODO: Complete this
        return null;
    }

    public function setControllerStack(lcControllerStack $stack)
    {
        $this->controller_stack = $stack;
    }

    public function getControllerStack()
    {
        return $this->controller_stack;
    }

    public function setActionFilterChain(lcActionFilterChain $action_filter_chain)
    {
        $this->action_filter_chain = $action_filter_chain;
    }

    public function getActionFilterChain()
    {
        return $this->action_filter_chain;
    }

    public function getActionFilters()
    {
        return $this->action_filters;
    }

    public function setDispatchParams(array $dispatch_params = null)
    {
        $this->dispatch_params = &$dispatch_params;
    }

    public function & getDispatchParams()
    {
        return $this->dispatch_params;
    }

    protected function validateRequestAndThrow()
    {
        $args = func_get_args();

        if ($args) {
            foreach ($args as $arg) {
                if (($arg == self::VPOST && !$this->request->isPost()) ||
                    ($arg == self::VPUT && !$this->request->isPut()) ||
                    ($arg == self::VGET && !$this->request->isGet()) ||
                    ($arg == self::VAJAX && !$this->request->isAjax()) ||
                    ($arg == self::VAUTH && !$this->user->isAuthenticated()) ||
                    ($arg == self::VNAUTH && $this->user->isAuthenticated()) ||
                    !$arg
                ) {
                    throw new lcInvalidRequestException($this->t('Invalid Request'));
                }
            }
        }
    }

    public function setDecoratorView(iSupportsLayoutDecoration $view = null)
    {
        // LC 1.4 compatibility fixes:
        $params = array(
            'view' => $view
        );

        $full_template_name = null;

        if ($view && $view instanceof lcHTMLTemplateView) {
            $full_template_name = $view->getTemplateFilename();

            if ($full_template_name) {
                $params['template_filename'] = $full_template_name;
                $params['template_name'] = basename($full_template_name);
            }

            // send a filtering event to allow / disallow changing the decorator
            $event = $this->event_dispatcher->filter(new lcEvent('view.set_decorator', $this, $params), $full_template_name);

            if ($event->isProcessed()) {
                $return_value = $event->getReturnValue();

                if (!$return_value) {
                    $this->info('setDecoratorView was disabled by an event handler');
                    return false;
                }
            }
        }

        // unset the previous one first
        $this->unsetDecoratorView();

        // set the new one
        $this->layout_view = $view;

        if (DO_DEBUG) {
            $log_str = $this->controller_name . '/' . $this->action_name . ' set decorator to: ' . (string)$view;
            $this->notice($log_str);
        }
    }

    public function unsetDecoratorView()
    {
        if ($this->layout_view) {
            $this->layout_view->shutdown();
            $this->layout_view = null;
        }
    }

    public function getDecoratorView()
    {
        return $this->layout_view;
    }

    public function getActionResult()
    {
        return $this->action_result;
    }

    protected function forwardIf($condition, $action_name, $controller_name = null)
    {
        if ($condition) {
            return $this->forward($action_name, $controller_name);
        }
    }

    protected function forwardUnless($condition, $action_name, $controller_name = null)
    {
        if (!$condition) {
            return $this->forward($action_name, $controller_name);
        }
    }

    protected function forwardError($message = null)
    {
        throw new lcNotAvailableException($message);
    }

    protected function forwardErrorIf($condition, $message = null)
    {
        if ($condition) {
            $this->forwardError($message);
        }
    }

    protected function forwardErrorUnless($condition, $message = null)
    {
        if (!$condition) {
            $this->forwardError($message);
        }
    }

    public function forward($action_name, $controller_name = null, array $action_params = null)
    {
        $controller_name = !$controller_name ? $this->controller_name : $controller_name;

        // validate and throw exception if not possible to forward
        if ($this->root_controller) {
            $this->getRootController()->validateForward($action_name, $controller_name);
        }

        $controller_instance = $this->getControllerInstance($controller_name);

        if (!$controller_instance || !$action_name) {
            throw new lcControllerNotFoundException('Controller ' .
                ($action_name ? 'action ' : null) . ' \'' .
                $controller_name . ($action_name ? ' / ' . $action_name : null) . '\' not found');
        }

        $controller_instance->setDispatchParams($this->dispatch_params);

        $action_params['module'] = $controller_name;
        $action_params['action'] = $action_name;

        $action_params = array(
            'request' => (array)$action_params,
            'type' => (isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION)
        );

        // override the web controller's forward for the sake of using this method within
        // the context of the root view controller
        return $this->forwardToControllerAction(
            $controller_instance,
            $this,
            $action_name, $action_params);
    }

    public function forwardToControllerAction(lcController $controller_instance, lcController $parent_controller = null, $action_name, array $action_params = null)
    {
        if (!$controller_instance || !$action_name) {
            throw new lcInvalidArgumentException('Invalid controller / action');
        }

        // mark the start of the forward
        $this->render_time = microtime(true);

        if (!$this->controller_stack) {
            throw new lcNotAvailableException('Controller stack not available');
        }

        $controller_name = $controller_instance->getControllerName();

        $this->info('Forwarding from ' .
            (($parent_controller ? $parent_controller->getControllerName() : '-root-') . '/' .
                ($parent_controller ? $parent_controller->getActionName() : '-root-')) .
            ' to: ' . $controller_name . '/' . $action_name);

        // merge action_params with dispatch_params
        $action_params = array_merge((array)$this->dispatch_params, (array)$action_params);

        // set the action type if missing
        $action_params['type'] = isset($action_params['type']) ? $action_params['type'] : self::TYPE_ACTION;

        // set params
        $this->prepareControllerInstance($controller_instance);
        $controller_instance->setActionFilterChain($this->action_filter_chain);
        $controller_instance->setParentController($parent_controller);
        $controller_instance->setDecoratorView($this->layout_view);

        // initialize the controller now
        $controller_instance->initialize();

        // add to controller stack
        $this->controller_stack->add($controller_instance);

        // apply action filters
        $this->applyActionFilters($controller_instance, $controller_name, $action_name, $action_params);

        // notify about this forward
        // LC 1.4 Compatibility - this notification should be a notification - not a filter
        // but to keep compatibility with previous version - we leave it like this for the moment
        $notification_params = array(
            'controller_name' => $controller_name,
            'action_name' => $action_name,
            'params' => $action_params,
        );
        $this->event_dispatcher->filter(new lcEvent('controller.change_action', $this, $notification_params), $notification_params);
        unset($notification_params);

        // render the action
        $rendered_view_contents = $this->renderControllerAction($controller_instance, $action_name, $action_params);

        // decorate content with layout view
        $content_type = $rendered_view_contents['content_type'];
        $content = isset($rendered_view_contents['content']) ? $rendered_view_contents['content'] : null;

        try {
            $content = $controller_instance->renderLayoutView($content, $content_type);
        } catch (Exception $e) {
            throw new lcRenderException('Could not render layout view: ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // save the total running time
        $this->render_time = (microtime(true) - $this->render_time);

        // let the inherited class handle the rest
        $this->outputViewContents($controller_instance, $content, $content_type);
    }

    protected function executeControllerFilterChain($controller_name, $controller_filename, $controller_context_type, $controller_context_name, $controller_parent_plugin_name,
                                                    $action_name, array $action_params = null)
    {
        $filter_results = $this->action_filter_chain->execute($this, $controller_name, $action_name, $action_params, array(
            'controller_context_name' => $controller_context_name,
            'controller_context_type' => $controller_context_type,
            'controller_filename' => $controller_filename,
            'controller_parent_plugin' => $controller_parent_plugin_name
        ));

        return $filter_results;
    }

    public function shouldApplyActionFilters($action_name, $action_type)
    {
        $action_filters = $this->action_filters;

        if (!$action_filters || !isset($action_filters[$action_name])) {
            return true;
        }

        $ac = $action_filters[$action_name];

        if (!isset($ac['type']) || $ac['type'] == $action_type) {
            $should_apply_filters = (!isset($ac['skip_filters']) || !$ac['skip_filters']);
            return $should_apply_filters;
        }

        return true;
    }

    protected function applyActionFilters(lcController $controller_instance, $controller_name, $action_name, array $action_params = null)
    {
        if (!$this->action_filter_chain) {
            return false;
        }

        // check action_filters to see if the controller requests different credentials
        // and no filter processing
        if (!$this->shouldApplyActionFilters($action_name, (isset($action_params['type']) ? $action_params['type'] : null))) {
            return false;
        }

        // allow event filterers to disable action filters
        $event = $this->event_dispatcher->filter(new lcEvent('controller.should_apply_filters', $this, array(
            'controller_instance' => $controller_instance,
            'controller_name' => $controller_name,
            'action_name' => $action_name,
            'action_params' => $action_params
        )), true);

        if ($event->isProcessed()) {
            if (!$event->getReturnValue()) {
                $this->info('Applying controller filters was skipped due to an event request');
                return false;
            }
        }

        try {
            $filter_results = $this->executeControllerFilterChain(
                $controller_name,
                $controller_instance->getControllerFilename(),
                $controller_instance->getContextType(),
                $controller_instance->getContextName(),
                ($controller_instance->getParentPlugin() ? $controller_instance->getParentPlugin()->getPluginName() : null),
                $action_name,
                $action_params);

            if ($filter_results) {
                assert(isset($filter_results['filter']));
                assert(isset($filter_results['result']));

                $filter = $filter_results['filter'];
                $result = $filter_results['result'];

                $allow_forward = isset($result['allow_forward']) ? (bool)$result['allow_forward'] : true;

                if (!$allow_forward) {
                    $deny_reason = isset($result['filter_reason']) ? $result['filter_reason'] : null;

                    $credentials_module = isset($result['controller_name']) && $result['controller_name'] ? $result['controller_name'] :
                        $this->configuration['security.credentials_module'];

                    $credentials_action = isset($result['action_name']) && $result['action_name'] ? $result['action_name'] :
                        $this->configuration['security.credentials_action'];

                    $this->info('Controller action denied by filter (Filtering object: ' . $filter . '): Previous: ' .
                        $controller_name . '/' . $action_name . ' => New: ' .
                        ($credentials_module . '/' . $credentials_action) .
                        ' (Type: ' . (isset($action_params['type']) ? $action_params['type'] : null) . '), Reason: ' . ($deny_reason ? $deny_reason : ' - none given - '));


                    if ($credentials_module && $credentials_action) {
                        return $this->forward(
                            $credentials_action,
                            $credentials_module,
                            $action_params
                        );
                    } else {
                        throw new lcAuthException('Access Denied (' . $controller_name . '/' . $action_name . ')');
                    }
                }
            }

            unset($filter_results);

            return false;
        } catch (Exception $ee) {
            throw new lcFilterException('Could not apply view filters: ' .
                $ee->getMessage(),
                $ee->getCode(),
                $ee);
        }
    }

    public function renderControllerAction(lcController $controller, $action_name, array $action_params = null)
    {
        if (!$controller || !$action_name) {
            throw new lcInvalidArgumentException('Invalid controller / action');
        }

        //$this->info('Rendering action (' . $controller->getControllerName() . '/' . $action_name . ': ' . "\n\n" . print_r($action_params, true) . "\n\n");

        // set params
        $controller->setControllerStack($this->controller_stack);
        $controller->setRootController($this->root_controller);
        $controller->setDispatchParams($this->dispatch_params);

        // execute the request
        $action_result = null;
        $action_params = $action_params ? $action_params : array();

        $action_result = $controller->execute($action_name, $action_params);

        // set back to controller
        $controller->action_result = $action_result;

        // view rendering
        $view = $controller->getView();
        $rendered_view_contents = null;

        if (!$view || (int)$action_result == (int)lcBaseController::RENDER_NONE) {
            // if user specified render none - don't render anything!
            $controller->unsetView();
        } elseif ($view) {
            // set the result
            $view->setActionResult($action_result);

            try {
                $rendered_view_contents = $controller->renderView();
            } catch (Exception $e) {
                throw new lcRenderException('Could not render controller response: ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }

            // notify about this render
            $notification_params = array(
                'controller_name' => $controller->getControllerName(),
                'action_name' => $action_name,
                'params' => $action_params,
            );
            $this->event_dispatcher->filter(new lcEvent('controller.did_render_action', $this, $notification_params), $notification_params);
            unset($notification_params);

            // shutdown the view after rendering to preserve memory
            $view->shutdown();
            $controller->view = null;
        }

        return $rendered_view_contents;
    }

    protected function renderControllerView(lcBaseController $controller, lcView $view)
    {
        if (!$controller || !$view) {
            return null;
        }

        $content_type = null;
        $output = null;

        $controller_name = $controller->getControllerName();
        $action_name = $controller->getActionName();

        try {
            // set the filtering chain
            $view->setViewFilterChain($this->view_filter_chain);

            // set the decorator
            if ($controller instanceof iViewDecorator && $view instanceof iDecoratingView) {
                $view->setViewDecorator($controller);
            }

            // get the view's output
            $this->event_dispatcher->notify(
                new lcEvent('view.will_render', $this, array(
                    'view' => $view,
                    'controller_name' => $controller_name,
                    'action_name' => $action_name,
                    'context_name' => $controller->getContextName(),
                    'context_type' => $controller->getContextType(),
                    'translation_context_name' => $controller->getTranslationContextName(),
                    'translation_context_type' => $controller->getTranslationContextType()
                )));

            // render the view
            $output = $view->render();

            // send view render event
            $event = $this->event_dispatcher->filter(
                new lcEvent('view.render', $this, array(
                    'view' => $view,
                    'controller_name' => $controller_name,
                    'action_name' => $action_name,
                    'context_type' => $controller->getContextType(),
                    'context_name' => $controller->getContextName(),
                    'translation_context_name' => $controller->getTranslationContextName(),
                    'translation_context_type' => $controller->getTranslationContextType()
                )), $output);

            if ($event->isProcessed()) {
                $output = $event->getReturnValue();
            }

            $content_type = $view->getContentType();
        } catch (Exception $e) {
            throw new lcViewRenderException('Could not render view: ' . $e->getMessage(),
                $e->getCode(),
                $e);
        }

        $ret = array(
            'content_type' => $content_type,
            'content' => $output
        );

        return $ret;
    }

    protected function renderLayoutView($layout_content, $layout_content_type = null)
    {
        $layout_view = $this->getDecoratorView();

        if (!$layout_view) {
            return $layout_content;
        }

        // notify / filter
        $event = $this->event_dispatcher->filter(new lcEvent('controller.render_layout', $this), array(
            'use_layout' => true,
            'content' => $layout_content,
            'content_type' => $layout_content_type
        ));

        if ($event->isProcessed()) {
            $r = $event->getReturnValue();

            $use_layout = isset($r['use_layout']) ? (bool)$r['use_layout'] : true;
            $layout_content = isset($r['content']) ? $r['content'] : null;
            $layout_content_type = isset($r['content_type']) ? $r['content_type'] : null;

            if (!$use_layout) {
                // layout decoration not allowed
                return $layout_content;
            }
        }

        $layout_view->setDecorateContent($layout_content, $layout_content_type);
        $render_result = $this->renderControllerView($this, $layout_view);

        // unset / shutdown the view after we are done with it to preserve memory
        $layout_view->shutdown();
        $this->layout_view = null;

        if (!$render_result) {
            return null;
        }

        // notify / filter
        $event = $this->event_dispatcher->filter(new lcEvent('controller.did_render_layout', $this), array(
            'content' => $render_result['content'],
            'content_type' => $layout_content_type
        ));

        if ($event->isProcessed()) {
            $r = $event->getReturnValue();

            $layout_content = isset($r['content']) ? $r['content'] : null;
            $layout_content_type = isset($r['content_type']) ? $r['content_type'] : null;

            if (!$layout_content) {
                return $layout_content;
            }

            $render_result['content'] = $layout_content;
        }

        $content = $render_result['content'];

        return $content;
    }

    protected function renderFragment($type, $url)
    {
        $fragment_content = null;

        if ($type == 'file' || $type == 'url') {
            $fragment_content = file_get_contents($url);
        } elseif ($type == 'php') {
            ob_start();
            ob_implicit_flush(0);

            $saved_exception = null;

            try {
                if (!include($url)) {
                    throw new lcIOException('Cannot include file');
                }
            } catch (Exception $ee) {
                // do not throw the exception until ob clean passes
                $saved_exception = $ee;
            }

            $fragment_content = ob_get_clean();

            if ($saved_exception) {
                throw $saved_exception;
            }

            unset($saved_exception);
        }

        return $fragment_content;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getPartialViewByEvent(lcEvent $event)
    {
        $params = $event->getParams();

        $partial_url = isset($params['partial_url']) ? $params['partial_url'] : null;

        if (!$partial_url) {
            assert(false);
            return null;
        }

        $partial_view = $this->getPartialViewBasedOnParams($partial_url);

        return $partial_view;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getPartialViewBasedOnParams($params /* dynamic - string or array */)
    {
        if (!$params) {
            return null;
        }

        // two formats
        // - url
        // - array(module, action, params)
        if (is_string($params)) {
            // need to find the route first
            $router = $this->routing;

            assert(isset($router));

            $options = array('url' => $params);
            $params = $router->getParamsByCriteria($options);

            if (!$params) {
                return null;
            }

            unset($router, $options);
        } elseif (!is_array($params)) {
            return null;
        }

        assert(isset($params['action']) && isset($params['module']));

        $action_name = $params['action'];
        $module_name = $params['module'];

        $action_params_detected = (isset($params['params']) && is_array($params['params'])) ? $params['params'] : $params;

        return $this->getPartialView($action_name, $module_name, $action_params_detected);
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getPartialView($action_name, $module, array $params = null)
    {
        return $this->getPartial($action_name, $module, $params);
    }

    protected function getControllerInstance($controller_name, $context_type = null, $context_name = null)
    {
        if (!$this->root_controller) {
            throw new lcNotAvailableException('Root controller not available');
        }

        return $this->root_controller->getControllerInstance($controller_name, $context_type, $context_name);
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */
    public function getPartial($action_name, $module, array $params = null)
    {
        $params = array(
            'request' => array_merge(
                array(
                    'module' => $module,
                    'action' => $action_name,
                    'type' => lcController::TYPE_PARTIAL,
                ),
                (array)$params
            ),
            'type' => lcController::TYPE_PARTIAL
        );

        $content = null;

        try {
            // get an instance of the controller first
            $controller_instance = $this->getControllerInstance($module);

            if (!$controller_instance) {
                throw new lcControllerNotFoundException('Controller \'' . $module . ' / ' . $action_name . '\' not found');
            }

            $rendered_contents = null;

            $controller_instance->initialize();

            $this->prepareControllerInstance($controller_instance);

            try {
                $rendered_contents = $this->renderControllerAction($controller_instance, $action_name, $params);
            } catch (Exception $e) {
                // shutdown the controller after usage
                $controller_instance->shutdown();

                throw $e;
            }

            if (!$rendered_contents) {
                return null;
            }

            $content = $rendered_contents['content'];
        } catch (Exception $e) {
            if (DO_DEBUG) {
                $content =
                    '<div style="color:white;background-color:pink;border:1px solid gray;padding:2px;font-size:10px">Decorator error: ' .
                    $e->getMessage() . "<br />\n<br />\n" . nl2br(htmlspecialchars($e->getTraceAsString())) . '</div>';
            }

            // silence if not debugging
        }

        return $content;
    }

    public function getRenderTime()
    {
        return $this->render_time;
    }

    public function setActionName($action_name)
    {
        $this->action_name = $action_name;
    }

    public function getActionName()
    {
        return $this->action_name;
    }

    public function setActionParams(array $params = null)
    {
        $this->action_params = $params;
    }

    public function getActionParams()
    {
        return $this->action_params;
    }

    public function setActionType($action_type = null)
    {
        $this->action_type = $action_type;
    }

    public function getActionType()
    {
        return $this->action_type;
    }

    public function setParentController(lcController & $parent_controller = null)
    {
        $this->parent_controller = $parent_controller;
    }

    public function getParentController()
    {
        return $this->parent_controller;
    }

    public function isFrontController()
    {
        return $this->isRootController();
    }

    public function isTopController()
    {
        $res = ($this->getTopController() === $this) ? false : true;
        return $res;
    }

    public function setRootController(iFrontController & $controller = null)
    {
        $this->root_controller = $controller;
    }

    public function getRootController()
    {
        return $this->root_controller;
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
}

?>