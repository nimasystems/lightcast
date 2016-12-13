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

abstract class lcBaseActionForm extends lcSysObj
{
    /** @var lcWebController */
    protected $controller;

    // TODO: these should NOT be abstract (getters)
    abstract public function getName();

    abstract public function getFormId();

    abstract public function getFormAction();

    abstract public function getFormClass();

    abstract public function getFormTabIndex();

    abstract public function validate();

    abstract public function render();

    /**
     * @return iActionFormExecuteSubmitResponse
     */
    abstract public function execute();

    abstract public function bindDefaultData(array $data);

    abstract public function bindData(array $data);

    /**
     * @param $field_name
     * @param $container_name
     * @return lcBaseActionFormWidget|null
     */
    abstract public function getWidget($field_name, $container_name);

    /**
     * @return lcBaseActionFormValidationFailure[]
     */
    abstract public function getValidationFailures();

    public function initialize()
    {
        parent::initialize();

        $this->controller->getEventDispatcher()->notify(new lcEvent('action_form.iniitialize', $this));
    }

    public function setController(lcBaseController $controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return lcWebController
     */
    public function getController()
    {
        return $this->controller;
    }

    public function initializeFormWidgets()
    {
        $this->controller->getEventDispatcher()->notify(new lcEvent('action_form.iniitialize_form_widgets', $this));

        return $this;
    }

    protected function getPlugin($plugin_name)
    {
        return $this->plugin_manager->getPlugin($plugin_name);
    }
}
