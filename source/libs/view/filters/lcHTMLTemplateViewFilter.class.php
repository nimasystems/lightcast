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
 * @changed $Id: lcHTMLTemplateViewFilter.class.php 1569 2015-01-28 21:49:54Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1569 $
 */
class lcHTMLTemplateViewFilter extends lcViewFilter
{
    protected $debug;

    /** @var lcHTMLTemplateView */
    protected $view;
    private $tmp_node_params;

    protected function getShouldApplyFilter()
    {
        // we support only lcHTMLTemplateView
        if (!($this->view instanceof lcHTMLTemplateView)) {
            return false;
        }

        return true;
    }

    protected function applyFilter($content, $content_type = null)
    {
        // we support only text/html
        if ($content_type != 'text/html') {
            return $content;
        }

        $this->debug = $this->view->getViewDebuggingEnabled();

        // compile deep
        $params = $this->view->getParams();
        $content = $this->parseNodeTemplate($params, $content);
        $content = $this->postCompile($content);

        return $content;
    }

    protected function parseNodeTemplate(lcIterateParamHolder $params, $data)
    {
        return $this->parseNodeComplete($data, $params);
    }

    protected function parseNodeComplete($template, lcIterateParamHolder $holder)
    {
        $replacement_policy = $holder->getReplacementPolicy();

        switch ($replacement_policy) {
            case lcIterateParamHolder::REPLACE_DEEP: {
                $template = $this->parseTemplateCycle($holder, $template);
                $template = $this->parseSubnodesCycle($holder, $template);

                break;
            }
            case lcIterateParamHolder::REPLACE_LEVEL: {
                $template = $this->parseSubnodesCycle($holder, $template);
                $template = $this->parseTemplateCycle($holder, $template);

                break;
            }
        }

        // clear template
        $template = $this->clearHtmlNode($template);

        return $template;
    }

    protected function parseTemplateCycle(lcIterateParamHolder $holder, $template)
    {
        // parse repeats
        $repeats = $holder->getRepeats();

        if ($repeats && count($repeats)) {
            $out_repeat = '';
            $parses = array();

            foreach ($repeats as $repeat) {
                $node_name = $repeat->getNodeName();

                $tmp_tem = $this->getHtmlNode($node_name, $template);

                if (!$tmp_tem) {
                    continue;
                }

                $original = $tmp_tem;

                // top level params
                $tmp_tem = $this->parseNodeComplete($tmp_tem, $repeat);

                $repeat->clear();

                $parses[$node_name][] = $tmp_tem;

                unset($original, $tmp_tem, $repeat, $node_name);
            }

            unset($repeats);

            $un = array_unique(array_keys($parses));

            foreach ($un as $template_name1) {
                $out = '';

                foreach ($parses as $name => $parsed) {
                    if ($template_name1 != $name) {
                        continue;
                    }

                    $out .= implode('', $parsed);

                    unset($name, $parsed);
                }

                $template = $this->insertHtmlNode($template_name1, $template, $out);

                unset($out, $template_name1);
            }

            unset($parses, $tmp_tem, $out_repeat, $un);
        }

        $template = $this->parseTemplate($template, $holder);

        unset($repeats);

        return $template;
    }

    protected function getHtmlNode($getwhat, $getfrom)
    {
        $getfrom = str_replace("\n", '[---n---]', $getfrom);

        $conditions = "/<!--\s*BEGIN\s*" . preg_quote($getwhat) . "\s*-->(.*)<!--\s*END\s*" . preg_quote($getwhat) . "\s*-->/";

        preg_match($conditions, $getfrom, $matches);

        if (!$matches) {
            return false;
        }

        $matches[1] = str_replace('[---n---]', "\n", $matches[1]);

        unset($getfrom);

        return $matches[1];
    }

    protected function insertHtmlNode($insertwhere, $ret, $content = null, $noinsert_ifempty = false)
    {
        if ((strlen($content) < 1) && (!$noinsert_ifempty)) {
            $content = $this->insertHtmlNode($insertwhere, $ret);
        }

        $ret = str_replace("\n", '[---n---]', $ret);
        $condition = "/<!--\s*BEGIN\s*" . preg_quote($insertwhere) . "\s*-->.*<!--\s*END\s*" . preg_quote($insertwhere) . "\s*-->/";
        $ret = preg_replace($condition, $content, $ret);
        $ret = str_replace('[---n---]', "\n", $ret);

        return $ret;
    }

    protected function parseTemplate($template, lcIterateParamHolder $node)
    {
        $this->tmp_node_params = $node->getParams();

        // parse node params
        // do not count on the number of params in $node_params
        // there may be other automatic params in the code as well!
        // we must parse it always!

        $self = $this;
        $tmp_node_params = $this->tmp_node_params;
        $template = preg_replace_callback("/\{[\$]([\w\d\s\:]+)\}/i", function ($m) use ($self, $tmp_node_params) {
            return $self->parseParam(@$m[1], $tmp_node_params);
        }, $template);

        $this->tmp_node_params = null;

        return $template;
    }

