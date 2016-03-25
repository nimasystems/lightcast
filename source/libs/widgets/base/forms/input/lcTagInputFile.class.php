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

class lcTagInputFile extends lcTagInput
{
    public function __construct($name = null, $id = null, $value = null, $accept = null, $size = null, $maxsize = null, $disabled = null, $readonly = null, $accesskey = null)
    {
        parent::__construct('file', $name, $id, $value, $size, $maxsize, $disabled, $readonly, $accesskey);

        $this->setAccept($accept);
    }

    public static function create()
    {
        return new lcTagInputFile();
    }

    public function setAccept($value = null)
    {
        $this->setAttribute('accept', $value);
        return $this;
    }

    public static function getOptionalAttributes()
    {
        return implode(array('accept'), parent::getOptionalAttributes());
    }

    public function getAccept()
    {
        return $this->getAttribute('accept');
    }
}
