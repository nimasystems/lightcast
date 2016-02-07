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

class lcTagHead extends lcHtmlBaseTag implements iI18nAttributes
{
    public function __construct($content = null, $profile = null, $xml_lang = null, $lang = null, $dir = null)
    {
        parent::__construct('head', true);

        $this->setContent($content);
        $this->setProfile($profile);
        $this->setDir($dir);
        $this->setXmlLang($xml_lang);
        $this->setLang($lang);
    }

    public static function create()
    {
        return new lcTagHead();
    }

    public function setProfile($value = null)
    {
        $this->setAttribute('profile', $value);
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
        return array('xmlns');
    }

    public static function getOptionalAttributes()
    {
        return array('dir', 'lang', 'xml:lang');
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

    public function getProfile()
    {
        return $this->attributes->get('profile');
    }
}