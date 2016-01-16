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
 * @changed $Id: lcFrontWebController.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcFrontWebController extends lcFrontController
{
    /** @var lcWebRequest */
    protected $request;

    public function getControllerInstance($controller_name, $context_type = null, $context_name = null)
    {
        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        $controller_instance = $this->system_component_factory->getControllerModuleInstance($controller_name, $context_type, $context_name);

        if (!$controller_instance) {
            return null;
        }

        // assign system objects
        $this->prepareControllerInstance($controller_instance);

        if ($this->default_decorator) {
            $controller_instance->setDefaultDecorator($this->default_decorator);
        }

        // assign request-based web path
        if (!$controller_instance->getWebPath()) {
            $web_path = $this->request->getUrlPrefix() . '/' . $controller_instance->getControllerName();
            $controller_instance->setWebPath($web_path);
        }

        // resolve dependancies
        try {
            $controller_instance->loadDependancies();
        } catch (Exception $e) {
            throw new lcRequirementException('Web controller dependancies could not be loaded (' . $controller_name . '): ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // do not initialize the object yet! leave it to the caller
        return $controller_instance;
    }

    protected function beforeDispatch()
    {
        // custom code before dispatching
    }

    protected function shouldDispatch($controller_name, $action_name, array $params = null)
    {
        // handler called just before dispatching by front controller
        return true;
    }

    protected function prepareDispatchParams(lcRequest $request)
    {
        // parse the request params and merge them
        // pass to forwarded method for easier access
        $params = array();

        /** @var lcWebRequest $request */

        // parse request params
        /** @var lcNameValuePair[] $request_params */
        $request_params = $request->getParams()->getArrayCopy();

        if ($request_params) {
            foreach ($request_params as $obj) {
                $params[$obj->getName()] = $obj->getValue();
            }
        }

        // parse post params
        /** @var lcNameValuePair[] $post */
        $post = $request->getPostParams()->getArrayCopy();

        if ($post) {
            foreach ($post as $obj) {
                if (isset($params[$obj->getName()])) {
                    continue;
                }

                $params[$obj->getName()] = $obj->getValue();
            }
        }

        // parse get params
        /** @var lcNameValuePair[] $get */
        $get = $request->getGetParams()->getArrayCopy();

        if ($get) {
            foreach ($get as $obj) {
                if (isset($params[$obj->getName()])) {
                    continue;
                }

                $params[$obj->getName()] = $obj->getValue();
            }
        }

        // filter the input from request
        if ($params) {
            $this->filterForwardParams($params);
        }

        return $params;
    }

    protected function handleControllerNotReachable($controller_name, $action_name = null, array $action_params = null)
    {
        parent::handleControllerNotReachable($controller_name, $action_name, $action_params);

        /** @var lcWebResponse $response */
        $response = $this->response;

        if ((bool)$this->configuration['routing.send_http_errors']) {
            $this->info('Sending a HTTP 404 because no suitable module/action were found for the request');

            $response->sendHttpNotFound();
        }

        // final stop
        throw new lcControllerForwardException('Could not forward to controller action');
    }

    protected function handleControllerNotReachableAfter()
    {
        // don't throw here - but in handleControllerNotReachable
    }
}
