<?php

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com
*/

class lcPasswordValidator extends lcStringValidator
{
    public function validate($data)
    {
        if (!parent::validate($data)) {
            return false;
        }

        $min_uppercase_symbols = isset($this->options['min_uppercase_symbols']) ? (int)$this->options['min_uppercase_symbols'] : null;
        $min_lowercase_symbols = isset($this->options['min_lowercase_symbols']) ? (int)$this->options['min_lowercase_symbols'] : null;
        $min_special_symbols = isset($this->options['min_special_symbols']) ? (int)$this->options['min_special_symbols'] : null;
        $min_numbers = isset($this->options['min_numbers']) ? (int)$this->options['min_numbers'] : null;
        $min_letters = isset($this->options['min_letters']) ? (int)$this->options['min_letters'] : null;

        $tmp = null;

        $min_uppercase_symbols_valid = !$min_uppercase_symbols || (preg_match_all("/[A-Z]/", $data, $tmp) >= $min_uppercase_symbols);
        $min_lowercase_symbols = !$min_lowercase_symbols || (preg_match_all("/[a-z]/", $data, $tmp) >= $min_lowercase_symbols);
        $min_special_symbols = !$min_special_symbols || (preg_match_all("/[!@#$%^&*()\-_=+{};:,<.>]/", $data, $tmp) >= $min_special_symbols);
        $min_numbers = !$min_numbers || (preg_match_all("/[0-9]/", $data, $tmp) >= $min_numbers);
        $min_letters = !$min_letters || (preg_match_all("/[a-zA-Z]/", $data, $tmp) >= $min_letters);

        $ret =
            $min_uppercase_symbols_valid &&
            $min_lowercase_symbols &&
            $min_special_symbols &&
            $min_numbers &&
            $min_letters;

        return $ret;
    }
}
