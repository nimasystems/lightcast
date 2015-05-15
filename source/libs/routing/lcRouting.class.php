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
 * @changed $Id: lcRouting.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcRouting extends lcSysObj implements iProvidesCapabilities, iDebuggable
{
    /**
     * @var lcRequest
     */
    protected $request;

    /**
     * @var lcApp
     */
    protected $context;

    protected $default_module;
    protected $default_action;

    abstract public function getParams();

    abstract public function getParamsByCriteria($criteria);

    public function initialize()
    {
        parent::initialize();

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();
        $this->context = $this->request->getRequestContext();

        assert(isset($this->context) && is_array($this->context));

        // config
        $this->default_module = (string)$this->configuration['routing.default_module'];
        $this->default_action = (string)$this->configuration['routing.default_action'];

        $this->context['default_module'] = $this->default_module;
        $this->context['default_action'] = $this->default_action;
    }

    public function shutdown()
    {
        $this->context =
        $this->request =
            null;

        parent::shutdown();
    }

    public function getCapabilities()
    {
        return array(
            'routing'
        );
    }

    public function getDebugInfo()
    {
        $debug = array(
            'default_module' => $this->default_module,
            'default_action' => $this->default_action
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getContext()
    {
        return $this->context;
    }
}

?>