    public function parseParam($param, array $all_params = null)
    {
        assert(isset($param));

        $p = explode(':', $param);

        $param_default = '{$' . $param . '}';

        assert(isset($p[0]));

        $param_one = $p[0];
        $param_two = isset($p[1]) ? (string)$p[1] : null;

        // normal / asis
        if (!$param_two || $param_two == 'asis') {
            if (isset($all_params[$param_one])) {
                $param_one_set = isset($all_params[$param_one]) ? $all_params[$param_one] : null;
                $val = null;

                if (!is_array($param_one_set)) {
                    $val = !$param_two ? htmlspecialchars($param_one_set) : $param_one_set;
                    $val = $this->debug ? '{' . $val . '}' : $val;
                }

                return $val;
            } else {
                return $param_default;
            }
        } elseif ($param_two) {
            // check with a custom modifier method
            $internal_value = isset($all_params[$param_one]) ? $all_params[$param_one] : null;
            $value = $this->parsedParamValue($param_one, $param_two, $internal_value);
            $value = $this->debug ? '{' . $value . '}' : $value;

            return $value;
        }

        $param_default = $this->debug ? '{' . $param_default . '}' : $param_default;

        return $param_default;
    }

    public function parsedParamValue($name, $category, $default_value = null)
    {
        $ret = $default_value;

        // custom categories - which are handled by this class
        if ($category == 'keylink') {
            if ($ret) {
                $ret = lcStrings::keyLink($ret);
                return $ret;
            }
        } elseif ($category == 'seopath') {
            if ($ret) {
                $ret = preg_replace('/\s{2,}/', '', $ret);
                $ret = preg_replace('/\s{1,}/', '-', $ret);
                $ret = str_replace('/', '_', $ret);
                return $ret;
            }
        } elseif ($category == 'sp') {
            if ($ret) {
                $ret = htmlspecialchars($ret);
                return $ret;
            }
        } elseif ($category == '*') {
            return '*';
        } elseif ($category == 'urlencode' || $category == 'encode') {
            return urlencode($name);
        } elseif ($category == 'urldecode' || $category == 'decode') {
            return urldecode($name);
        } elseif ($category == 'env') {
            /** @var lcWebRequest $request */
            $request = $this->view->getController()->getRequest();
            $env = $request->getEnv();
            $value = strtoupper($name);
            $ret = isset($env[$value]) ? (string)$env[$value] : null;
            return $ret;
        } elseif ($category == 'config') {
            $config = $this->configuration;
            $r = $config[$name];
            return $r;
        } elseif ($category == 'controller') {
            $ctrl = $this->view->getController();

            if ($ctrl && ($ctrl instanceof iKeyValueProvider)) {
                $ret = $ctrl->getValueForKey($name);
                return $ret;
            }
        } else {
            // try to fetch from loaders - through the view's controller
            /** @var lcWebController $controller */
            $controller = $this->view->getController();

            $loaders = array(
                'request' => $controller->getRequest(),
                'response' => $controller->getResponse(),
                'routing' => $controller->getRouting(),
                'database_manager' => $controller->getDatabaseManager(),
                'storage' => $controller->getStorage(),
                'data_storage' => $controller->getDataStorage(),
                'cache' => $controller->getCache(),
                'mailer' => $controller->getMailer()
            );

            $loader = isset($loaders[$category]) ? $loaders[$category] : null;

            if ($loader && ($loader instanceof iKeyValueProvider)) {
                $ret = $loader->getValueForKey($name);
            }

            return $ret;
        }

        return null;
    }

    /*
     * Keep public for PHP 5.3 compatibility
     */

    protected function parseSubnodesCycle(lcIterateParamHolder $holder, $template)
    {
        // parse subnodes
        $subnodes = $holder->getNodes();

        if ($subnodes) {
            foreach ($subnodes as $subnode) {
                $node_name = $subnode->getNodeName();
                $tmp_tem = $this->getHtmlNode($node_name, $template);

                if (!$tmp_tem) {
                    continue;
                }

                $tmp_tem = $this->parseNodeComplete($tmp_tem, $subnode);

                $subnode->clear();

                $template = $this->insertHtmlNode($node_name, $template, $tmp_tem);

                unset($tmp_tem, $subnode, $node_name);
            }
        }

        // cleanup
        unset($subnodes);

        return $template;
    }

    protected function clearHtmlNode($text)
    {
        $text_prepared = str_replace("\n", '[---n---]', $text);

        preg_match_all("/<!--\s*BEGIN\s*(.*?)\s*-->/", $text_prepared, $matches);

        if (!$matches) {
            return $text;
        }

        $total_count = count($matches[1]);

        if (!$total_count) {
            return $text;
        }

        for ($i = 0; $i <= $total_count; $i++) {
            if (!isset($matches[1][$i])) {
                continue;
            }

            $conditions = "/<!--\s*BEGIN\s*" . preg_quote($matches[1][$i]) . " .*? END\s*" . preg_quote($matches[1][$i]) . "\s*-->/";
            $text_prepared = preg_replace($conditions, '', $text_prepared);
        }

        $text_prepared = str_replace('[---n---]', "\n", $text_prepared);

        return $text_prepared;
    }

    protected function postCompile($data)
    {
        // clear template
        $data = preg_replace("/\{\\$(.*?)\}/i", '', $data);

        // clear empty lines
        // TODO: Test the speed of this!
        //$data = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $data);

        return $data;
    }
}