<?php

class lcValueMatchesCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Value is not allowed');
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
        return isset($this->options['value']) && $this->options['value'];
    }

    /*private function prepareRegexp($exp)
    {
        // remove surrounding '/' marks so that they don't get escaped in next step
        if ($exp{0} !== '/' || $exp{strlen($exp) - 1} !== '/') {
            $exp = '/' . $exp . '/';
        }

        // if they did not escape / chars; we do that for them
        $exp = preg_replace('/([^\\\])\/([^$])/', '$1\/$2', $exp);

        return $exp;
    }*/

    protected function doValidate($value = null)
    {
        //return (preg_match($this->prepareRegexp($this->options['value']), $value) != 0);
        return (preg_match($this->options['value'], $value) != 0);
    }
}