<?php

class lcUriCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value is not a valid URI');
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
        return
            $value && preg_match("/(((ht|f)tps*:\/\/)*)((([a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6}))|(([0-9]{1,6}\.){4}([0-9]{1,6})))((\/|\?)[a-z0-9~#%&'_+=:?.-]*)*)$/",
                $value);
    }
}
