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

    private $included_javascripts = [];
    private $included_stylesheets = [];

    private $required_javascript_code = [];

    public function getRequiredJavascriptCode()
    {
        return $this->required_javascript_code;
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

    protected function includeStylesheet($src, array $options = null, $tag = null)
    {
        $tag = $tag ?: 'css_' . $this->getRandomIdentifier();
        $this->included_stylesheets[$tag] = [
            'src' => $src,
            'options' => $options,
        ];
        return $this;
    }

    public function renderJavascriptCode()
    {
        $code = $this->required_javascript_code;

        if ($code) {
            return lcTagScript::create()
                ->setContent(implode("\n", array_values($code)))
                ->toString();
        }

        return null;
    }

    protected function getRandomIdentifier()
    {
        return 'component_' . $this->getControllerName() . '_' . $this->getClassName() . '_' . lcStrings::randomString(15);
    }

    protected function addJavascriptCode($code, $identifier = null)
    {
        $identifier = $identifier ? $identifier : $this->getRandomIdentifier();
        $this->required_javascript_code[$identifier] = $code;
    }
}
