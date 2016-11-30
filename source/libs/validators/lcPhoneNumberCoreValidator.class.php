<?php

class lcPhoneNumberCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Invalid Phone');
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
        return $value && (bool)preg_match("/^(\+)?(\d)+$/", $value);
    }
}
