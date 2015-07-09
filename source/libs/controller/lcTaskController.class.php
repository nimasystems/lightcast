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
 * @changed $Id: lcTaskController.class.php 1544 2014-06-21 06:14:47Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1544 $
 */
abstract class lcTaskController extends lcController implements iDebuggable
{
    /** @var lcConsoleRequest */
    protected $request;

    /** @var lcConsoleResponse */
    protected $response;

    public function initialize()
    {
        parent::initialize();

        $this->event_dispatcher->connect('app.exception', $this, 'onAppException');
    }

    public function onAppException(lcEvent $event)
    {
        /** @var Exception $exception */
        $exception = isset($event->params['exception']) ? $event->params['exception'] : null;

        // if the exception is a lcControllerNotFoundException then just display the message
        if ($exception instanceof lcControllerNotFoundException) {
            $event->setProcessed(true);
            $this->consoleDisplay('The requested controller action could not be found', false);
            return true;
        }

        // prefetch exceptions and display just the error message if in release mode
        if (!DO_DEBUG) {
            $event->setProcessed(true);
            $this->consoleDisplay('Error: ' . $exception->getMessage(), false);
            return true;
        }

        // otherwise let the error handler handle it
        return false;
    }

    /**
     * @return lcView
     */
    public function getDefaultViewInstance()
    {
        return null;
    }

    /**
     * @return lcView
     */
    public function getDefaultLayoutViewInstance()
    {
        // console tasks don't have layouts
        return null;
    }

    protected function outputViewContents(lcController $controller, $content = null, $content_type = null)
    {
        fnothing($content_type);

        $action_result = $controller->getActionResult();
        $execute_status = 0;

        // if unsuccessfull
        if ((is_numeric($action_result) && $action_result != 0) || !$action_result) {
            $execute_status = is_numeric($execute_status) ? $execute_status : 1;
            $this->displayError('Task did not finish successfully', true, true);
        }

        $response = $this->getResponse();
        $response->setExitCode($execute_status);
        $response->setContent($content);
        $response->sendResponse();
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

    protected function classMethodForAction($action_name, array $action_params = null)
    {
        fnothing($action_name, $action_params);
        return 'executeTask';
    }

    protected function execute($action_name, array $action_params)
    {
        $this->action_name = $action_name;
        $this->action_params = $action_params;

        $action_exists = $this->actionExists($action_name, $action_params);

        if (!$action_exists) {
            throw new lcActionException('Invalid task - missing execute() method');
        }

        if (DO_DEBUG) {
            $this->debug(sprintf('%-40s %s', 'Execute ' . ($this->parent_plugin ? 'p-' . $this->parent_plugin->getPluginName() . ' :: ' : null) .
                $this->controller_name . '/' . $action_name .
                '(' . $this->action_type . ')', '{' . lcArrays::arrayToString($action_params) . '}'));
        }

        // run before execute
        call_user_func_array(array($this, 'beforeExecute'), $action_params);

        // call the action
        $action = $this->classMethodForAction($action_name, $action_params);
        $this->action_result = $this->$action($action_params);

        if (!$this->action_result) {
            throw new lcActionException('Console task did not finish successfully');
        }

        // run after execute
        call_user_func_array(array($this, 'afterExecute'), $action_params);

        return $this->action_result;
    }

    public function display($data, $prefixed = true, $return = false)
    {
        $this->consoleDisplay($data, $prefixed, $return);
    }

    public function displayError($data, $prefixed = true, $return = false)
    {
        $data = lcConsolePainter::formatColoredConsoleText($data, 'red');
        $this->consoleDisplay($data, $prefixed, $return);
    }

    public function displayWarning($data, $prefixed = true, $return = false)
    {
        $data = lcConsolePainter::formatColoredConsoleText($data, 'yellow');
        $this->consoleDisplay($data, $prefixed, $return);
    }

    public function consoleDisplay($data, $prefixed = true, $return = false)
    {
        $this->response->consoleDisplay($data, $prefixed, $return);
    }

    protected function confirm($message, $accept_string = 'y')
    {
        if (!function_exists('readline')) {
            return true;
        }

        $ret = readline($message . ' ');
        $input = strtolower(trim($ret));

        if ($input == strtolower($accept_string)) {
            return $input;
        }

        return false;
    }

    public function getHelpInformation()
    {
        $this->consoleDisplay('No help information provided by: \'' . $this->getControllerName() . '\'');
        return false;
    }
}
