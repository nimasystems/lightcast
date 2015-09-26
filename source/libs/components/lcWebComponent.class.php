<?php

/*
 * Lightcast - A PHP MVC Framework Copyright (C) 2005 Nimasystems Ltd This program is NOT free
 * software; you cannot redistribute and/or modify it's sources under any circumstances without the
 * explicit knowledge and agreement of the rightful owner of the software - Nimasystems Ltd. This
 * program is distributed WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the LICENSE.txt file for more information. You should
 * have received a copy of LICENSE.txt file along with this program; if not, write to: NIMASYSTEMS
 * LTD Plovdiv, Bulgaria ZIP Code: 4000 Address: 95 "Kapitan Raycho" Str. E-Mail:
 * info@nimasystems.com
 */

abstract class lcWebComponent extends lcComponent
{
    /**
     * @var lcWebController
     */
    protected $controller;

    protected $required_js_includes;
    protected $required_css_includes;

    protected $required_javascript_code;

    private function getRandomIdentifier()
    {
        return 'anon_' . $this->getControllerName() . '_' . lcStrings::randomString(15);
    }

    protected function addJavascriptInclude($location, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_js_includes[$identifier] = $location;
    }

    protected function addCssInclude($location, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_css_includes[$identifier] = $location;
    }

    protected function addJavascriptCode($code, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_javascript_code[$identifier] = $code;
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

    public function renderJavascriptCode()
    {
        $code = $this->required_javascript_code;

        if ($code) {
            return '<script>' . implode("\n", array_values($code)) . '</script>';
        }

        return null;
    }
}
