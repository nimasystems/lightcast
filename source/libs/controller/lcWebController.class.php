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

abstract class lcWebController extends lcWebBaseController implements iKeyValueProvider, iViewDecorator
{
    const DEFAULT_HAS_LAYOUT = true;
    const DEFAULT_LAYOUT_NAME = 'index';
    const DEFAULT_LAYOUT_EXT = 'htm';
    const LAYOUT_CONTENT_REPLACEMENT = '[PAGE_CONTENT]';
    const ASSETS_DIR = 'templates';

    /**
     * @var lcHtmlTemplateView
     */
    protected $view;

    private $included_javascripts = [];
    private $included_stylesheets = [];
    private $required_javascript_code = [];

    protected $web_path;

    protected $title;
    protected $description;
    protected $keywords;
    /** @var lcBaseActionForm[] */
    protected $action_forms;
    private $show_extra_debugging;
    private $default_decorator;
    private $default_decorator_extension;

    public function getRequiredJavascriptCode()
    {
        return $this->required_javascript_code;
    }

    public function setDefaultDecorator($default_decorator, $decorator_filename_ext = self::DEFAULT_LAYOUT_EXT)
    {
        $this->default_decorator = $default_decorator;
        $this->default_decorator_extension = $decorator_filename_ext;
    }

    public function getAllKeys()
    {
        return [
            'my_webpath',
        ];
    }

    public function getValueForKey($key)
    {
        if ($key == 'my_webpath' || $key == 'my_path') {
            return $this->getWebPath();
        } else if ($key == 'my_action_path') {
            return $this->getMyActionPath();
        }
        return null;
    }

    /**
     * @return array
     */
    public function getIncludedJavascripts()
    {
        return $this->included_javascripts;
    }

    /**
     * @return array
     */
    public function getIncludedStylesheets()
    {
        return $this->included_stylesheets;
    }

