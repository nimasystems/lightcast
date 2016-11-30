<?php

class lcEqualsCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Values are not equal');
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
        return isset($this->options['value']);
    }

    protected function doValidate($value = null)
    {
        return (($value && (string)$value == (string)$this->options['value']) ||
            (!$value && isset($this->options['allow_null']) && $this->options['allow_null']));
    }
}
