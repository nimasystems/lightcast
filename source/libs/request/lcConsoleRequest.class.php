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
 * @changed $Id: lcConsoleRequest.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcConsoleRequest extends lcRequest implements iDebuggable
{
    const SILENT_PARAM_NAME = 'silent';

    /**
     * @var array
     */
    protected $argv;

    /**
     * @var array
     */
    protected $argc;

    /*
     * Initialization of the Request
    */
    public function initializeBeforeApp(lcEventDispatcher $event_dispatcher, lcConfiguration $configuration)
    {
        parent::initializeBeforeApp($event_dispatcher, $configuration);

        $this->argv = $this->env('argv');
        $this->argc = $this->env('argc');
        $this->params = new lcArrayCollection($this->argv);

        // when router loads the detected params from request
        // it will notify us with this event
        // and request will set its local params to the ones detected by router
        $this->event_dispatcher->connect('router.detect_parameters', $this, 'onRouterDetectParameters');
    }

    public function getDebugInfo()
    {
        $debug = array(
            'argv' => $this->argv,
            'argc' => $this->argc,
            'params' => $this->params
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function onRouterDetectParameters(lcEvent $event)
    {
        $request_params = $event->getParams();

        $processed_event = $this->event_dispatcher->filter(
            new lcEvent('request.filter_parameters', $this,
                array('context' => $this->getRequestContext(), 'parameters' => $request_params)
            ), array());

        if ($processed_event->isProcessed()) {
            $request_params = (array)$processed_event->getReturnValue();
        }

        assert($request_params && is_array($request_params) && isset($request_params['params']));

        $pparams = isset($request_params['params']) ? $request_params['params'] : null;

        $this->params = new lcArrayCollection($pparams);

        $this->event_dispatcher->notify(new lcEvent('request.load_parameters', $this, $pparams));

        // process the silent output parameter
        $this->is_silent = isset($pparams[self::SILENT_PARAM_NAME]) ? (bool)$pparams[self::SILENT_PARAM_NAME] : false;
    }

    public function getRequestContext()
    {
        return array(
            'argc' => $this->argc,
            'argv' => $this->argv
        );
    }

    public function getArgv()
    {
        return $this->argv;
    }

    public function getArgc()
    {
        return $this->argc;
    }
}

?>