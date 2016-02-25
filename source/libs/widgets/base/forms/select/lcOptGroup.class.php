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

class lcOptGroup extends lcHtmlTag
{
    /** @var lcTagOption[] */
    private $options;

    public function __construct($label, $content = null, $disabled = null)
    {
        parent::__construct('optgroup', true);

        $this->setContent($content);
        $this->setLabel($label);
        $this->setIsDisabled($disabled);
    }

    public static function create($label)
    {
        return new lcOptGroup($label);
    }

    public function setLabel($value = null)
    {
        $this->setAttribute('label', $value);
        return $this;
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('label');
    }

    public static function getOptionalAttributes()
    {
        return array('disabled');
    }

    public function addOption(lcTagOption $option)
    {
        $this->options[] = $option;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function clearOptions()
    {
        $this->options = null;
        return $this;
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function getLabel()
    {
        return $this->getAttribute('label');
    }

    public function asHtml()
    {
        $options_html = null;

        if (!$this->content && $this->options) {
            $all_rendered = array();

            foreach ($this->options as $opt) {
                $all_rendered[] = $opt->asHtml();
                unset($opt);
            }

            $options_html = implode("\n", $all_rendered);
        }

        $this->setContent($options_html);

        return parent::asHtml();
    }
}
