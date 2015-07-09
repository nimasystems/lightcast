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
 * @changed $Id: lcTagButton.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagButton extends lcHtmlTag
{
    public function __construct($content = null, $type = null, $name = null, $value = null,
                                $disabled = false, $tabindex = null, $accesskey = null)
    {
        parent::__construct('button', true);

        $this->setContent($content);
        $this->setType($type);
        $this->setName($name);
        $this->setValue($value);
        $this->setIsDisabled($disabled);
        $this->setTabIndex($tabindex);
        $this->setAccessKey($accesskey);
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('type', 'name', 'value', 'disabled', 'tabindex', 'accesskey');
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

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function setValue($value = null)
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function setIsDisabled($value = false)
    {
        $this->setAttribute('disabled', $value ? 'disabled' : null);
        return $this;
    }

    public function getIsDisabled()
    {
        return $this->getAttribute('disabled') ? true : false;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }

    public function setAccessKey($accesskey = null)
    {
        $this->setAttribute('accesskey', $accesskey);
        return $this;
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }
}
