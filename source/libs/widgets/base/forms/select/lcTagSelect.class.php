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
 * Class lcTagSelect
 * @method lcTagSelect addClass($class_name)
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

    /**
     * @return lcTagSelect
     */
    public static function create()
    {
        return new lcTagSelect();
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    /**
     * @param string $placeholder
     * @return $this
     */
    public function setPlaceholder($placeholder = null)
    {
        return $this->attr('placeholder', $placeholder);
    }

    /*
     * Can be option or option group
    */

    public function setSize($value = null)
    {
        $this->setAttribute('size', $value);
        return $this;
    }

    public function setIsMultiple($value = false)
    {
        $this->setAttribute('multiple', $value ? 'multiple' : null);
        return $this;
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return [];
    }

    public static function getOptionalAttributes()
    {
        return ['name', 'size', 'multiply', 'disabled', 'tabindex'];
    }

    /**
     * @param array $options
     * @param string $key_identifier
     * @param string $value_identifier
     * @return $this
     * @throws lcInvalidArgumentException
     */
    public function setOptions(array $options, $key_identifier = 'key', $value_identifier = 'value')
    {
        foreach ($options as $option) {

            if ($option instanceof lcTagOption ||
                $option instanceof lcOptGroup
            ) {
                $this->addOption($option);
            } elseif (is_array($option)) {
                if (isset($option[$key_identifier]) &&
                    isset($option[$value_identifier])
                ) {
                    $this->addOption(lcTagOption::create()
                        ->setValue($option[$key_identifier])
                        ->setContent($option[$value_identifier]));
                }
            }

            unset($option);
        }

        return $this;
    }

    /**
     * @param lcTagOption|lcOptGroup $option
     * @return $this
     * @throws lcInvalidArgumentException
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

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getSize()
    {
        return $this->getAttribute('size');
    }

    public function getIsMultiple()
    {
        return $this->getAttribute('multiple') ? true : false;
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }

    public function asHtml()
    {
        $options_html = null;

        if ($this->options) {
            $all_rendered = [];

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
