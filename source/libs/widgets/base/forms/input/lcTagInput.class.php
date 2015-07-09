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

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcTagInput.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagInput extends lcHtmlTag
{
    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('name', 'type', 'value', 'maxlength', 'src', 'accept', 'disabled', 'readonly', 'accesskey', 'tabindex');
    }

    public function __construct($type = null, $name = null, $id = null, $value = null, $size = null, $maxsize = null, $disabled = null, $readonly = null, $accesskey = null)
    {
        parent::__construct('input', false);

        $this->setType($type);
        $this->setName($name);
        $this->setId($id);
        $this->setValue($value);
        $this->setSize($size);
        $this->setMaxSize($maxsize);
        $this->setIsDisabled($disabled);
        $this->setIsReadOnly($readonly);
        $this->setAccessKey($accesskey);
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function setType($value = null)
    {
        $this->setAttribute('type', $value);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function getId()
    {
        return $this->getAttribute('id');
    }

    public function setId($value)
    {
        $this->setAttribute('id', $value);
        return $this;
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function setValue($value = null)
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    public function getSize()
    {
        return $this->getAttribute('size');
    }

    public function setSize($value = null)
    {
        $this->setAttribute('size', $value);
        return $this;
    }

    public function getMaxSize()
    {
        return $this->getAttribute('maxsize');
    }

    public function setMaxSize($value = null)
    {
        $this->setAttribute('maxsize', $value);
        return $this;
    }

    public function getMaxLength()
    {
        return $this->getAttribute('maxlength');
    }

    public function setMaxLength($value = null)
    {
        $this->setAttribute('maxlength', $value);
        return $this;
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }

    public function setAccessKey($value = null)
    {
        $this->setAttribute('accesskey', $value);
        return $this;
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public function getIsReadOnly()
    {
        return $this->getAttribute('readonly') ? true : false;
    }

    public function setIsReadOnly($value = false)
    {
        $this->setAttribute('readonly', $value ? 'readonly' : null);
        return $this;
    }
}