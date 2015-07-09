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
 * @changed $Id: lcFrontConsoleController.class.php 1493 2013-12-17 11:27:04Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1493 $
 */
class lcFrontConsoleController extends lcFrontController
{
    /** @var lcConsoleResponse */
    protected $response;

    public function initialize()
    {
        parent::initialize();
    }

    protected function beforeDispatch()
    {
        // custom code before dispatching
    }

    protected function prepareDispatchParams(lcRequest $request)
    {
        $params = $request->getParams();
        $tmp = array();

        if ($params) {
            $params = $params->getArrayCopy();

            foreach ($params as $param) {
                /** @var lcNameValuePair $param */
                $tmp[$param->getName()] = $param->getValue();

                unset($param);
            }

            unset($params);
        }

        return $tmp;
    }

    public function getConsoleIntro()
    {
        $ver = LIGHTCAST_VER;
        $lyear = date('Y');

        $lcintro = <<<EOD
----------------------------------------------
Lightcast $ver Console Control
----------------------------------------------
Nimasystems Ltd - All Rights Reserved, 2007 - $lyear
	
You must be licensed to use this software and
it comes with ABSOLUTELY NO WARRANTY.
Please read the README and LICENSE files.
----------------------------------------------
	
EOD;

        $intro = lcConsolePainter::formatColoredConsoleText($lcintro, 'cyan');

        $intro .= <<<EOD
	
The Lightcast command line tool (console) can be
used to launch existing project tasks.
	
Usage: console [task_name] [action_name] [OPTIONS]
Usage (debugging): console_debug [task_name] [action_name='default'] [OPTIONS]
	
- If action_name is not specified the default HELP context of the task will be shown
- OPTIONS can be specified as string parameters or --key=value parameters
	
General Options:
	
{fgcolor:green}--help{/fgcolor} - Displays help information about this tool or a task if specified
{fgcolor:green}--silent{/fgcolor} - Run a task action without providing any feedback to the terminal
	
Plugin Options:
	
{fgcolor:green}--debug{/fgcolor} - Enable debugging mode
	
{fgcolor:green}--disable-plugins{/fgcolor} - Do not initialize plugins. Useful when generating Propel models for the first time
					and there are model dependancies upon plugin initialization
	
{fgcolor:green}--disable-loaders{/fgcolor} - Do not load the loaders specified in configuration. Fallback to default loaders
	
EOD;

        return $intro;
    }

    protected function shouldDispatch($controller_name, $action_name, array $params = null)
    {
        // handler called just before dispatching by front controller
        if (isset($params['help']) || ($controller_name && !$action_name)) {
            $this->displayControllerHelp($controller_name);
            return false;
        } elseif (!$controller_name && !$action_name) {
            // show console info
            $this->response->consoleDisplay($this->getConsoleIntro(), false);
            return false;
        }

        return true;
    }

    protected function getControllerHelpInformation($controller_name)
    {
        /** @var lcTaskController $controller */
        $controller = $this->getControllerInstance($controller_name);

        if (!$controller) {
            throw new lcControllerNotFoundException('Controller \'' . $controller_name . '\' not found');
        }

        $controller->initialize();

        $help = $controller->getHelpInformation();

        $controller->shutdown();

        return $help;
    }

    protected function displayControllerHelp($controller_name)
    {
        $help_info = $this->getControllerHelpInformation($controller_name);

        $output = $help_info ?
            "\n\n" . $help_info . "\n\n" :
            'Module does not provide help information';

        $this->response->consoleDisplay($output, false);
    }

    public function getControllerInstance($controller_name, $context_type = null, $context_name = null)
    {
        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        $controller_instance = $this->system_component_factory->getControllerTaskInstance($controller_name, $context_type, $context_name);

        if (!$controller_instance) {
            return null;
        }

        // assign system objects
        $this->prepareControllerInstance($controller_instance);

        // resolve dependancies
        try {
            $controller_instance->loadDependancies();
        } catch (Exception $e) {
            throw new lcRequirementException('Console task controller dependancies could not be loaded (' . $controller_name . '): ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // do not initialize the object yet! leave it to the caller

        return $controller_instance;
    }
}