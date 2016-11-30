<?php

class lcUsernameCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Username is not valid');
    }

    protected function skipNullValues()
    {
        return true;
    }

    public function getDefaultOptions()
    {
        return array(
            'min_length' => 6,
            'max_length' => 30,
            'allowed_symbols' => '_.'
        );
    }

    protected function validateOptions()
    {
        return true;
    }

    private function prepareRegexp($exp)
    {
        // if they did not escape / chars; we do that for them
        return preg_replace('/([^\\\])\/([^$])/', '$1\/$2', $exp);
    }

    protected function doValidate($value = null)
    {
        if (!$value) {
            return false;
        }

        $min_length = isset($this->options['min_length']) ? (int)$this->options['min_length'] : null;
        $max_length = isset($this->options['max_length']) ? (int)$this->options['max_length'] : null;

        $min_length_valid = !$min_length || strlen($value) >= $min_length;
        $max_length_valid = !$max_length || strlen($value) <= $max_length;

        $allowed_symbols = isset($this->options['allowed_symbols']) ? (string)$this->options['allowed_symbols'] : '';
        $allowed_symbols = $allowed_symbols ? $this->prepareRegexp($allowed_symbols) : '';
        $regex = '/^[a-zA-Z0-9' . $allowed_symbols . "]+$/";
        $valid_symbols = preg_match($regex, $value);

        $ret = $min_length_valid &&
            $max_length_valid &&
            $valid_symbols;

        return $ret;
    }
}