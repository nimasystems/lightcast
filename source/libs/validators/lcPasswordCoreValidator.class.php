<?php

class lcPasswordCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Password is not valid');
    }

    protected function skipNullValues()
    {
        return true;
    }

    public function getDefaultOptions()
    {
        return array(
            'min_length' => 8,
            'max_length' => 30,
            'min_uppercase_symbols' => 1,
            'min_lowercase_symbols' => 1,
            'min_numbers' => 1,
        );
    }

    protected function validateOptions()
    {
        return true;
    }

    protected function doValidate($value = null)
    {
        if (!$value) {
            return false;
        }

        $min_length = isset($this->options['min_length']) ? (int)$this->options['min_length'] : null;
        $max_length = isset($this->options['max_length']) ? (int)$this->options['max_length'] : null;
        $min_uppercase_symbols = isset($this->options['min_uppercase_symbols']) ? (int)$this->options['min_uppercase_symbols'] : null;
        $min_lowercase_symbols = isset($this->options['min_lowercase_symbols']) ? (int)$this->options['min_lowercase_symbols'] : null;
        $min_special_symbols = isset($this->options['min_special_symbols']) ? (int)$this->options['min_special_symbols'] : null;
        $min_numbers = isset($this->options['min_numbers']) ? (int)$this->options['min_numbers'] : null;
        $min_letters = isset($this->options['min_letters']) ? (int)$this->options['min_letters'] : null;

        $tmp = null;

        $min_length_valid = !$min_length || strlen($value) >= $min_length;
        $max_length_valid = !$max_length || strlen($value) <= $max_length;
        $min_uppercase_symbols_valid = !$min_uppercase_symbols || (preg_match_all('/[A-Z]/', $value, $tmp) >= $min_uppercase_symbols);
        $min_lowercase_symbols = !$min_lowercase_symbols || (preg_match_all('/[a-z]/', $value, $tmp) >= $min_lowercase_symbols);
        $min_special_symbols = !$min_special_symbols || (preg_match_all("/[!@#$%^&*()\-_=+{};:,<.>]/", $value, $tmp) >= $min_special_symbols);
        $min_numbers = !$min_numbers || (preg_match_all('/\\d/', $value, $tmp) >= $min_numbers);
        $min_letters = !$min_letters || (preg_match_all('/[a-zA-Z]/', $value, $tmp) >= $min_letters);

        $ret = $min_length_valid &&
            $max_length_valid &&
            $min_uppercase_symbols_valid &&
            $min_lowercase_symbols &&
            $min_special_symbols &&
            $min_numbers &&
            $min_letters;

        return $ret;
    }
}
