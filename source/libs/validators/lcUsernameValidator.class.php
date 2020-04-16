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

class lcUsernameValidator extends lcStringValidator
{
    public function validate($data)
    {
        if (!parent::validate($data)) {
            return false;
        }

        $default_chars = '-_.';
        $allowed_chars = isset($this->options['allowed_chars']) ? (string)$this->options['allowed_chars'] : $default_chars;

        //$ret = (bool)preg_match("/^[\w\d-_\.]+$/", $data);

        return (bool)preg_match("/^[\w\d" . preg_quote($allowed_chars) . "]+$/", $data);
    }
}
