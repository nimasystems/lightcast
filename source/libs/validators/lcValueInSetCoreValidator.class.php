<?php

class lcValueInSetCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Invalid value');
    }

    protected function skipNullValues()
    {
        return true;
    }

    public function getDefaultOptions()
    {
        return array(
            'allow_null' => false
        );
    }

    protected function validateOptions()
    {
        return isset($this->options['value']) && is_array($this->options['value']) && $this->options['value'];
    }

    protected function doValidate($value = null)
    {
        return (($value && in_array($value, $this->options['value'])) ||
            (!$value && isset($this->options['allow_null']) && $this->options['allow_null']));
    }
}
