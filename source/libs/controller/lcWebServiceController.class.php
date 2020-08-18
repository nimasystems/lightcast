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

abstract class lcWebServiceController extends lcWebBaseController implements iPluginContained, iDebuggable
{
    const HTTP_CODE_OK = '200';
    const HTTP_CODE_NOT_FOUND = '404';
    const HTTP_CODE_FORBIDDEN = '403';
    const HTTP_CODE_BAD_REQUEST = '400';
    const HTTP_CODE_SYSTEM_ERROR = '500';
    const HTTP_CODE_APP_ERROR = '422';

    const TIMEZONE_RESPONSE_HEADER = 'X-TZ';

    protected $send_direct_response;
    protected $send_server_timezone;

    /** @var lcApiWebRequest */
    protected $request;

    /**
     * @var int
     */
    protected $member_id;

    public function initialize()
    {
        parent::initialize();

        $this->send_direct_response = isset($this->configuration['settings.send_direct_response']) ? (bool)$this->configuration['settings.send_direct_response'] : true;
        $this->send_server_timezone = isset($this->configuration['settings.send_server_timezone']) ? (bool)$this->configuration['settings.send_server_timezone'] : true;

        $this->member_id = $this->user ? $this->user->getUserId() : null;
    }

    /**
     * @return lcView
     */
    public function getDefaultLayoutViewInstance()
    {
        // web services don't have layouts
        return null;
    }

    protected function validateSet(array $data, array $input, $name = null)
    {
        $validation_errors = [];
        $invalid_fields = [];
        $filtered_data = [];

        foreach ($data as $field => $validation_details) {
            $filtered_data[$field] = isset($input[$field]) ? $input[$field] : null;

            $options = isset($validation_details['options']) ? $validation_details['options'] : [];
            $is_split = isset($validation_details['options']);

            $required = isset($options['required']) && $options['required'];

            if ($is_split) {
                $filters = isset($validation_details['filters']) ? $validation_details['filters'] : [];
                $validators = isset($validation_details['validators']) ? $validation_details['validators'] : [];
            } else {
                $validators = $validation_details;
                $required = $validation_details && !(isset($validation_details[0]['optional']) && $validation_details[0]['optional']);
                $filters = [];
            }

            $should_test_validators = $required || isset($input[$field]);

            // trim string values
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }

            if ($required && (!isset($input[$field]) || is_null($input[$field]) || (is_string($input[$field]) && !strlen($input[$field])))) {
                $validation_errors[] = new lcValidatorFailure($field, sprintf($this->t('Field \'%s\' is required'), $field));
                $invalid_fields[] = $field;
                $should_test_validators = false;
            }

            foreach ($filters as $filter) {
                $filtered_data[$field] = $filter($filtered_data[$field]);
                unset($filter);
            }

            if (!$filtered_data[$field]) {
                continue;
            }

            if ($should_test_validators) {
                $vdata = [];

                foreach ($validators as $validator) {
                    $vdata[] = [
                        'name' => $field,
                        'validator' => $validator['type'],
                        'value' => $filtered_data[$field],
                        'options' => isset($validator['options']) ? $validator['options'] : [],
                        'fail' => isset($validator['message']) ? $validator['message'] :
                            sprintf($this->t('Field \'%s\' is not valid'), $field),
                    ];

                    unset($validator);
                }

                $is_valid = $this->validateData($vdata, $validation_errors);

                if (!$is_valid) {
                    $invalid_fields[] = $field;
                }
            }

            unset($field, $validation_details);
        }

        if (count($validation_errors)) {
            $vex = new lcValidationException($this->t('Invalid request data'));
            $vex->setValidationFailures($validation_errors);
            throw $vex;
        }

