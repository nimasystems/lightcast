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
 * @changed $Id: lcView.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcView extends lcSysObj implements iViewRenderer, iDebuggable
{
    protected $view_filter_chain;
    protected $view_decorator;

    protected $view_debugging_enabled;
    protected $content_type;

    protected $action_result;

    protected $controller;
    protected $label;
    protected $options;

    abstract protected function getViewContent();

    public function shutdown()
    {
        $this->view_filter_chain =
        $this->view_decorator =
        $this->action_result =
        $this->options =
        $this->controller = null;

        parent::shutdown();
    }

    protected function willApplyFilters($content)
    {
        // subclassers may override this method to detect the state
        return $content;
    }

    protected function didApplyFilters($content)
    {
        // subclassers may override this method to detect the state
        return $content;
    }

    protected function willDecorateView($content)
    {
        // subclassers may override this method to detect the state
        return $content;
    }

    protected function didDecorateView($content)
    {
        // subclassers may override this method to detect the state
        return $content;
    }

    public function render()
    {
        // check if rendering is supported
        $supported_content_types = $this->getSupportedContentTypes();

        if ($this->content_type && $supported_content_types && is_array($supported_content_types) &&
            !in_array($this->content_type, $supported_content_types)
        ) {
            return null;
        }

        // get the content from subclassed view
        $view_content = $this->getViewContent();

        // apply view filters
        if ($this->view_filter_chain) {
            try {
                $view_content = $this->willApplyFilters($view_content);
                $view_content = $this->view_filter_chain->execute($this, $view_content, $this->getContentType());
                $view_content = $this->didApplyFilters($view_content);

            } catch (Exception $ee) {
                throw new lcFilterException('Could not apply view filters: ' .
                    $ee->getMessage(),
                    $ee->getCode(),
                    $ee);
            }
        }

        // decorate the view
        if ($this->view_decorator) {
            try {
                $view_content = $this->willDecorateView($view_content);
                $view_content = $this->view_decorator->decorateViewContent($this, $view_content);
                $view_content = $this->didDecorateView($view_content);
            } catch (Exception $e) {
                throw new lcRenderException('Could not decorate view: ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e);
            }
        }

        return $view_content;
    }

    public function getDebugInfo()
    {
        $debug = array(
            'options' => $this->options
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function setViewDecorator(iViewDecorator $view_decorator)
    {
        $this->view_decorator = $view_decorator;
    }

    public function getViewDecorator()
    {
        return $this->view_decorator;
    }

    public function setViewFilterChain(lcViewFilterChain $view_filter_chain)
    {
        $this->view_filter_chain = $view_filter_chain;
    }

    public function getViewFilterChain()
    {
        return $this->view_filter_chain;
    }

    public function getContentType()
    {
        return $this->content_type;
    }

    public function setContentType($content_type)
    {
        $this->content_type = $content_type;
    }

    public function setActionResult($action_result)
    {
        $this->action_result = $action_result;
    }

    public function getActionResult()
    {
        return $this->action_result;
    }

    public function setViewDebuggingEnabled($debugging_enabled = true)
    {
        $this->view_debugging_enabled = $debugging_enabled;
    }

    public function getViewDebuggingEnabled()
    {
        return $this->view_debugging_enabled;
    }

    public function setController(lcBaseController $controller)
    {
        $this->controller = $controller;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }
}

?>