    /**
     * @param $src
     * @param array|null $options
     * @param null $tag
     * @return $this
     */
    protected function includeJavascript($src, array $options = null, $tag = null)
    {
        $tag = $tag ?: 'js_' . $this->getRandomIdentifier();
        $this->included_javascripts[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    protected function includeStylesheet($src, array $options = null, $tag = null)
    {
        $tag = $tag ?: 'css_' . $this->getRandomIdentifier();
        $this->included_stylesheets[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    public function getWebPath($suffixed = true)
    {
        return $this->web_path . ($suffixed ? '/' : null);
    }

    public function setWebPath($web_path)
    {
        $this->web_path = $web_path;
    }

    public function getMyActionPath()
    {
        return $this->getWebPath() . $this->action_name;
    }

    public function setCustomTemplate($filename)
    {
        $full_filename = dirname($this->controller_filename) . DS . 'templates' . DS . $filename . '.htm';
        $this->view->setTemplateFilename($full_filename);
    }

    public function unsetDecorator()
    {
        $this->unsetDecoratorView();
    }

    public function decorateViewContent(lcView $view, $content)
    {
        $routing = $this->routing;

        if (!$this->getHasInitialized()) {
            throw new Exception();
        }

        if (!$routing) {
            return $content;
        }

        if ($view instanceof lcHTMLTemplateView) {
            $controllers = $view->getControllerActionsToDecorate();
            $fragments = $view->getFragmentsToDecorate();

            // compile controlers
            if ($controllers) {
                foreach ($controllers as $controller_info) {
                    $controller_content = null;

                    $tag_name = isset($controller_info['tag_name']) ? $controller_info['tag_name'] : null;
                    $route = isset($controller_info['route']) ? $controller_info['route'] : null;
                    $action_type = isset($controller_info['action_type']) ? (string)$controller_info['action_type'] : null;

                    $has_error = false;
                    $error_message = null;
                    $error_trace = null;
                    $module = null;
                    $render_time = null;
                    $action = null;

                    if ($this->show_extra_debugging) {
                        $render_time = microtime(true);
                    }

                    if ($tag_name && $route && $action_type) {
                        // try to find a matching route
                        $found_route = $routing->findMatchingRoute($route);

                        if ($found_route) {
                            $module = $found_route['params']['module'];
                            $action = $found_route['params']['action'];
                            $params = $found_route['params'];

                            $params = [
                                'request' => array_merge(
                                    [
                                        'module' => $module,
                                        'action' => $action,
                                        'type' => $action_type,
                                    ],
                                    (array)$params
                                ),
                                'type' => $action_type,
                            ];

                            if (!$tag_name || !$route || !$module || !$action) {
                                continue;
                            }

                            try {
                                // obtain an instance of the controller
                                /** @var lcWebController $decorating_controller */
                                $decorating_controller = $this->getControllerInstance($module);

                                if (!$decorating_controller) {
                                    throw new lcControllerNotFoundException('Controller not found');
                                }

                                $this->prepareControllerInstance($decorating_controller);

                                $render_response = null;

                                $decorating_controller->initialize();

                                try {
                                    $render_response = $this->renderControllerAction(
                                        $decorating_controller,
                                        $action,
                                        $params);
                                    $render_response['javascript'] = $decorating_controller->renderJavascriptCode(true, false);
                                } catch (Exception $e) {
                                    $decorating_controller->shutdown();
                                    throw $e;
                                }

                                if ($render_response && isset($render_response['content'])) {
                                    $controller_content = $render_response['content'];
                                    $controller_content_type = isset($render_response['content_type']) ? $render_response['content_type'] : null;

                                    if ($controller_content_type && $controller_content_type != 'text/html') {
                                        throw new lcUnsupportedException('Content unsupported - ' . $controller_content_type);
                                    }

                                    if (isset($render_response['javascript'])) {
                                        $this->addJavascriptCode($render_response['javascript']);
                                    }

                                    unset($controller_content_type);
                                }

                                unset($render_response);
                            } catch (Exception $e) {
                                $has_error = true;
                                $controller_content = null;
                                $error_message = 'Decorating error (' . $module . '/' . $action . '): ' . $e->getMessage();
                                $error_trace = "Trace:\n\n" . $e->getTraceAsString();

                                $this->err('Could not get render decorator action (' . $module . '/' . $action . '): ' . $e->getMessage());
                            }
                        }
                    }

                    // add debugging information
                    if ($this->show_extra_debugging) {
                        $total_render_time = sprintf('%.0f', (microtime(true) - $render_time) * 1000);

                        $_url = htmlspecialchars(($this->parent_plugin ? $this->parent_plugin->getPluginName() . ' :: ' : null) . $module . '/' . $action);
                        $controller_content =
                            '<!-- DecoratingControllerBegin (' . $_url . '), time: ' . $total_render_time . ' ms. -->' . "\n" .
                            /*'<div title="Decorating controller (' . $_url . ')' . ($has_error ? "\n\nError:\n\n" . htmlspecialchars($error_message) .
                                    "\n\n" . htmlspecialchars($error_trace) : null) . '">' .*/
                            (!$has_error ? $controller_content :
                                '<div style="color:white;background-color:pink;border:1px solid gray;padding:2px;font-size:10px">Decorator error: ' .
                                $_url . "\n\n" . nl2br(htmlspecialchars($error_message)) . "\n\n" . nl2br(htmlspecialchars(($error_trace))) . '</div>') .
                            /*'</div>' .*/
                            '<!-- DecoratingControllerEnd (' . $_url . ') -->';

                        unset($total_render_time, $_url, $render_time);
                    }

                    $content = str_replace($tag_name, $controller_content, $content);

                    unset($tag_name, $route, $controller_info, $module, $action, $action_type, $params, $error_message, $error_trace, $has_error);
                }
            }

            // compile fragments
            if ($fragments && is_array($fragments)) {
                foreach ($fragments as $fragment_info) {
                    $tag_name = isset($fragment_info['tag_name']) ? $fragment_info['tag_name'] : null;
                    $url = isset($fragment_info['url']) ? $fragment_info['url'] : null;
                    $type = isset($fragment_info['type']) ? $fragment_info['type'] : null;

                    if (!$tag_name || !$url || !$type) {
                        continue;
                    }

                    $fragment_content = null;
                    $has_error = false;
                    $error_message = null;
                    $error_trace = null;
                    $render_time = null;

                    if ($this->show_extra_debugging) {
                        $render_time = microtime(true);
                    }

                    try {
                        $fragment_content = $this->renderFragment($type, $url);

                        if ($fragment_content && !is_string($fragment_content)) {
                            throw new lcUnsupportedException('Content unsupported - not a string');
                        }
                    } catch (Exception $e) {
                        $has_error = true;
                        $fragment_content = null;
                        $error_message = 'Decorating error: ' . $e->getMessage();
                        $error_trace = "Trace:\n\n" . $e->getTraceAsString();

                        $this->err('Could not get fragment: ' . $e->getMessage());
                    }

                    // add debugging information
                    if ($this->show_extra_debugging) {
                        $total_render_time = sprintf('%.0f', (microtime(true) - $render_time) * 1000);

                        $_url = htmlspecialchars(($this->parent_plugin ? $this->parent_plugin->getPluginName() . ' :: ' : null) . $url);
                        $fragment_content =
                            '<!-- DecoratingFragmentBegin (' . $_url . '), time: ' . $total_render_time . ' ms. -->' . "\n" .
                            '<div title="Decorating fragment (' . $_url . ')' . ($has_error ? "\n\nError:\n\n" . htmlspecialchars($error_message) .
                                "\n\n" . htmlspecialchars($error_trace) : null) . '">' .
                            (!$has_error ? $fragment_content :
                                '<div style="color:white;background-color:pink;border:1px solid gray;padding:2px;font-size:10px">Decorator error: ' .
                                $_url . "\n\n" . nl2br(htmlspecialchars($error_message)) . '</div>') .
                            '</div>' .
                            '<!-- DecoratingFragmentBegin (' . $_url . ') -->';

                        unset($total_render_time, $_url, $render_time);
                    }

                    $content = str_replace($tag_name, $fragment_content, $content);

                    unset($tag_name, $url, $type, $fragment_content);
                }
            }
        }

        return $content;
    }

    public function initialize()
    {
        parent::initialize();

        // initialize layout view
        if (!$this->default_decorator) {
            $this->default_decorator = isset($this->configuration['view.decorator']) ? (string)$this->configuration['view.decorator'] : self::DEFAULT_LAYOUT_NAME;
        }

        if (!$this->default_decorator_extension) {
            $this->default_decorator_extension = isset($this->configuration['view.extension']) ? (string)$this->configuration['view.extension'] : self::DEFAULT_LAYOUT_EXT;
        }

        // extra debugging
        $this->show_extra_debugging = isset($this->configuration['controller.extra_debug']) ? (bool)$this->configuration['controller.extra_debug'] : DO_DEBUG;

        // init default layout
        $has_layout = isset($this->configuration['view.has_layout']) ? (bool)$this->configuration['view.has_layout'] : self::DEFAULT_HAS_LAYOUT;

        // do not enable decorator by default on ajax requests
        if ($has_layout && !$this->request->isAjax()) {
            $this->setDecorator($this->default_decorator, $this->default_decorator_extension);
        }
    }

    public function setDecorator($decorator_template_name = null, $extension = 'htm')
    {
        if (!$decorator_template_name) {
            $this->unsetDecoratorView();
            return;
        }

        $extension = $extension ? $extension : $this->default_decorator_extension;

        $full_template_name = $this->configuration->getLayoutsDir() . DS . $decorator_template_name . '.' . $extension;

        $view = $this->getDefaultLayoutViewInstance();

        if (!$view) {
            return;
        }

        $view->setTemplateFilename($full_template_name);

        $this->setDecoratorView($view);
    }

    public function getDefaultLayoutViewInstance()
    {
        $view = new lcHTMLTemplateLayoutView();

        $view->setEventDispatcher($this->event_dispatcher);
        $view->setConfiguration($this->configuration);
        $view->setController($this);
        $view->setReplacementString(self::LAYOUT_CONTENT_REPLACEMENT);
        $view->initialize();

        return $view;
    }

    private function processWebComponentResponse(lcWebComponent $component, lcWebResponse $response)
    {
        // js code
        $js_code = $component->getRequiredJavascriptCode();

        if ($js_code) {
            foreach ($js_code as $identifier => $code2) {
                // append the component name before the identifier - to prevent overlapping of identifiers
                $identifier = $component->getControllerName() . '-' . $identifier;
                $code[$identifier] = $code2;
                unset($identifier, $code2);
            }
        }

        $parent_plugin = $component->getParentPlugin();

        // javascript includes
        $this->renderIncludedJavascript($component->getIncludedJavascripts(), $response, $parent_plugin);

        // css includes
        $this->renderIncludedStylesheets($component->getIncludedStylesheets(), $response, $parent_plugin);
    }

    public function renderJavascriptCode($with_children = true, $with_script_tag = true)
    {
        /** @var lcWebResponse $response */
        $response = $this->getResponse();

        $code = (array)$this->required_javascript_code;

        if ($with_children) {
            $components = $this->getLoadedComponents();

            if ($components) {
                foreach ($components as $component_data) {
                    /** @var lcWebComponent $component */
                    $component = $component_data['instance'];

                    if ($component instanceof lcWebComponent) {
                        $this->processWebComponentResponse($component, $response);

                        $js_codes = $component->getRequiredJavascriptCode();

                        if ($js_codes) {
                            foreach ($js_codes as $identifier => $code2) {
                                // append the component name before the identifier - to prevent overlapping of identifiers
                                $identifier = $component->getControllerName() . '-' . $identifier;
                                $code[$identifier] = $code2;
                                unset($identifier, $code2);
                            }
                        }

                        unset($js_codes);
                    }
                }
            }
        }

        if ($code) {
            $out = implode("\n", array_values($code));

            if ($with_script_tag) {
                return lcTagScript::create()
                    ->setContent($out)
                    ->toString();
            } else {
                return $out;
            }
        }

        return null;
    }

    public function shutdown()
    {
        if ($this->action_forms) {
            foreach ($this->action_forms as $form) {
                $form->shutdown();
                unset($form);
            }
            $this->action_forms = null;
        }

        parent::shutdown();
    }

    public function addJavascriptCode($code, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_javascript_code[$identifier] = (is_array($code) ? implode("\n", $code) : $code);
    }

    public function getActionFormInstance($form_name)
    {
        if (!$this->system_component_factory) {
            throw new lcNotAvailableException('System Component Factory not available');
        }

        $form_instance = $this->system_component_factory->getActionFormInstance($form_name);

        if (!$form_instance) {
            return null;
        }

        // assign system objects
        $form_instance->setEventDispatcher($this->event_dispatcher);
        $form_instance->setConfiguration($this->configuration);

        $form_instance->setI18n($this->i18n);

        // translation context
        $form_instance->setTranslationContext($this->getContextType(), $this->getContextName());

        $form_instance->setClassAutoloader($this->class_autoloader);
        $form_instance->setPluginManager($this->plugin_manager);

        $form_instance->setController($this);

        $form_instance->initialize();

        $this->action_forms[] = $form_instance;

        return $form_instance;
    }

    public function getMyPath($suffixed = true)
    {
        return $this->getWebPath($suffixed);
    }

    protected function execute($action_name, array $action_params)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        $action_params['request'] = isset($action_params['request']) ? (array)$action_params['request'] : [];
        $action_params['type'] = isset($action_params['type']) ? (string)$action_params['type'] : $action_type;

        $this->action_name = $action_name;
        $this->action_params = $action_params;
        $this->action_type = $action_type;

        $action = $this->classMethodForAction($action_name, $action_params);
        $controller_name = $this->controller_name;

        if (DO_DEBUG) {
            $this->debug(sprintf('%-40s %s', 'Execute ' . ($this->parent_plugin ? 'p-' . $this->parent_plugin->getPluginName() . ' :: ' : null) . $controller_name . '/' . $action_name .
                '(' . $this->action_type . ')', '{' . lcArrays::arrayToString($action_params) . '}'));
        }

        if (!$this->actionExists($action_name, $action_params)) {
            throw new lcActionNotFoundException('Controller action: \'' . $this->controller_name . ' / ' . $action_name . '\' is not valid');
        }

        // configure the default view
        if (!$this->getView()) {
            $view = $this->getDefaultViewInstance();

            if ($view) {
                $this->configureControllerView($view);
            }
        }

        // run before execute
        call_user_func_array([$this, 'beforeExecute'], $action_params);

        $req = $this->getRequest();

        $should_use_reqresp = self::checkShouldUseRequestArgument($this, $action, 'lcWebRequest');

        $call_params = $action_params;

        if ($should_use_reqresp) {
            $reqr = clone $req;
            $reqr->setCallStyle(lcController::CALL_STYLE_REQRESP);
            $call_params = [$reqr];
        }

        // call the action
        // unfortunately the way we are handling variables at the moment
        // we can't use the fast calling as args need to be expanded with their names (actions are looking for them)
        // so we fall back to the default way
        //$call_result = $this->__call($action, $params);
        $this->action_result = call_user_func_array([$this, $action], $call_params);

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

        return $this->action_result;
    }

    protected function classMethodForAction($action_name, array $action_params = null)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        $method_name = $action_type . ucfirst(lcInflector::camelize($action_name));
        return $method_name;
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
        $callable_check = is_callable([$this, $method_name]) && method_exists($this, $method_name);

        return $callable_check;
    }

    protected function configureControllerView(lcView $view)
    {
        $view->setOptions([
            'action_name' => $this->getActionName(),
            'action_params' => $this->getActionParams(),
        ]);
        $view->setConfiguration($this->getConfiguration());
        $view->setEventDispatcher($this->getEventDispatcher());

        // set the view template
        $template_filename = $this->getAssetsPath() . DS . $this->getActionName() . '.htm';

        $view->setTemplateFilename($template_filename);
        $view->setController($this);

        $view->initialize();

        // set to controller
        $this->setView($view);
    }

    public function getDefaultViewInstance()
    {
        $instance = new lcHTMLTemplateView();
        return $instance;
    }

    public function getAssetsPath()
    {
        return $this->getControllerDirectory() . DS . self::ASSETS_DIR;
    }

    protected function processViewResponse(lcWebController $controller)
    {
        $this->outputViewContents($controller, $this->view->render(), $this->view->getContentType());
    }

    protected function outputViewContents(lcController $controller, $content = null, $content_type = null)
    {
        // add debugging information
        /*if (DO_DEBUG && $this->show_extra_debugging && $content_type == 'text/html')
        {
            $render_time = $this->getRenderTime();

            $total_render_time = sprintf('%.0f', $render_time * 1000);

            $_url = htmlspecialchars(($this->parent_plugin ? $this->parent_plugin->getPluginName() . ' :: ' : null) . $controller->getControllerName() . '/' . $controller->getActionName());
            //$content =
            //'<!-- ActionBegin (' . $_url . '), time: ' . $total_render_time . ' ms. -->' . "\n" .
            $content .=
            '<!-- ActionEnd (' . $_url . '), time: ' . $total_render_time . ' ms. -->' . "\n";

            unset($total_render_time, $_url, $render_time);
        }*/

        /** @var lcWebResponse $response */
        $response = $this->getResponse();

        // send the output
        if ($content_type) {
            $response->setContentType($content_type);
        }

        $this->event_dispatcher->notify(new lcEvent('controller.will_process_view', $this, [
            'content' => $content,
            'content_type' => $content_type,
        ]));

        if ($controller instanceof lcWebController) {
            $this->processWebControllerResponse($controller, $response);
        }

        $this->event_dispatcher->notify(new lcEvent('controller.did_process_view', $this, [
            'content' => $content,
            'content_type' => $content_type,
        ]));

        $response->setContent($content);
        $response->sendResponse();
    }

    private function processWebControllerResponse(lcWebController $controller, lcWebResponse $response)
    {
        /* page metadata */
        $title = $controller->getTitle();

        if ($title) {
            $response->setTitle($title);
            //$response->setMetatag('title', $title);
        }

        $description = $controller->getDescription();

        if ($description) {
            $response->setMetatag('description', $description);
        }

        $keywords = $controller->getKeywords();

        if ($keywords) {
            $response->setMetatag('keywords', $keywords);
        }

        $parent_plugin = $controller->getParentPlugin();

        // javascript includes
        $this->renderIncludedJavascript($controller->getIncludedJavascripts(), $response, $parent_plugin);

        // css includes
        $this->renderIncludedStylesheets($controller->getIncludedStylesheets(), $response, $parent_plugin);

        // javascript code
        $javascript_code = $controller->renderJavascriptCode(true, false);

        if ($javascript_code) {
            $response->setJavascriptCode($javascript_code);
        }
    }

    private function renderIncludedStylesheets(array $included_stylesheets, lcWebResponse $response, lcPlugin $parent_plugin = null)
    {
        $assets_webpath = $parent_plugin ? $parent_plugin->getAssetsWebPath('css') : null;

        foreach ($included_stylesheets as $tag => $data) {
            $opts = isset($data['options']) ? $data['options'] : [];
            $src = !$assets_webpath || lcStrings::isAbsolutePath($data['src']) ? $data['src'] : $assets_webpath . $data['src'];
            $response->setStylesheet($src,
                isset($opts['media']) ? $opts['media'] : null,
                isset($opts['type']) ? $opts['type'] : null
            );
            unset($tag, $data, $src, $opts);
        }
    }

    private function renderIncludedJavascript(array $included_javascripts, lcWebResponse $response, lcPlugin $parent_plugin = null)
    {
        $assets_webpath = $parent_plugin ? $parent_plugin->getAssetsWebPath('js') : null;

        foreach ($included_javascripts as $tag => $data) {
            $opts = isset($data['options']) ? $data['options'] : [];
            $prepend = isset($opts['prepend']) && $opts['prepend'];
            $src = !$assets_webpath || lcStrings::isAbsolutePath($data['src']) ? $data['src'] : $assets_webpath . $data['src'];

            if ($prepend) {
                $response->prependJavascript($src,
                    isset($opts['type']) ? $opts['type'] : null,
                    isset($opts['language']) ? $opts['language'] : null,
                    isset($opts['at_end']) ? $opts['at_end'] : null,
                    isset($opts['attribs']) ? $opts['attribs'] : null
                );
            } else {
                $response->setJavascript($src,
                    isset($opts['type']) ? $opts['type'] : null,
                    isset($opts['language']) ? $opts['language'] : null,
                    isset($opts['at_end']) ? $opts['at_end'] : null,
                    isset($opts['attribs']) ? $opts['attribs'] : null
                );
            }

            unset($tag, $data, $opts);
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
        return $this;
    }
}
