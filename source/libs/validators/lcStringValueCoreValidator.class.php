<?php

class lcStringValueCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value is not a valid string');
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
        $max_length = isset($this->options['max_length']) ? (int)$this->options['max_length'] : 0;
        $min_length = isset($this->options['min_length']) ? (int)$this->options['min_length'] : 0;
        $alphanum_only = isset($this->options['alpha_numeric']) ? (int)$this->options['alpha_numeric'] : false;
        $allow_whitespace = isset($this->options['allow_whitespace']) ? (int)$this->options['allow_whitespace'] : false;

        // min length
        if ($min_length && lcUnicode::strlen($value) < $min_length) {
            return false;
        }

        // max length
        if ($max_length && lcUnicode::strlen($value) > $max_length) {
            return false;
        }

        // space
        if (!$allow_whitespace && lcUnicode::strpos($value, ' ') !== false) {
            return false;
        }

        // alpha numeric only
        if ($alphanum_only) {
            return (bool)preg_match('/^[\w\d' . ($allow_whitespace ? '\s' : '') . ']+$/', $value);
        }

        return true;
    }
}
