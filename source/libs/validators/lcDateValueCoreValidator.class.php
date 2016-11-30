<?php

class lcDateValueCoreValidator extends lcCoreValidator
{
    public function initialize()
    {
        parent::initialize();

        $this->default_error_message = $this->default_error_message ?: $this->translate('Invalid Date');
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
        if (!$value) {
            return false;
        }

        // check if we have time in the date also - remove it
        $tmp = array_filter(explode(' ', $value));

        $str = null;

        if (count($tmp)) {
            $str = $tmp[0];
        }

        if (!$str) {
            return false;
        }

        $match = array();

        if (preg_match('/^([\d]+){1,4}[-|\/]([\d]+){1,2}[-|\/]([\d]+){1,4}/', $str, $match)) {
            // check if we have year at the first position and swap it with the last
            if ($match[1] > 31) {
                $tmp = $match[3];
                $match[3] = $match[1];
                $match[1] = $tmp;
            }

            // check if the first position is not a month - swap it with the second
            if ($match[1] > 12) {
                $str = $match[2] . '/' . $match[1] . '/' . $match[3];
            } else {
                $str = $match[1] . '/' . $match[2] . '/' . $match[3];
            }
        } else {
            return false;
        }

        if (!$stamp = strtotime($str)) {
            return false;
        }

        $m = date('m', $stamp);
        $d = date('d', $stamp);
        $y = date('Y', $stamp);

        return (bool)checkdate($m, $d, $y);
    }
}
