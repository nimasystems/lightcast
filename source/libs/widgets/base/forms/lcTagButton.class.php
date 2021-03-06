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

class lcTagButton extends lcHtmlTag
{
    public function __construct($content = null, $type = null, $name = null, $value = null,
                                $disabled = false, $tabindex = null, $accesskey = null)
    {
        parent::__construct('button', true);

        if ($content) {
            $this->setContent($content);
        }

        if ($type) {
            $this->setType($type);
        }

        if ($name) {
            $this->setName($name);
        }

        if ($value) {
            $this->setValue($value);
        }

        $this->setIsDisabled($disabled);

        if ($tabindex) {
            $this->setTabIndex($tabindex);
        }

        if ($accesskey) {
            $this->setAccessKey($accesskey);
        }
    }

    public function setContent($content)
    {
        parent::setContent($content);
        return $this;
    }

    public function setType($value = null)
    {
        if ($value != 'submit' && $value != 'button' && $value != 'reset') {
            throw new lcInvalidArgumentException('Invalid button type');
        }

        $this->setAttribute('type', $value);
        return $this;
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function setValue($value = null)
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public function setAccessKey($accesskey = null)
    {
        $this->setAttribute('accesskey', $accesskey);
        return $this;
    }

    public static function create()
    {
        return new lcTagButton();
    }

    public static function getRequiredAttributes()
    {
        return [];
    }

    public static function getOptionalAttributes()
    {
        return ['type', 'name', 'value', 'disabled', 'tabindex', 'accesskey'];
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }
}
