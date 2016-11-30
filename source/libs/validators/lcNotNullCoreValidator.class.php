<?php

class lcNotNullCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value cannot be null');
    }

    protected function skipNullValues()
    {
        return false;
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
        return null !== $value && $value;
    }
}
