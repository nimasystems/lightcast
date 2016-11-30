<?php

class lcMaxValueCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value is larger than the maximum allowed');
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
        return isset($this->options['value']);
    }

    protected function doValidate($value = null)
    {
        return $this->options['value'] && strlen($value) <= $this->options['value'];
    }
}
