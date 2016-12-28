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
        return is_array($value) && isset($value[0]) && isset($value[1]) && $value[0] === $value[1];
    }
}
