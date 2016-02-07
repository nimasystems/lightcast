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

class lcPHPRouting extends lcRouting
{
    /** @var lcWebRequest */
    protected $request;

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
        $res = $this->parse($this->context['request_uri']);
        $result = null;

        if ($res && isset($res['module']) && isset($res['action'])) {
            $result = array('params' => $res);
        }

        $this->event_dispatcher->notify(new lcEvent('router.detect_parameters', $this, $result));
    }

    public function parse($url)
    {
        // get the prefixes of URL matching vars
        $this->context['application_prefix'] = $this->configuration['routing.application_prefix'] ?
            $this->configuration['routing.application_prefix'] : 'application';

        $this->context['module_prefix'] = $this->configuration['routing.module_prefix'] ?
            $this->configuration['routing.module_prefix'] : 'module';

        $this->context['action_prefix'] = $this->configuration['routing.action_prefix'] ?
            $this->configuration['routing.action_prefix'] : 'action';

        $params = array();

        // not really sure about this - but we know the application name for sure (as it was booted with it)
        $params['application'] = $this->configuration->getApplicationName();

        $get_params = $this->context['get_params']->getArrayCopy();

        // set all GET params into params
        if ($get_params) {
            foreach ($get_params as $param) {
                $value = $param->getValue();

                if (!is_string($value)) {
                    continue;
                }

                $params[$param->getName()] = $value;

                unset($param, $value);
            }

            unset($get_params);
        }

        // find if we have the rest in the GET vars
        if ($module = $this->context['get_params']->get($this->context['module_prefix'])) {
            $params['module'] = $module;
        }

        if ($action = $this->context['get_params']->get($this->context['action_prefix'])) {
            $params['action'] = $action;
        }

        return $params;
    }

    // TODO: Finish this implementation

    public function shutdown()
    {
        $this->request = null;

        parent::shutdown();
    }

    public function getParams()
    {
        return false;
    }

    public function getParamsByCriteria($criteria)
    {
        return false;
    }

    public function generate($params = array(), $absolute = false, $name = null)
    {
        !isset($params['application']) ?
            $params['application'] = $this->getDefaultParams()->get('application') :
            null;

        !isset($params['controller']) ?
            $params['controller'] = $this->getDefaultParams()->get('controller') :
            null;

        !isset($params['action']) ?
            $params['action'] = $this->getDefaultParams()->get('action') :
            null;

        $params = http_build_query($params, null, '&');

        return $this->fixGeneratedUrl('/' . ($params ? '?' . $params : ''), $absolute);
    }
}
