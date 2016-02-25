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

class lcConsoleResponse extends lcResponse
{
    /**
     * @var lcConsoleRequest
     */
    protected $request;

    protected $exit_upon_send;

    private $content;

    private $output_formatters = array(
        'fgcolor' => '_outputFormatFgColor',
        'bgcolor' => '_outputFormatBgColor'
    );

    private $compiled_output_modifiers = array();
    private $compiled_output_modifier_methods = array();

    public function initialize()
    {
        parent::initialize();

        $this->exit_upon_send = true;

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

        $this->compileOutputModifiers();
    }

    private function compileOutputModifiers()
    {
        if (!$this->output_formatters) {
            return;
        }

        $formatters = $this->output_formatters;

        $modifiers = array();
        $methods = array();

        foreach ($formatters as $key => $method) {
            $pr = preg_quote($key);
            $modifiers[] = "/{" . $pr . "\:(.*?)}(.*?){\/" . $pr . "}/i";
            $methods[] = $method;

            unset($key, $method, $pr);
        }

        $this->compiled_output_modifiers = $modifiers;
        $this->compiled_output_modifier_methods = $methods;
    }

    public function shutdown()
    {
        $this->request = null;

        parent::shutdown();
    }

    public function consoleDisplay($data, $prefixed = true, $return = false)
    {
        $data = ($prefixed ? (lcConsolePainter::formatColoredConsoleText(date('Y-m-d H:i:s') . ' >> ', 'yellow')) : null) . $data . "\n";

        if ($return) {
            return $this->formatOutput($data);
        } else {
            $request = $this->request;

            if ($request->getIsSilent()) {
                return false;
            }

            echo $this->formatOutput($data);
        }

        return null;
    }

    protected function formatOutput($output)
    {
        if (!$output || !$this->compiled_output_modifiers) {
            return false;
        }

        // TODO: Make this dynamic and allow other classes to registed and add/remove output formatters

        $self = $this;
        $compiled_output_modifier_methods = $this->compiled_output_modifier_methods;
        $output = preg_replace_callback($this->compiled_output_modifiers, function ($m) use ($self, $compiled_output_modifier_methods) {

            if ($compiled_output_modifier_methods) {

                $ret = null;

                foreach ($compiled_output_modifier_methods as $func) {

                    $ret = $self->$func(@$m[1], @$m[2]);

                    unset($m);
                }

                return $ret;
            }

            return null;
        }, $output);
        $this->compiled_output_modifier_methods = $compiled_output_modifier_methods;

        return $output;
    }

    /*
     * Keep public for PHP 5.3 compatibility
     */
    public function _outputFormatBgColor($color, $string)
    {
        return lcConsolePainter::formatColoredConsoleText($string, 'white', $color);
    }

    /*
     * Keep public for PHP 5.3 compatibility
     */
    public function _outputFormatFgColor($color, $string)
    {
        return lcConsolePainter::formatColoredConsoleText($string, $color);
    }

    /*
     * Get the current Response content
    */
    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getOutputContent()
    {
        return $this->content;
    }

    public function clear()
    {
        $this->content = null;
        $this->exit_code = 0;
    }

    // for compatibility

    public function setContentType($content_type)
    {
        //
    }

    public function setShouldExitUponSend($do_exit = true)
    {
        $this->exit_upon_send = (bool)$do_exit;
    }

    public function sendResponse()
    {
        if ($this->response_sent) {
            return;
        }

        // content output
        $should_be_silent = (bool)$this->request->getIsSilent();

        if (!$should_be_silent) {
            echo $this->formatOutput($this->content);
        }

        $this->response_sent = true;

        if ($this->exit_upon_send) {
            exit($this->exit_code);
        }
    }
}