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

class lcTagParam extends lcHtmlBaseTag
{
    public function __construct($name = null, $value = null, $id = null, $type = null, $valuetype = null)
    {
        parent::__construct('param', false);

        $this->setName($name);
        $this->setValue($value);
        $this->setId($id);
        $this->setType($type);
        $this->setValueType($valuetype);
    }

    public static function create()
    {
        return new lcTagParam();
    }

    public function setName($value)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function setValue($value = null)
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    public function setId($value = null)
    {
        $this->setAttribute('id', $value);
        return $this;
    }

    public function setType($value = null)
    {
        $this->setAttribute('type', $value);
        return $this;
    }

    public function setValueType($value = null)
    {
        if ($value != 'data' && $value != 'ref' && $value != 'object') {
            throw new lcInvalidArgumentException('Invalid value for param tag, attribute valuetype: ' . $value);
        }

        $this->setAttribute('valuetype', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('name');
    }

    public static function getOptionalAttributes()
    {
        return array('value', 'id', 'type', 'valuetype');
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function getId()
    {
        return $this->getAttribute('id');
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getValueType()
    {
        return $this->getAttribute('valuetype');
    }
}
