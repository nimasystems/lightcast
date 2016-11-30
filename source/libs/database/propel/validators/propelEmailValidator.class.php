<?php

class propelEmailValidator implements BasicValidator
{
    public function isValid(ValidatorMap $map, $str)
    {
        return lcCoreValidator::validateValue('email', $str);
    }
}
