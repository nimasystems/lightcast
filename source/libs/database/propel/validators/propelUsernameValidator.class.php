<?php

class propelUsernameValidator implements BasicValidator
{
    public function isValid(ValidatorMap $map, $str)
    {
        return lcCoreValidator::validateValue('username', $str);
    }
}
