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

interface iFrontController
{
    /**
     * @param $controller_name
     * @param $action_name
     * @param $action_type
     * @param null $context_type
     * @param null $context_name
     * @return lcController
     */
    public function getControllerInstance($controller_name, $action_name = null, $action_type = null, $context_type = null, $context_name = null);

    public function filterForwardParams(array &$forward_params);

    /**
     * @return lcControllerStack
     */
    public function getControllerStack();

    public function dispatch();

    public function validateForward($action_name, $controller_name, array $custom_params = null);
}
