<?php

class propelDateValidator implements BasicValidator
{
    public function isValid(ValidatorMap $map, $str)
    {
        return lcCoreValidator::validateValue('date', $str);
    }
}
