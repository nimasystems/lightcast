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
 * @changed $Id: lcTagBdo.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagBdo extends lcHtmlTag
{
    const DEFAULT_DIR = 'ltr';

    public function __construct($content = null, $dir = self::DEFAULT_DIR, $xml_lang = null)
    {
        parent::__construct('bdo', true);

        $this->setContent($content);
        $this->setDir($dir);
        $this->setXmlLang($xml_lang);
    }

    public static function create()
    {
        return new lcTagBdo();
    }

    public function setDir($value = self::DEFAULT_DIR)
    {
        if ($value != 'ltr' && $value != 'rtl') {
            throw new lcInvalidArgumentException('Bdo tag has an invalid dir attribute: ' . $value);
        }

        $this->setAttribute('dir', $value);
        return $this;
    }

    public function setXmlLang($value = null)
    {
        $this->setAttribute('xml:lang', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('dir');
    }

    public static function getOptionalAttributes()
    {
        return array('xml:lang');
    }

    public function getDir()
    {
        return $this->attributes->get('dir');
    }

    public function getXmlLang()
    {
        return $this->attributes->get('xml:lang');
    }
}