        return $filtered_data;
    }

    protected function validateRequestData(array $data, lcApiWebRequest $request)
    {
        $request_data = (array)$request->getRequestData();
        return $this->validateSet($data, $request_data);
    }

    protected function renderSuccess(array $data = null)
    {
        $this->renderRaw(json_encode(['data' => $data]), 'application/json');
    }

    protected function renderAppError($message, $domain = null, $code = null)
    {
        $this->response->setStatusCode(self::HTTP_CODE_APP_ERROR);

        $this->renderRaw(json_encode(['error' => array_filter([
            'message' => $message,
            'domain' => $domain,
            'code' => $code,
        ])]), 'application/json');
    }

    protected function outputViewContents(lcController $controller, $content = null, $content_type = null)
    {
        /** @var lcWebResponse $response */
        $response = $this->getResponse();

        // send the output
        if ($content_type) {
            $response->setContentType($content_type);
        }

        $response->setNoContentProcessing(true);

        if ($this->send_server_timezone) {
            $response->header(self::TIMEZONE_RESPONSE_HEADER, date_default_timezone_get());
        }

        $response->setContent($content);
        $response->sendResponse();
    }

    protected function execute($action_name, array $action_params)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;

        $this->action_name = $action_name;
        $this->action_params = $action_params;
        $this->action_type = $action_type;

        $method_params = isset($action_params['method_params']) && is_array($action_params['method_params']) ? $action_params['method_params'] : null;
        $action = $this->classMethodForAction($action_name, $action_params);
        $controller_name = $this->controller_name;

        $action_params = ($method_params ? $method_params : $action_params);

        // echo $controller_name . '/' . $action . ' (' . $this->action_type . ')<br />';

        if (DO_DEBUG) {
            $this->debug(sprintf('%-40s %s', 'Execute ' . ($this->parent_plugin ? 'p-' . $this->parent_plugin->getPluginName() . ' :: ' : null) . $controller_name . '/' . $action_name .
                '(' . $this->action_type . ')', '{' . lcArrays::arrayToString($action_params) . '}'));
        }

        $action_exists = $this->actionExists($action_name, $action_params);

        if (!$action_exists) {
            throw new lcActionNotFoundException('Controller action: \'' . $this->controller_name . ' / ' . $action_name . '\' is not valid');
        }

        // configure the view
        $view = $this->getDefaultViewInstance();

        if ($view) {
            $this->configureControllerView($view);
        }

        // run before execute
        call_user_func_array([$this, 'beforeExecute'], $action_params);

        // call the action
        // unfortunately the way we are handling variables at the moment
        // we can't use the fast calling as args need to be expanded with their names (actions are looking for them)
        // so we fall back to the default way
        //$call_result = $this->__call($action, $params);

        $call_style = $this->configuration['controller.call_style'];
        $call_input = $call_style == lcController::CALL_STYLE_REQRESP ?
            [$this->getRequest()] : $action_params;

        $this->action_result = call_user_func_array([$this, $action], $call_input);

        // run after execute
        call_user_func_array([$this, 'afterExecute'], $action_params);

        // notify after the action has been executed
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('controller.executed_action', $this,
                ['controller_name' => $this->controller_name,
                 'action_name' => $this->action_name,
                 'action_type' => $this->action_type,
                 'controller' => $this,
                 'action_params' => $this->action_params,
                 'action_result' => $this->action_result,
                ]
            ));
        }

        // set the results to the raw content view if such is available
        $view = $this->getView();

        if ($view && $view instanceof lcDataView) {
            $view_contents = $this->send_direct_response ? $this->action_result : [
                'error' => 0,
                'result' => $this->action_result,
            ];
            $view->setContent($view_contents);
        }

        return $this->action_result;
    }

    protected function classMethodForAction($action_name, array $action_params = null)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        return $action_type . ucfirst(lcInflector::camelize($action_name));
    }

    protected function actionExists($action_name, array $action_params = null)
    {
        /*
         * We need to make this call with both is_callable, method_exists
        *  as the inherited classes may contain a __call()
        *  magic method which will be raised also lcObj as the last parent
        *  in this tree - throws an exception!
        */
        $method_name = $this->classMethodForAction($action_name, $action_params);

        if (!$method_name) {
            return false;
        }

        return is_callable([$this, $method_name]) && method_exists($this, $method_name);
    }

    protected function configureControllerView(lcView $view)
    {
        $view->setOptions([
            'action_name' => $this->getActionName(),
            'action_params' => $this->getActionParams(),
        ]);
        $view->setConfiguration($this->getConfiguration());
        $view->setEventDispatcher($this->getEventDispatcher());
        $view->setController($this);

        $view->setContentType('application/json; charset=' . $this->response->getServerCharset());

        $view->initialize();

        // set to controller
        $this->setView($view);
    }

    public function getDefaultViewInstance()
    {
        $view = new lcDataView();
        $view->setContentType($this->configuration['view.content_type']);
        return $view;
    }

    protected function forwardToAction($parent_controller, $action_name, array $custom_params = null, $controller = null)
    {
        // custom handling of exceptions in web service mode
        try {
            parent::forwardToControllerAction($parent_controller, $action_name, $custom_params, $controller);
        } catch (Exception $e) {
            $front_controller = $this->getRootController();

            if ($front_controller && $front_controller instanceof lcFrontWebServiceController) {
                $front_controller->sendErrorResponseFromException($e);
            }
        }
    }
}
