<?php

class propelPhoneValidator implements BasicValidator
{
    public function isValid(ValidatorMap $map, $str)
    {
        return lcCoreValidator::validateValue('phone_number', $str);
    }
}
