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

class lcTagInputImage extends lcTagInput
{
    public function __construct($src = null, $name = null, $id = null, $value = null, $alt = null, $size = null, $maxsize = null, $disabled = null, $readonly = null, $accesskey = null)
    {
        parent::__construct('image', $name, $id, $value, $size, $maxsize, $disabled, $readonly, $accesskey);

        $this->setSrc($src);
        $this->setAlt($alt);
    }

    public function setSrc($value = null)
    {
        $this->setAttribute('src', $value);
        return $this;
    }

    public function setAlt($value = null)
    {
        $this->setAttribute('alt', $value);
    }

    public static function create()
    {
        return new lcTagInputImage();
    }

    public static function getRequiredAttributes()
    {
        return implode(['src'], parent::getRequiredAttributes());
    }

    public static function getOptionalAttributes()
    {
        return implode(['alt'], parent::getOptionalAttributes());
    }

    public function getSrc()
    {
        return $this->getAttribute('src');
    }

    public function getAlt()
    {
        $this->getAttribute('alt');
        return $this;
    }
}
