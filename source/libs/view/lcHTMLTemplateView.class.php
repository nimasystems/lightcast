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

class lcHTMLTemplateView extends lcHTMLView implements ArrayAccess, iDebuggable, iDecoratingView
{
    const FRAGMENT_PREFIX = 'f#';
    const PARTIAL_PREFIX = 'p#';

    protected $view_contents;

    /** @var lcIterateParamHolder */
    protected $params;

    protected $found_controller_actions = [];
    protected $found_fragments = [];

    protected $template_filename;

    protected $enable_partials = true;
    protected $enable_fragments = true;

    public function initialize()
    {
        parent::initialize();

        $this->params = new lcIterateParamHolder();
    }

    public function shutdown()
    {
        if ($this->params) {
            $this->params = null;
        }

        $this->view_contents =
        $this->found_controller_actions =
        $this->found_fragments = null;

        parent::shutdown();
    }

    public function __toString()
    {
        return 'Template filename: ' . $this->template_filename;
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = [
            'template_filename' => $this->template_filename,
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getControllerActionsToDecorate()
    {
        return $this->found_controller_actions;
    }

    public function getFragmentsToDecorate()
    {
        return $this->found_fragments;
    }

    public function getTemplateFilename()
    {
        return $this->template_filename;
    }

    public function setTemplateFilename($filename)
    {
        $this->template_filename = $filename;
    }

    public function getParams()
    {
        return $this->params;
    }

    // left here for compatibility
    public function __get($name)
    {
        return $this->params->__get($name);
    }

    // left here for compatibility
    public function __set($name, $value = null)
    {
        return $this->params->__set($name, $value);
    }

    public function setParams(array $params = null)
    {
        $this->params->setParams($params);
        return $this;
    }

    /*
     * Keep public for PHP 5.3 compatibility
     */

    /**
     * @param $name
     * @param null $params
     * @return lcIterateParamHolder
     */
    public function getNode($name, $params = null)
    {
        return $this->params->getNode($name, $params);
    }

    /*
     * Keep public for PHP 5.3 compatibility
     */

    /**
     * @param $name
     * @param null $params
     * @return lcHtmlTemplateView
     */
    public function repeat($name, $params = null)
    {
        return $this->params->repeat($name, $params);
    }

    /**
     * @param $node_deep_name
     * @return lcHtmlTemplateView
     */
    public function getDeepNode($node_deep_name)
    {
        return $this->params->getDeepNode($node_deep_name);
    }

    /**
     * @return lcHtmlTemplateView[]
     */
    public function getNodes()
    {
        return $this->params->getNodes();
    }

    public function insertNode($name, lcIterateParamHolder $node)
    {
        return $this->params->insertNode($name, $node);
    }

    public function copyNode($name, lcIterateParamHolder $node)
    {
        return $this->params->copyNode($name, $node);
    }

    public function rawText($text = null)
    {
        return $this->params->rawText($text);
    }

    public function offsetExists($name)
    {
        return $this->params->offsetExists($name);
    }

    public function offsetGet($name)
    {
        return $this->params->offsetGet($name);
    }

    public function offsetSet($name, $value)
    {
        $this->params->offsetSet($name, $value);
    }

    public function offsetUnset($name)
    {
        $this->params->offsetUnset($name);
    }

    protected function getViewContent()
    {
        return $this->getTemplateData();
    }

    protected function getTemplateData()
    {
        $template_filename = $this->template_filename;

        if (!isset($template_filename)) {
            throw new lcInvalidArgumentException('No template filename has been set to view');
        }

        $data = @file_get_contents($template_filename);

        return $data;
    }

    protected function didApplyFilters($content)
    {
        // parse the content to obtain partials / fragments
        $content = $this->parseParticles($content);
        return $content;
    }

    protected function parseParticles($data)
    {
        // parse and find info of found partials
        $self = $this;
        $data = preg_replace_callback("/<!--\s*PARTIAL\s*(.*?)\s*-->/i", function ($m) use ($self) {
            return $self->parsePartialDetails(@$m[1]);
        }, $data);

        // parse all find info of found fragments
        $data = preg_replace_callback("/<!--\s*FRAGMENT\s*(.*?)\s*-->/i", function ($m) use ($self) {
            return $self->parseFragmentDetails(@$m[1]);
        }, $data);

        return $data;
    }

    /**
     * @param $url
     * @warning Leave as public for PHP 5.3x compatibility (some methods are calling from lambdas below)
     * @return null|string
     */
    public function parsePartialDetails($url)
    {
        if (!$url) {
            return null;
        }

        $url = stripslashes($url);

        $rep_tag = self::PARTIAL_PREFIX . (count($this->found_controller_actions) + 1) . '#' . $url . '#';

        $this->found_controller_actions[] = [
            'tag_name' => $rep_tag,
            'route' => $url,
            'action_type' => 'partial'
        ];

        // return the tag as the replacement of the preg - this is
        // how we'll match it later on to replace with actual content
        return $rep_tag;
    }

    public function parseFragmentDetails($url)
    {
        if (!$url) {
            return null;
        }

        $res = null;
        $type = 'file';

        // a web page
        if ((substr($url, 0, 7) == 'http://') ||
            (substr($url, 0, 8) == 'https://') ||
            (substr($url, 0, 4) == 'www.')
        ) {
            $type = 'url';
            $res = $url;
        } else {
            // if relative - search for the file within the assets folder of the module
            // otherwise try to include it directly
            $res = ($url{0} == '/') ? $url : $this->controller->getAssetsPath() . DS . $url;

            $do_include =
                lcFiles::getFileExt($res) == '.php' ?
                    true : false;

            if ($do_include) {
                $type = 'php';
            }
        }

        $rep_tag = self::FRAGMENT_PREFIX . (count($this->found_fragments) + 1);
        $this->found_fragments[] = [
            'tag_name' => $rep_tag,
            'url' => $res,
            'type' => $type
        ];

        return $rep_tag;
    }
}
