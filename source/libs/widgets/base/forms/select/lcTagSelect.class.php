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
 * @changed $Id: lcTagSelect.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagSelect extends lcHtmlTag
{
    /** @var lcTagOption[] */
    protected $options;

    public function __construct($name = null, $id = null, $size = null, $disabled = false, $multiple = false, $tabindex = false, $content = null)
    {
        parent::__construct('select', true);

        $this->setContent($content);
        $this->setId($id);
        $this->setName($name);
        $this->setSize($size);
        $this->setIsMultiple($multiple);
        $this->setIsDisabled($disabled);
        $this->setTabIndex($tabindex);
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    /*
     * Can be option or option group
    */
    public function addOption($option)
    {
        if ((!$option instanceof lcTagOption) && (!$option instanceof lcOptGroup)) {
            throw new lcInvalidArgumentException('Select options can be either \'option\' or \'option group\'');
        }

        $this->options[] = $option;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($id)
    {
        if (!$this->options) {
            return null;
        }

        foreach ($this->options as $option) {
            if (strcmp($option->getValue(), $id) == 0) {
                return $option;
            }

            unset($option);
        }

        return null;
    }

    public function clearOptions()
    {
        $this->options = null;
        return $this;
    }

    public static function getOptionalAttributes()
    {
        return array('name', 'size', 'multiply', 'disabled', 'tabindex');
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function setSize($value = null)
    {
        $this->setAttribute('size', $value);
        return $this;
    }

    public function getSize()
    {
        return $this->getAttribute('size');
    }

    public function setIsMultiple($value = false)
    {
        $this->setAttribute('multiple', $value ? 'multiple' : null);
        return $this;
    }

    public function getIsMultiple()
    {
        return $this->getAttribute('multiple') ? true : false;
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }

    public function asHtml()
    {
        $options_html = null;

        if ($this->options) {
            $all_rendered = array();

            foreach ($this->options as $opt) {
                $all_rendered[] = $opt->asHtml();
                unset($opt);
            }

            $options_html = implode("\n", $all_rendered);
        }

        return
            '<select ' . $this->getAttributes()->asHtml() . '>' . "\n" .
            $options_html . "\n" .
            '</select>';
    }
}
