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
 * @changed $Id: lcWebServiceController.class.php 1559 2014-10-27 23:47:30Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1559 $
 */
abstract class lcWebServiceController extends lcWebBaseController implements iPluginContained, iDebuggable
{
    const TIMEZONE_RESPONSE_HEADER = 'X-TZ';

    protected $send_direct_response;
    protected $send_server_timezone;

    public function initialize()
    {
        parent::initialize();

        $this->send_direct_response = isset($this->configuration['settings.send_direct_response']) ? (bool)$this->configuration['settings.send_direct_response'] : true;
        $this->send_server_timezone = isset($this->configuration['settings.send_server_timezone']) ? (bool)$this->configuration['settings.send_server_timezone'] : true;
    }

    /**
     * @return lcView
     */
    public function getDefaultLayoutViewInstance()
    {
        // web services don't have layouts
        return null;
    }

    protected function outputViewContents(lcController $controller, $content = null, $content_type = null)
    {
        fnothing($controller);

        $response = $this->getResponse();

        // send the output
        if ($content_type) {
            $response->setContentType($content_type);
        }

        $response->setNoContentProcessing(true);

        if ($this->send_server_timezone) {
            $response->header(self::TIMEZONE_RESPONSE_HEADER, date_default_timezone_get());
        }

        $response->setContent($content);
        $response->sendResponse();
    }

    protected function execute($action_name, array $action_params)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;

        $this->action_name = $action_name;
        $this->action_params = $action_params;
        $this->action_type = $action_type;

        $method_params = isset($action_params['method_params']) && is_array($action_params['method_params']) ? $action_params['method_params'] : null;
        $action = $this->classMethodForAction($action_name, $action_params);
        $controller_name = $this->controller_name;

        $action_params = ($method_params ? $method_params : $action_params);

        // echo $controller_name . '/' . $action . ' (' . $this->action_type . ')<br />';

        if (DO_DEBUG) {
            $this->debug(sprintf('%-40s %s', 'Execute ' . ($this->parent_plugin ? 'p-' . $this->parent_plugin->getPluginName() . ' :: ' : null) . $controller_name . '/' . $action_name .
                '(' . $this->action_type . ')', '{' . lcArrays::arrayToString($action_params) . '}'));
        }

        $action_exists = $this->actionExists($action_name, $action_params);

        if (!$action_exists) {
            throw new lcActionNotFoundException('Controller action: \'' . $this->controller_name . ' / ' . $action_name . '\' is not valid');
        }

        // configure the view
        $this->configureControllerView();

        // run before execute
        call_user_func_array(array($this, 'beforeExecute'), $action_params);

        // call the action
        // unfortunately the way we are handling variables at the moment
        // we can't use the fast calling as args need to be expanded with their names (actions are looking for them)
        // so we fall back to the default way
        //$call_result = $this->__call($action, $params);
        $this->action_result = call_user_func_array(array($this, $action), $action_params);

        // run after execute
        call_user_func_array(array($this, 'afterExecute'), $action_params);

        // notify after the action has been executed
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('controller.executed_action', $this,
                array('controller_name' => $this->controller_name,
                    'action_name' => $this->action_name,
                    'action_type' => $this->action_type,
                    'controller' => $this,
                    'action_params' => $this->action_params,
                    'action_result' => $this->action_result,
                )
            ));
        }

        // set the results to the raw content view if such is available
        $view = $this->getView();

        if ($view && $view instanceof lcDataView) {
            $view_contents = $this->send_direct_response ? $this->action_result : array(
                'error' => 0,
                'result' => $this->action_result
            );
            $view->setContent($view_contents);
        }

        return $this->action_result;
    }

    protected function classMethodForAction($action_name, array $action_params = null)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        $method_name = $action_type . ucfirst(lcInflector::camelize($action_name));
        return $method_name;
    }

    protected function actionExists($action_name, array $action_params = null)
    {
        fnothing($action_params);

        /*
         * We need to make this call with both is_callable, method_exists
        *  as the inherited classes may contain a __call()
        *  magic method which will be raised also lcObj as the last parent
        *  in this tree - throws an exception!
        */
        $method_name = $this->classMethodForAction($action_name, $action_params);

        if (!$method_name) {
            return false;
        }

        $callable_check = is_callable(array($this, $method_name)) && method_exists($this, $method_name);

        return $callable_check;
    }

    protected function configureControllerView()
    {
        // create and set a view to the controller
        $view = $this->getDefaultViewInstance();

        if (!$view) {
            return;
        }

        $view->setOptions(array(
            'action_name' => $this->getActionName(),
            'action_params' => $this->getActionParams(),
        ));
        $view->setConfiguration($this->getConfiguration());
        $view->setEventDispatcher($this->getEventDispatcher());
        $view->setController($this);

        $view->setContentType('application/json; charset=' . $this->response->getServerCharset());

        $view->initialize();

        // set to controller
        $this->setView($view);
    }

    public function getDefaultViewInstance()
    {
        $view = new lcDataView();
        $view->setContentType($this->configuration['view.content_type']);
        return $view;
    }

    protected function forwardToAction($parent_controller, $action_name, $controller_name = null, array $custom_params = null)
    {
        // custom handling of exceptions in web service mode
        try {
            return parent::forwardToAction($parent_controller, $action_name, $controller_name, $custom_params);
        } catch (Exception $e) {
            $this->sendErrorResponseFromException($e);
        }
    }
}

?>