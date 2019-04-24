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

    /** @var array */
    protected $additional_tag_attributes;

    protected $data;
    protected $data_alias;

    protected $init_javascript;

    /** @var lcBaseActionFormValidator[] */
    protected $validators;

    protected $validation_rules;

    /** @var lcBaseActionFormValidationFailure[] */
    protected $validation_failures;

    protected $is_validated;
    protected $is_valid;

    protected $priority;

    protected $label_shown_after;

    protected $default_class;

    protected $title;

    protected $ui_description;
    protected $ui_requirements;

    protected $placeholder;

    protected $default_value;

    /** @var array|null */
    protected $constraints;

    protected $is_disabled;
    protected $is_hidden;
    protected $is_required;
    protected $is_read_only;
    protected $is_numeric;

    protected $id_prefix;
    protected $name_suffix;
    protected $name_prefix;

    protected $container_name;
    protected $field_tag_id;
    protected $field_tag_id_prefix;

    public function __construct()
    {
        parent::__construct();

        $this->validation_failures = [];
        $this->validators = [];
    }

    abstract public function render();

    public function initialize()
    {
        //
    }

    public function getIsUserControl()
    {
        return true;
    }

    /**
     * @return string|null
     */
    public function getLabelClass()
    {
        //
    }

    public function getFieldClassesMerged(array $additional_classes = null)
    {
        $classes = implode(' ', array_filter(array_merge([$this->getFieldClass(), $this->default_class], (array)$additional_classes)));
        return $classes;
    }

    /**
     * @return string|null
     */
    public function getFieldClass()
    {
        //
    }

    public function getDataAlias()
    {
        return $this->data_alias;
    }

    public function setDataAlias($alias)
    {
        $this->data_alias = $alias;
        return $this;
    }

    public function getLabelShownAfter()
    {
        return $this->label_shown_after;
    }

    public function setLabelShownAfter($after)
    {
        $this->label_shown_after = $after;
        return $this;
    }

    public function getIdPrefix()
    {
        return $this->id_prefix;
    }

    public function setIdPrefix($prefix)
    {
        $this->id_prefix = $prefix;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getValidationRules()
    {
        return $this->validation_rules;
    }

    public function setValidationRules($rules)
    {
        $this->validation_rules = $rules;
    }

    public function getDefaultClass()
    {
        return $this->default_class;
    }

    public function setDefaultClass($class)
    {
        $this->default_class = $class;
        return $this;
    }

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default_value;
    }

    /**
     * @param null $default_value
     * @return lcBaseActionFormWidget
     */
    public function setDefaultValue($default_value = null)
    {
        $this->default_value = $default_value;
        return $this;
    }

    public function getUIDescription()
    {
        return $this->ui_description;
    }

    public function setUIDescription($description)
    {
        $this->ui_description = $description;
        return $this;
    }

    public function getUIRequirements()
    {
        return $this->ui_requirements;
    }

    public function setUIRequirements($requirements)
    {
        $this->ui_requirements = $requirements;
        return $this;
    }

    public function getOnInitJavascript()
    {
        return $this->init_javascript;
    }

    public function setOnInitJavascript($javascript)
    {
        $this->init_javascript = $javascript;
        return $this;
    }

    public function getActionForm()
    {
        return $this->action_form;
    }

    public function setActionForm(lcActionForm $form)
    {
        $this->action_form = $form;
        return $this;
    }

    public function getConstraints()
    {
        return $this->constraints;
    }

    public function setConstraints(array $constraints)
    {
        $this->constraints = $constraints;
        return $this;
    }

    public function addConstraint($constraint)
    {
        $this->constraints[] = $constraint;
        return $this;
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

    public function getContainer()
    {
        return $this->container_name;
    }

    public function getContainerFormName()
    {
        return $this->action_form ? $this->action_form->getName() : null;
    }

    public function getNamePrefix()
    {
        return $this->name_prefix;
    }

    public function setNamePrefix($prefix)
    {
        $this->name_prefix = $prefix;
        return $this;
    }

    public function getNameSuffix()
    {
        return $this->name_suffix;
    }

    public function setNameSuffix($name_suffix)
    {
        $this->name_suffix = $name_suffix;
        return $this;
    }

    public function getFieldName()
    {
        return $this->field_name;
    }

    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;
        return $this;
    }

    public function setAttributes(array $attributes)
    {
        $this->additional_tag_attributes = $attributes;
    }

    public function setAttribute($key, $value)
    {
        $this->additional_tag_attributes[$key] = $value;
    }

    public function setContainerName($container_name)
    {
        $this->container_name = $container_name;
        return $this;
    }

    public function getFieldTagId()
    {
        return $this->field_tag_id;
    }

    public function setFieldTagId($field_id)
    {
        $this->field_tag_id = $field_id;
        return $this;
    }

    public function getFieldTagIdPrefix()
    {
        return $this->field_tag_id_prefix;
    }

    public function setFieldTagIdPrefix($id_prefix)
    {
        $this->field_tag_id_prefix = $id_prefix;
        return $this;
    }

    public function getFieldId()
    {
        $container_prefix = $this->container_name ? strtolower($this->container_name) . '_' : null;
        $id = ($this->field_tag_id ? (string)$this->field_tag_id : ($this->field_tag_id_prefix ? $this->field_tag_id_prefix : null)) .
            $container_prefix . $this->getFieldName();
        return $id;
    }

    public function getIsReadOnly()
    {
        return $this->is_read_only;
    }

    public function setIsReadOnly($read_only)
    {
        $this->is_read_only = $read_only;
        return $this;
    }

    public function getIsEnabled()
    {
        return !$this->getIsDisabled();
    }

    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    public function setIsDisabled($is_disabled)
    {
        $this->is_disabled = $is_disabled;
        return $this;
    }

    public function getIsHidden()
    {
        return $this->is_hidden;
    }

    public function setIsHidden($is_hidden)
    {
        $this->is_hidden = $is_hidden;
        return $this;
    }

    public function getIsNumeric()
    {
        return $this->is_numeric;
    }

    public function setIsNumeric($is_numeric)
    {
        $this->is_numeric = $is_numeric;
        return $this;
    }

    public function getIsRequired()
    {
        return $this->is_required;
    }

    public function setIsRequired($is_required)
    {
        $this->is_required = $is_required;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    public function getRawData()
    {
        return $this->getData();
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data = null)
    {
        $this->data = $data;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options = null)
    {
        $this->options = $options;

        if ($options) {
            $this->parseOptions($options);
        }

        return $this;
    }

    protected function parseOptions(array $options)
    {
        // parse the default options
        if (isset($options['name_suffix'])) {
            $this->name_suffix = $options['name_suffix'];
        }

        if (isset($options['class'])) {
            $this->default_class = $options['class'];
        }

        if (isset($options['id_prefix'])) {
            $this->id_prefix = $options['id_prefix'];
        }

        if (isset($options['name_prefix'])) {
            $this->name_prefix = $options['name_prefix'];
        }

        if (isset($options['help']['description'])) {
            $this->ui_description = $options['help']['description'];
        }

        if (isset($options['help']['requirements'])) {
            $this->ui_requirements = $options['help']['requirements'];
        }

        if (isset($options['placeholder'])) {
            $this->placeholder = $options['placeholder'];
        }

        if (isset($options['default_value'])) {
            $this->default_value = $options['default_value'];
        }

        if (isset($options['container'])) {
            $this->container_name = $options['container'];
        }

        if (isset($options['constraints'])) {
            $this->constraints = $options['constraints'];
        }

        if (isset($options['disabled'])) {
            $this->is_disabled = $options['disabled'];
        }

        if (isset($options['readonly'])) {
            $this->is_read_only = $options['readonly'];
        }

        if (isset($options['hidden'])) {
            $this->is_hidden = $options['hidden'];
        }

        if (isset($options['required'])) {
            $this->is_required = $options['required'];
        }

        if (isset($options['priority'])) {
            $this->priority = $options['priority'];
        }

        if (isset($options['id'])) {
            $this->field_tag_id = $options['id'];
        }

        if (isset($options['validation'])) {
            $this->validation_rules = $options['validation'];
        }

        if (isset($options['data_alias'])) {
            $this->data_alias = $options['data_alias'];
        }

        if (isset($options['title'])) {
            $this->title = $options['title'];
        }

        if (isset($options['numeric'])) {
            $this->is_numeric = (bool)$options['numeric'];
        }

        return $this;
    }

    public function getValue()
    {
        return $this->data;
    }

    public function validate()
    {
        $this->clearValidationFailures();

        $validators = $this->validators;

        $is_valid = true;
        $validation_failures = [];

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

    public function clearValidationFailures()
    {
        $this->validation_failures = [];
        $this->is_valid = false;
        $this->is_validated = false;
        return $this;
    }

    public function isValid()
    {
        return $this->is_valid;
    }

    #pragma mark - Validation

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
        return $this;
    }

    public function getValidators()
    {
        return $this->validators;
    }

    public function addValidator(lcBaseActionFormValidator $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function removeValidators()
    {
        $this->validators = [];
        $this->clearValidationFailures();
        return $this;
    }

    public function __toString()
    {
        return (!is_array($this->data) ? $this->data : '');
    }

    protected function addAdditionalAttributesToTag(lcHtmlTag $tag)
    {
        $attrs = $this->getAttributes();

        if (!$attrs) {
            return false;
        }

        foreach ($attrs as $name => $value) {
            $tag->setAttribute($name, $value);
            unset($name, $value);
        }

        return $this;
    }

    public function getAttributes()
    {
        return array_merge((array)$this->additional_tag_attributes,
            (isset($this->options['attributes']) ? (array)$this->options['attributes'] : []));
    }

    protected function getContainerNameForForm()
    {
        return strtolower($this->getContainer());
    }

    protected function t($string)
    {
        return $this->action_form ? $this->action_form->translate($string) : $string;
    }
}
