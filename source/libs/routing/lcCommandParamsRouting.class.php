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
 * @changed $Id: lcCommandParamsRouting.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
/*
 * Console Argument Routing
* cmd.bat [application] [controller] [action] parameter1 parameter2 parameterN
*
* Default application: lc
* Default controller: default
* Default action: help
*/

class lcCommandParamsRouting extends lcRouting implements iDebuggable
{
    /** @var lcConsoleRequest */
    protected $request;

    protected $detected_params;

    protected $context;

    public function initialize()
    {
        parent::initialize();

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

        $this->context = $this->request->getRequestContext();
        $this->context['default_module'] = $this->default_module;
        $this->context['default_action'] = $this->default_action;

        // allow others to be notified when base routes have been loaded
        $this->event_dispatcher->notify(new lcEvent('router.load_configuration', $this, array(
            'context' => $this->context
        )));

        // try to detect the parameters from request
        $this->detectParameters();
    }

    private function detectParameters()
    {
        $context = $this->context;

        assert(isset($context) && is_array($context) && $context);

        if (!isset($context['argv']) || !isset($context['argc']) || !is_array($context['argv']) || !$context['argv'] || !$context['argc']) {
            throw new lcInvalidArgumentException('Invalid request context');
        }

        $argv = $context['argv'];
        $argc = (int)$context['argc'];

        $compiled_params = array();

        // parse the params
        $value_next = false;

        for ($i = 1; $i <= $argc - 1; $i++) {
            $param = $argv[$i];

            if ($i == 1 && !strstr($param, '--')) {
                // module
                $compiled_params['module'] = (string)$param;
                continue;
            } elseif ($i == 2 && !strstr($param, '--')) {
                // action
                $compiled_params['action'] = (string)$param;
                continue;
            }

            // parse the rest of the params
            $ex = array_filter(explode('=', $param));

            if ($ex && is_array($ex)) {
                if (count($ex) == 1 && isset($argv[$i + 1]) && substr($argv[$i + 1], 0, 2) != '--') {
                    $value_next = true;
                    continue;
                }

                $pkey = null;
                $pval = null;

                if (count($ex) == 1 && $value_next) {
                    // when expecting a value now from a previous iteration
                    $tmpx = array_filter(explode('=', $argv[$i - 1]));
                    $pkey = strtolower((string)$tmpx[0]);
                    $pval = isset($ex[0]) ? (string)$ex[0] : true;
                    unset($tmpx);
                    $value_next = false;
                } else {
                    $pkey = strtolower((string)$ex[0]);
                    $pval = isset($ex[1]) ? (string)$ex[1] : true;
                }

                assert(!is_null($pkey));

                // strip the key prefix
                if (substr($pkey, 0, 2) == '--') {
                    assert(!$value_next);
                    $pkey = substr($pkey, 2, strlen($pkey));
                }

                if ($pkey && $pkey != 'module' && $pkey != 'action') {
                    $compiled_params[$pkey] = $pval;
                }

                unset($pkey, $pval);
            }

            unset($ex);
            unset($param);
        }

        // add defaults if missing
        $compiled_params['module'] = isset($compiled_params['module']) ? $compiled_params['module'] : $this->default_module;
        $compiled_params['action'] = isset($compiled_params['action']) ? $compiled_params['action'] : $this->default_action;

        $this->detected_params = $compiled_params;

        $this->event_dispatcher->notify(new lcEvent('router.detect_parameters', $this, array(
            'params' => $compiled_params,
            'default_module' => $this->default_module,
            'default_action' => $this->default_action
        )));
    }

    public function shutdown()
    {
        $this->request = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = array(
            'detected_params' => $this->detected_params
        );

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function onFilterParameters(lcEvent $event, $params)
    {
        $this->context = $event->getParams();

        if (false === $params_ret = $this->parse($event['argv'])) {
            return $params;
        }

        $pars = array_merge((array)$params, (array)$params_ret);

        return $pars;
    }

    // TODO: Finish this implementation

    public function getParams()
    {
        return $this->detected_params;
    }

    public function getParamsByCriteria($criteria)
    {
        return false;
    }
}
