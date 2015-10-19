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
 * @changed $Id: lcTagStyle.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagStyle extends lcHtmlBaseTag implements iI18nAttributes
{
    const DEFAULT_TYPE = 'text/css';

    public function __construct($content = null, $type = self::DEFAULT_TYPE, $title = null,
                                $media = null, $xml_lang = null, $lang = null, $dir = null)
    {
        parent::__construct('style', true);

        $this->setContent($content);
        $this->setType($type);
        $this->setTitle($title);
        $this->setMedia($media);
        $this->setDir($dir);
        $this->setXmlLang($xml_lang);
        $this->setLang($lang);
    }

    public static function create()
    {
        return new lcTagStyle();
    }

    public function setType($value = self::DEFAULT_TYPE)
    {
        $this->setAttribute('type', $value);
        return $this;
    }

    public function setTitle($value = null)
    {
        $this->setAttribute('title', $value);
        return $this;
    }

    public function setMedia($value = null)
    {
        $this->setAttribute('media', $value);
        return $this;
    }

    public function setDir($value = null)
    {
        $this->setAttribute('dir', $value);
        return $this;
    }

    public function setXmlLang($value = null)
    {
        $this->setAttribute('xml:lang', $value);
        return $this;
    }

    public function setLang($value = null)
    {
        $this->setAttribute('lang', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('type');
    }

    public static function getOptionalAttributes()
    {
        return array('dir', 'lang', 'xml:lang', 'media', 'title');
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getTitle()
    {
        return $this->getAttribute('title');
    }

    public function getMedia()
    {
        return $this->getAttribute('media');
    }

    public function getDir()
    {
        return $this->attributes->get('dir');
    }

    public function getXmlLang()
    {
        return $this->attributes->get('xml:lang');
    }

    public function getLang()
    {
        return $this->attributes->get('lang');
    }
}
