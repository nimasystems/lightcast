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
 * @changed $Id: lcTagMap.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagMap extends lcHtmlBaseTag implements iI18nAttributes, iEventAttributes
{
    public function __construct($id, $content = null, $class = null, $title = null)
    {
        parent::__construct('map', true);

        $this->setContent($content);
        $this->setAttribute('id', $id);
        $this->setClass($class);
        $this->setTitle($title);
    }

    public function setClass($value = null)
    {
        $this->setAttribute('class', $value);
        return $this;
    }

    public function setTitle($value = null)
    {
        $this->setAttribute('title', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('id');
    }

    public static function getOptionalAttributes()
    {
        return array('dir', 'lang', 'xml:lang', 'class', 'title');
    }

    public function getDir()
    {
        return $this->attributes->get('dir');
    }

    public function setDir($value = null)
    {
        $this->setAttribute('dir', $value);
        return $this;
    }

    public function getXmlLang()
    {
        return $this->attributes->get('xml:lang');
    }

    public function setXmlLang($value = null)
    {
        $this->setAttribute('xml:lang', $value);
        return $this;
    }

    public function getLang()
    {
        return $this->attributes->get('lang');
    }

    public function setLang($value = null)
    {
        $this->setAttribute('lang', $value);
        return $this;
    }

    public function getClass()
    {
        return $this->attributes->get('class');
    }

    public function getTitle()
    {
        return $this->attributes->get('title');
    }

    public function getOnClick()
    {
        return $this->attributes->get('onclick');
    }

    public function setOnClick($value = null)
    {
        $this->setAttribute('onclick', $value);
        return $this;
    }

    public function getOnDblClick()
    {
        return $this->attributes->get('ondblclick');
    }

    public function setOnDblClick($value = null)
    {
        $this->setAttribute('ondblclick', $value);
        return $this;
    }

    public function getOnMouseDown()
    {
        return $this->attributes->get('onmousedown');
    }

    public function setOnMouseDown($value = null)
    {
        $this->setAttribute('onmousedown', $value);
        return $this;
    }

    public function getOnMouseUp()
    {
        return $this->attributes->get('onmouseup');
    }

    public function setOnMouseUp($value = null)
    {
        $this->setAttribute('onmouseup', $value);
        return $this;
    }

    public function getOnMouseOver()
    {
        return $this->attributes->get('onmouseover');
    }

    public function setOnMouseOver($value = null)
    {
        $this->setAttribute('onmouseover', $value);
        return $this;
    }

    public function getOnMouseMove()
    {
        return $this->attributes->get('onmousemove');
    }

    public function setOnMouseMove($value = null)
    {
        $this->setAttribute('onmousemove', $value);
        return $this;
    }

    public function getOnMouseOut()
    {
        return $this->attributes->get('onmouseout');
    }

    public function setOnMouseOut($value = null)
    {
        $this->setAttribute('onmouseout', $value);
        return $this;
    }

    public function getOnKeyPress()
    {
        return $this->attributes->get('onkeypress');
    }

    public function setOnKeyPress($value = null)
    {
        $this->setAttribute('onkeypress', $value);
        return $this;
    }

    public function getOnKeyDown()
    {
        return $this->attributes->get('onkeydown');
    }

    public function setOnKeyDown($value = null)
    {
        $this->setAttribute('onkeydown', $value);
        return $this;
    }

    public function getOnKeyUp()
    {
        return $this->attributes->get('onkeydown');
    }

    public function setOnKeyUp($value = null)
    {
        $this->setAttribute('onkeydown', $value);
        return $this;
    }
}
