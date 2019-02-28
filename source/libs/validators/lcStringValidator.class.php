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

class lcStringValidator extends lcValidator
{
    public function validate($data)
    {
        if (!is_string($data) && !is_numeric($data)) {
            return false;
        }

        $regex = isset($this->options['regex']) ? $this->options['regex'] : null;
        $max_length = isset($this->options['max_length']) ? (int)$this->options['max_length'] : 0;
        $min_length = isset($this->options['min_length']) ? (int)$this->options['min_length'] : 0;
        $alphanum_only = isset($this->options['alpha_numeric']) ? (int)$this->options['alpha_numeric'] : false;
        $allow_whitespace = isset($this->options['allow_whitespace']) ? (int)$this->options['allow_whitespace'] : true;

        // min length
        if (($min_length && lcUnicode::strlen($data) < $min_length)) {
            return false;
        }

        // max length
        if (($max_length && lcUnicode::strlen($data) > $max_length)) {
            return false;
        }

        // space
        if (!$allow_whitespace && lcUnicode::strpos($data, ' ') !== false) {
            return false;
        }

        // alpha numeric only
        if ($alphanum_only) {
            return (bool)preg_match('/^[\w\d' . ($allow_whitespace ? '\s' : '') . ']+$/', $data);
        }

        if ($regex) {
            if (!preg_match($regex, $data)) {
                return false;
            }
        }

        return true;
    }
}