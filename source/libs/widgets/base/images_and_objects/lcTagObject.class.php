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

class lcTagObject extends lcHtmlTag
{
    public function __construct($content = null,
                                $classid = null, $data = null, $codebase = null, $declare = null,
                                $type = null, $codetype = null, $archive = null, $standby = null, $width = null,
                                $height = null, $name = null, $tabindex = null)
    {
        parent::__construct('object', true);

        $this->setContent($content);
        $this->setClassId($classid);
        $this->setData($data);
        $this->setCodebase($codebase);
        $this->setDeclare($declare);
        $this->setType($type);
        $this->setCodetype($codetype);
        $this->setArchive($archive);
        $this->setStandby($standby);
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setName($name);
        $this->setTabIndex($tabindex);
    }

    public static function create()
    {
        return new lcTagObject();
    }

    public function setClassId($value = null)
    {
        $this->setAttribute('classid', $value);
        return $this;
    }

    public function setData($value = null)
    {
        $this->setAttribute('data', $value);
        return $this;
    }

    public function setCodebase($value = null)
    {
        $this->setAttribute('codebase', $value);
        return $this;
    }

    public function setDeclare($value = null)
    {
        $this->setAttribute('declare', $value ? 'declare' : null);
        return $this;
    }

    public function setType($value = null)
    {
        $this->setAttribute('type', $value);
        return $this;
    }

    public function setCodetype($value = null)
    {
        $this->setAttribute('codetype', $value);
        return $this;
    }

    public function setArchive($value = null)
    {
        $this->setAttribute('archive', $value);
        return $this;
    }

    public function setStandby($value = null)
    {
        $this->setAttribute('standby', $value);
        return $this;
    }

    public function setWidth($value = null)
    {
        $this->setAttribute('width', $value);
        return $this;
    }

    public function setHeight($value = null)
    {
        $this->setAttribute('height', $value);
        return $this;
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('classid', 'data', 'codebase', 'declare', 'type', 'codetype', 'archive', 'standby',
            'width', 'height', 'name', 'tabindex');
    }

    public function getClassId()
    {
        return $this->getAttribute('classid');
    }

    public function getData()
    {
        return $this->getAttribute('data');
    }

    public function getCodebase()
    {
        return $this->getAttribute('codebase');
    }

    public function getDeclare()
    {
        return $this->getAttribute('declare') ? true : false;
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getCodetype()
    {
        return $this->getAttribute('codetype');
    }

    public function getArchive()
    {
        return $this->getAttribute('archive');
    }

    public function getStandby()
    {
        return $this->getAttribute('standby');
    }

    public function getWidth()
    {
        return $this->getAttribute('width');
    }

    public function getHeight()
    {
        return $this->getAttribute('height');
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }
}
