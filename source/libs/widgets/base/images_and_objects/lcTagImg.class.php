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
 * @changed $Id: lcTagImg.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagImg extends lcHtmlTag
{
    public function __construct($src = null, $alt = null, $height = null, $width = null, $longdesc = null)
    {
        parent::__construct('img', false);

        $this->setSrc($src);
        $this->setAlt($alt);
        $this->setHeight($height);
        $this->setWidth($width);
        $this->setLongDesc($longdesc);
    }

    public static function create()
    {
        return new lcTagImg();
    }

    public function setSrc($value)
    {
        $this->setAttribute('src', $value);
        return $this;
    }

    public function setAlt($value)
    {
        $this->setAttribute('alt', $value);
        return $this;
    }

    public function setHeight($value = null)
    {
        $this->setAttribute('height', isset($value) ? (int)$value : null);
        return $this;
    }

    public function setWidth($value = null)
    {
        $this->setAttribute('width', isset($value) ? (int)$value : null);
        return $this;
    }

    public function setLongDesc($value = null)
    {
        $this->setAttribute('longdesc', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('src', 'alt');
    }

    public static function getOptionalAttributes()
    {
        return array('longdesc', 'height', 'width');
    }

    public function getSrc()
    {
        return $this->getAttribute('src');
    }

    public function getAlt()
    {
        return $this->getAttribute('alt');
    }

    public function getHeight()
    {
        return $this->getAttribute('height');
    }

    public function getWidth()
    {
        return $this->getAttribute('width');
    }

    public function getLongDesc()
    {
        return $this->getAttribute('longdesc');
    }
}
