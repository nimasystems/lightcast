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

abstract class lcRouting extends lcResidentObj implements iProvidesCapabilities, iDebuggable
{
    /** @var lcRequest */
    protected $request;

    protected $default_module;
    protected $default_action;

    abstract public function getParams();

    abstract public function getParamsByCriteria($criteria);

    public function initialize()
    {
        parent::initialize();

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

        // config
        $this->default_module = (string)$this->configuration['routing.default_module'];
        $this->default_action = (string)$this->configuration['routing.default_action'];

    }

    public function shutdown()
    {
        $this->request =
            null;

        parent::shutdown();
    }

    public function getCapabilities()
    {
        return [
            'routing'
        ];
    }

    public function getDebugInfo()
    {
        $debug = [
            'default_module' => $this->default_module,
            'default_action' => $this->default_action
        ];

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }
}
