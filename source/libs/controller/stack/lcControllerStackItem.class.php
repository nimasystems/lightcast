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

class lcControllerStackItem extends lcSysObj
{
    /** @var lcController */
    private $controller_instance;

    public function shutdown()
    {
        if ($this->controller_instance) {
            $this->controller_instance->shutdown();
            $this->controller_instance = null;
        }

        parent::shutdown();
    }

    public function getControllerInstance()
    {
        return $this->controller_instance;
    }

    public function setControllerInstance(lcController $controller_instance)
    {
        $this->controller_instance = $controller_instance;
    }
}
