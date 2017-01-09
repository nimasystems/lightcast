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

class lcFrontWebServiceController extends lcFrontWebController
{
    const XLC_APILEVEL_HEADER_NAME = 'X-LC-Api-Level';
    const DEFAULT_HTTP_ERROR_CODE = '500';

    protected $use_actual_get_params;

    /** @var lcWebResponse */
    protected $response;

    public function initialize()
    {
        parent::initialize();

        $this->use_actual_get_params = isset($this->configuration['settings.use_actual_get_params']) ?
            (bool)$this->configuration['settings.use_actual_get_params'] : true;

        $this->event_dispatcher->connect('app.exception', $this, 'onAppException');
    }

    public function onAppException(lcEvent $event)
    {
        // handle exceptions in a custom way
        $exception = $event->params['exception'] ?: null;

        if ($exception) {
            $this->sendErrorResponseFromException($exception);
        }
    }

    public function sendErrorResponseFromException(Exception $e, $custom_domain = null, $custom_error_code = null,
                                                   $custom_message = null, $custom_http_error_code = null)
    {
        $custom_domain = isset($custom_domain) ? (string)$custom_domain : null;
        $custom_error_code = isset($custom_error_code) ? (string)$custom_error_code : null;
        $custom_message = isset($custom_message) ? (string)$custom_message : null;

        $response = $this->response;

        $response->clear();

        $err = (string)$e;

        // handle web service error here
        $this->err('WS Dispatching error: ' . $err);

        $error_code = $e->getCode() ? $e->getCode() : 1;

        $response->header("API-Error", $error_code);

        // timezone
        $send_server_timezone = isset($this->configuration['settings.send_server_timezone']) ?
            (bool)$this->configuration['settings.send_server_timezone'] : true;

        if ($send_server_timezone) {
            $response->header(lcWebServiceController::TIMEZONE_RESPONSE_HEADER, date_default_timezone_get());
        }

        // http errors
        $send_http_error_code = (bool)$this->configuration['settings.send_http_error_code'];

        if ($send_http_error_code) {
            $http_error_code = $custom_http_error_code ? $custom_http_error_code : self::DEFAULT_HTTP_ERROR_CODE;

            if ($e instanceof iHTTPException) {
                $http_error_code = $e->getStatusCode();
            }

            if ($http_error_code) {
                $response->setStatusCode($http_error_code);
            }
        }

        $exception_domain = ($e instanceof iDomainException) ? $e->getDomain() : lcException::DEFAULT_DOMAIN;
        $extra_data = ($e instanceof lcException) ? $e->getExtraData() : null;

        $validation_failures = ($e instanceof lcValidationException) ? $e->getValidationFailures() : null;

        // TODO: Hide system related messages
        $internal_message = $e->getMessage();

        $err_ar = array('domain' => $exception_domain, 'code' => $error_code, 'message' => $internal_message);

        if ($extra_data) {
            if (is_array($extra_data)) {
                $err_ar = array_merge($err_ar, $extra_data);
            } else {
                $err_ar['extra_data'] = $extra_data;
            }
        }

        unset($extra_data);

        if (DO_DEBUG) {
            $err_ar['exception'] = get_class($e);
            $err_ar['trace'] = $e->getTraceAsString();

            if ($e instanceof lcException && $e->getCause()) {
                $err_ar['previous_exception'] = get_class($e->getCause());
            }
        }

        // customizations
        if ($custom_domain) {
            $err_ar['domain'] = $custom_domain;
        }

        if ($custom_error_code) {
            $err_ar['code'] = $custom_error_code;
        }

        if ($custom_message) {
            $err_ar['message'] = $custom_message;
        }

        if ($validation_failures) {
            $fails = array();

            foreach ($validation_failures as $failure) {
                $fails[] = array_filter(array(
                    'name' => $failure->getName(),
                    'message' => $failure->getMessage(),
                    'extra_data' => $failure->getExtraData()
                ));
            }

            $err_ar['validation_failures'] = $fails;
            unset($fails);
        }

        // send it
        $response_result = array('error' => $err_ar);

        $response_content_type = 'application/json; charset=' . $response->getServerCharset();

        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $output = @json_encode($response_result, JSON_UNESCAPED_UNICODE);
        } else {
            $output = @json_encode($response_result);
        }

        // make the output pretty while debugging
        if (DO_DEBUG) {
            $output = lcVars::indentJson($output);
        }

        // set the API level
        $response->header(self::XLC_APILEVEL_HEADER_NAME, $this->getConfiguration()->getApiLevel());

        // send the response
        $response->header('Content-Type', $response_content_type);
        $response->setContent($output);
        $response->sendResponse();

        exit(0);
    }

    public function getControllerInstance($controller_name, $context_type = null, $context_name = null)
    {
        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        $controller_instance = $this->system_component_factory->getControllerWebServiceInstance($controller_name, $context_type, $context_name);

        if (!$controller_instance) {
            return null;
        }

        // assign system objects
        $this->prepareControllerInstance($controller_instance);

        // resolve dependancies
        try {
            $controller_instance->loadDependancies();
        } catch (Exception $e) {
            throw new lcRequirementException('Web Service controller dependancies could not be loaded (' . $controller_name . '): ' .
                $e->getMessage(),
                $e->getCode(),
                $e);
        }

        // do not initialize the object yet! leave it to the caller

        return $controller_instance;
    }

    protected function beforeDispatch()
    {
        // root view controller initialization point

        // set the API level
        $this->response->header(self::XLC_APILEVEL_HEADER_NAME, $this->configuration->getApiLevel());
    }

    protected function prepareDispatchParams(lcRequest $request)
    {
        $params = array();

        // TODO: Change this in 1.5 - remove it
        // as it harcodes the usage of lcPHPRouting only!
        if ($this->getRouter() instanceof lcPHPRouting) {
            $params = (array)$this->extractRequestParams($request);
        } else {
            /** @var lcNameValuePair[] $params_tmp */
            $params_tmp = $request->getParams()->getArrayCopy();

            if ($params_tmp) {
                foreach ($params_tmp as $param) {
                    $params[$param->getName()] = $param->getValue();

                    unset($param);
                }
            }

            unset($params_tmp);
        }

        $parent_params = parent::prepareDispatchParams($request);

        $params = array_merge((array)$params, (array)$parent_params);

        return $params;
    }

    protected function handleControllerNotReachable($controller_name, $action_name = null, array $action_params = null)
    {
        // final stop - we need to handle it as json
        throw new lcControllerForwardException('Could not forward to controller action');
    }

    private function extractRequestParams(lcRequest $request)
    {
        /** @var lcWebRequest $request */

        /** @var lcNameValuePair[] $params */
        $params = $request->isPost() ? $request->getPostParams()->getArrayCopy() :
            $request->getGetParams()->getArrayCopy();

        $extraction = array();
        $arr2 = array();

        if (!empty($params)) {
            foreach ($params as $param) {
                if (substr($param->getName(), 0, 5) == 'param') {
                    $extraction[substr($param->getName(), 5)] = $param->getValue();
                }

                $arr2[$param->getName()] = $param->getValue();
            }
        }

        ksort($extraction);

        $res = $this->use_actual_get_params ? $arr2 : array_values($extraction);

        return $res;
    }
}
