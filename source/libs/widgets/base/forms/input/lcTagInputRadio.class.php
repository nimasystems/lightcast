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

class lcTagInputRadio extends lcTagInput
{
    public function __construct($value = null, $name = null, $id = null, $checked = false, $size = null, $maxsize = null, $disabled = null, $readonly = null, $accesskey = null)
    {
        parent::__construct('radio', $name, $id, $value, $size, $maxsize, $disabled, $readonly, $accesskey);

        $this->setIsChecked($checked);
    }

    public function setIsChecked($value = false)
    {
        $this->setAttribute('checked', $value ? 'checked' : null);
        return $this;
    }

    public static function create()
    {
        return new lcTagInputRadio();
    }

    public static function getRequiredAttributes()
    {
        return implode(['value'], parent::getRequiredAttributes());
    }

    public function getIsChecked()
    {
        return $this->getAttribute('checked') ? true : false;
    }
}
