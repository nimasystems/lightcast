<?php

class lcIdenticalCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Field is not identical');
    }

    protected function skipNullValues()
    {
        return true;
    }

    public function getDefaultOptions()
    {
    }

    protected function validateOptions()
    {
        return true;
    }

    protected function doValidate($value = null)
    {
        $options = $this->getOptions();
        $field_name = isset($options['field_name']) ? $options['field_name'] : null;
        $request = isset($options['request']) ? $options['request'] : null;
        $field_value = isset($request[$field_name]) ? $request[$field_name] : null;

        return $field_value === $value;
    }
}
