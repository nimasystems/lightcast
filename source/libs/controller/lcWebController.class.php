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

    /**
     * @var string[]
     */
    protected $required_js_includes;

    /**
     * @var string[]
     */
    protected $required_css_includes;

    /**
     * @var string[]
     */
    protected $required_javascript_code;

    /**
     * @var string
     */
    protected $web_path;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var bool
     */
    private $show_extra_debugging;

    private $default_decorator;
    private $default_decorator_extension;

    /** @var lcBaseActionForm[] */
    protected $action_forms;

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

    private function getRandomIdentifier()
    {
        return 'anon_' . $this->getControllerName() . '/' . $this->getActionName() . '_' . lcStrings::randomString(15);
    }

    public function addJavascriptInclude($location, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_js_includes[$identifier] = $location;
    }

    public function addCssInclude($location, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_css_includes[$identifier] = $location;
    }

    public function addJavascriptCode($code, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_javascript_code[$identifier] = (is_array($code) ? implode("\n", $code) : $code);
    }

    public function getRequiredJavascriptIncludes()
    {
        return $this->required_js_includes;
    }

    public function getRequiredCssIncludes()
    {
        return $this->required_css_includes;
    }

    public function getRequiredJavascriptCode()
    {
        return $this->required_javascript_code;
    }

    public function renderJavascriptCode($with_children = true, $with_script_tag = true)
    {
        $code = (array)$this->required_javascript_code;

        if ($with_children) {
            $components = $this->getLoadedComponents();

            if ($components) {
                foreach ($components as $component_data) {
                    /** @var lcWebComponent $component */
                    $component = $component_data['instance'];

                    if ($component instanceof lcWebComponent) {
                        $js_codes = $component->getRequiredJavascriptCode();

                        if ($js_codes) {
                            foreach ($js_codes as $identifier => $code2) {
                                // append the component name before the identifier - to prevent overlapping of identifiers
                                $identifier = $component->getControllerName() . '-' . $identifier;
                                $code[$identifier] = $code2;
                                unset($identifier, $code2);
                            }
                        }
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

    public function setDefaultDecorator($default_decorator, $decorator_filename_ext = self::DEFAULT_LAYOUT_EXT)
    {
        $this->default_decorator = $default_decorator;
        $this->default_decorator_extension = $decorator_filename_ext;
    }

    public function getAllKeys()
    {
        return array(
            'my_webpath',
        );
    }

    public function getValueForKey($key)
    {
        if ($key == 'my_webpath' || $key == 'my_path') {
            return $this->getWebPath();
        } elseif ($key == 'my_action_path') {
            return $this->getMyActionPath();
        }
        return null;
    }

    public function getMyActionPath()
    {
        return $this->getWebPath() . $this->action_name;
    }

    public function getWebPath($suffixed = true)
    {
        return $this->web_path . ($suffixed ? '/' : null);
    }

    public function setWebPath($web_path)
    {
        $this->web_path = $web_path;
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

                            $params = array(
                                'request' => array_merge(
                                    array(
                                        'module' => $module,
                                        'action' => $action,
                                        'type' => $action_type,
                                    ),
                                    (array)$params
                                ),
                                'type' => $action_type
                            );

                            if (!$tag_name || !$route || !$module || !$action) {
                                continue;
                            }

                            try {
                                // obtain an instance of the controller
                                /** @var lcWebController $decorating_controller */
                                $decorating_controller = $this->getControllerInstance($module, $action, $action_type);

                                if (!$decorating_controller) {
                                    throw new lcControllerNotFoundException('Controller not found');
                                }

                                // reset in case of object overrides
                                $action = $decorating_controller->getActionName();

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

    /**
     * @param $form_name
     * @return lcBaseActionForm|null
     * @throws lcNotAvailableException
     */
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

        $this->action_forms[] = $form_instance;

        return $form_instance;
    }

    public function getMyPath($suffixed = true)
    {
        return $this->getWebPath($suffixed);
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

    protected function execute($action_name, array $action_params)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        $action_params['request'] = isset($action_params['request']) ? (array)$action_params['request'] : array();
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
            $this->configureControllerView();
        }

        // run before execute
        call_user_func_array(array($this, 'beforeExecute'), $action_params);

        // call the action
        // unfortunately the way we are handling variables at the moment
        // we can't use the fast calling as args need to be expanded with their names (actions are looking for them)
        // so we fall back to the default way
        //$call_result = $this->__call($action, $params);
        $this->action_result = call_user_func_array(array($this, $action), $action_params);

        // run after execute
        call_user_func_array(array($this, 'afterExecute'), $action_params);

        // notify after the action has been executed
        if ($this->event_dispatcher) {
            $this->event_dispatcher->notify(new lcEvent('controller.executed_action', $this,
                array('controller_name' => $this->controller_name,
                    'action_name' => $this->action_name,
                    'action_type' => $this->action_type,
                    'controller' => $this,
                    'action_params' => $this->action_params,
                    'action_result' => $this->action_result,
                )
            ));
        }

        return $this->action_result;
    }

    protected function classMethodForAction($action_name, array $action_params = null)
    {
        $action_type = isset($action_params['type']) ? (string)$action_params['type'] : lcController::TYPE_ACTION;
        return $action_type . ucfirst(lcInflector::camelize($action_name));
    }

    protected function configureControllerView()
    {
        // create and set a view to the controller
        $view = $this->getDefaultViewInstance();

        if (!$view) {
            return;
        }

        $view->setOptions(array(
            'action_name' => $this->getActionName(),
            'action_params' => $this->getActionParams(),
        ));
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
        return new lcHTMLTemplateView();
    }

    public function getAssetsPath()
    {
        return $this->getControllerDirectory() . DS . self::ASSETS_DIR;
    }

    /*
     * @deprecated The method is used by LC 1.4 projects
    */

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

        if ($controller instanceof lcWebController) {
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

            $javascript_code = $controller->renderJavascriptCode(true, false);

            if ($javascript_code) {
                $response->setJavascriptCode($javascript_code);
            }
        }

        $response->setContent($content);
        $response->sendResponse();
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
