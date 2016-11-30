<?php

class propelYesNoValidator implements BasicValidator
{
    public function isValid(ValidatorMap $map, $str)
    {
        $valid_enums = array('yes', 'no');

        return $str && in_array($str, $valid_enums);
    }
}
