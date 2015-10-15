<?php

/**
 * class lcBaseActionFormWidget
 *
 * Lightcast - A Complete MVC/PHP/XSLT based Framework
 * Copyright (C) 2005-2008 Nimasystems Ltd
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
 * General E-Mail: info@nimasystems.com
 *
 * $HeadURL: https://svn.nimasystems.com/ogledai-web/trunk/addons/plugins/forms/lib/action_forms/schema/lcBaseActionFormWidget.class.php $
 * $Revision: 443 $
 * $Author: mkovachev $
 * $Date: 2014-05-17 17:22:03 +0300 (Сб , 17 Май 2014) $
 * $Id: lcBaseActionFormWidget.class.php 443 2014-05-17 14:22:03Z mkovachev $
 *
 * @defgroup AdminLayout
 *
 */
abstract class lcBaseActionFormWidget extends lcObj
{
    /** @var lcActionForm */
    protected $action_form;

    protected $field_name;

    /** @var array */
    protected $options;

    protected $data;

    protected $init_javascript;

    /** @var lcBaseActionFormValidator[] */
    protected $validators;

    /** @var lcBaseActionFormValidationFailure[] */
    protected $validation_failures;

    protected $is_validated;
    protected $is_valid;

    protected $priority;

    protected $label_shown_after;

    abstract public function render();

    abstract public function getLabelClass();

    abstract public function getFieldClass();

    abstract public function getIsUserControl();

    public function __construct()
    {
        parent::__construct();

        $this->validation_failures = array();
        $this->validators = array();
    }

    public function getDefaultClass(array $additional_classes = null)
    {
        $class_name = isset($this->options['class']) ? (string)$this->options['class'] : null;
        $classes = implode(' ', array_filter(array_merge(array($this->getFieldClass(), $class_name), (array)$additional_classes)));
        return $classes;
    }

    public function setLabelShownAfter($after)
    {
        $this->label_shown_after = $after;
    }

    public function getLabelShownAfter()
    {
        return $this->label_shown_after;
    }

    public function getIdPrefix()
    {
        return (isset($this->options['id_prefix']) ? $this->options['id_prefix'] : null);
    }

    public function getNamePrefix()
    {
        return (isset($this->options['name_prefix']) ? $this->options['name_prefix'] : null);
    }

    public function getUIDescription()
    {
        return (isset($this->options['help']['description']) ? $this->options['help']['description'] : null);
    }

    public function getUIRequirements()
    {
        return (isset($this->options['help']['requirements']) ? $this->options['help']['requirements'] : null);
    }

    public function getPlaceholder()
    {
        return (isset($this->options['placeholder']) ? $this->options['placeholder'] : null);
    }

    public function getDefaultValue()
    {
        return (isset($this->options['default_value']) ? $this->options['default_value'] : null);
    }

    public function getOnInitJavascript()
    {
        return $this->init_javascript;
    }

    public function setOnInitJavascript($javascript)
    {
        $this->init_javascript = $javascript;
    }

    public function getContainer()
    {
        return (isset($this->options['container']) ? $this->options['container'] : null);
    }

    public function setActionForm(lcActionForm $form)
    {
        $this->action_form = $form;
    }

    public function getActionForm()
    {
        return $this->action_form;
    }

    public function getContainerFormName()
    {
        return $this->action_form ? $this->action_form->getName() : null;
    }

    public function getConstraints()
    {
        return (isset($this->options['constraints']) ? (array)$this->options['constraints'] : null);
    }

    public function getAdditionalAttributes()
    {
        return (isset($this->options['attributes']) ? $this->options['attributes'] : null);
    }

    public function getNameSuffix()
    {
        return (isset($this->options['name_suffix']) ? $this->options['name_suffix'] : null);
    }

    public function getPrefixedFieldName()
    {
        $container = $this->getContainer();
        $container_form_name = $this->getContainerFormName();
        $field_is_prefixed = ($container || $container_form_name && !($this->getNamePrefix() || $this->getNameSuffix()));

        $full_name = $container_form_name .
            ($container ? '[' . $container . ']' : null) .
            ($this->getNamePrefix() .
                ($field_is_prefixed ? '[' : null) .
                $this->getFieldName() .
                ($field_is_prefixed ? ']' : null) .
                $this->getNameSuffix());
        return $full_name;
    }

    protected function addAdditionalAttributesToTag(lcHtmlTag $tag)
    {
        $attrs = $this->getAdditionalAttributes();

        if (!$attrs) {
            return false;
        }

        foreach ($attrs as $name => $value) {
            $tag->setAttribute($name, $value);
            unset($name, $value);
        }

        return true;
    }

    public function getFieldId()
    {
        $options = $this->options;
        $container_prefix = isset($options['container']) ? /*&& isset($options['append_container_to_field_ids']) && $options['append_container_to_field_ids'] ?*/
            strtolower($options['container']) . '_' : null;
        $id = isset($options['id']) ? (string)$options['id'] : (isset($options['id_prefix']) ? $options['id_prefix'] : null) . $container_prefix . $this->getFieldName();
        return $id;
    }

    public function getIsDisabled()
    {
        return (isset($this->options['disabled']) && $this->options['disabled']);
    }

    public function getIsEnabled()
    {
        return !$this->getIsDisabled();
    }

    public function getIsHidden()
    {
        return (isset($this->options['hidden']) && $this->options['hidden']);
    }

    public function getIsRequired()
    {
        return (isset($this->options['required']) && $this->options['required']);
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        if ($this->priority) {
            return $this->priority;
        }

        return (isset($this->options['priority']) ? (int)$this->options['priority'] : null);
    }

    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;
    }

    public function getFieldName()
    {
        return $this->field_name;
    }

    public function setOptions(array $options = null)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setData($data = null)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getValue()
    {
        return $this->data;
    }

    #pragma mark - Validation

    public function validate()
    {
        $this->clearValidationFailures();

        $validators = $this->validators;

        $is_valid = true;
        $validation_failures = array();

        if ($validators) {
            $data = $this->getData();

            foreach ($validators as $validator) {
                $validator_result = $validator->validate($data);

                if (!$validator_result) {
                    $is_valid = false;
                    $failure = new lcBaseActionFormValidationFailure($this->field_name, $validator->getDefaultErrorMessage());
                    $validation_failures[] = $failure;
                }

                unset($validator);
            }
        }

        $this->is_valid = $is_valid;
        $this->is_validated = true;
        $this->validation_failures = $validation_failures;

        return $is_valid;
    }

    public function isValid()
    {
        return $this->is_valid;
    }

    public function isValidated()
    {
        return $this->is_validated;
    }

    public function getValidationFailures()
    {
        return $this->validation_failures;
    }

    public function addValidationFailure($message)
    {
        $this->is_valid = false;
        $this->validation_failures[] = new lcBaseActionFormValidationFailure($this->field_name, $message);
    }

    public function getValidators()
    {
        return $this->validators;
    }

    public function addValidator(lcBaseActionFormValidator $validator)
    {
        $this->validators[] = $validator;
    }

    public function removeValidators()
    {
        $this->validators = array();
        $this->clearValidationFailures();
    }

    public function clearValidationFailures()
    {
        $this->validation_failures = array();
        $this->is_valid = false;
        $this->is_validated = false;
    }

    protected function t($string)
    {
        return $this->action_form ? $this->action_form->translate($string) : $string;
    }

    public function __toString()
    {
        return (!is_array($this->data) ? $this->data : '');
    }
}
