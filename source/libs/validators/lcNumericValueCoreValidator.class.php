<?php

class lcNumericValueCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value is not numeric');
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
    }

    protected function doValidate($value = null)
    {
        return (bool)preg_match("/^\d*$/", $value);
    }
}
