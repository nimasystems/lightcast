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
 * @changed $Id: lcTagMeta.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagMeta extends lcHtmlBaseTag implements iI18nAttributes
{
    public function __construct($content = null, $name = null, $http_equiv = null, $scheme = null, $xml_lang = null, $lang = null, $dir = null)
    {
        parent::__construct('meta', false);

        $this->setContent($content);
        $this->setName($name);
        $this->setHttpEquiv($http_equiv);
        $this->setScheme($scheme);
        $this->setDir($dir);
        $this->setXmlLang($xml_lang);
        $this->setLang($lang);
    }

    public static function create()
    {
        return new lcTagMeta();
    }

    public function setContent($value)
    {
        $this->setAttribute('content', $value);
        return $this;
    }

    public function setName($value = null)
    {
        $this->setAttribute('name', $value);
        return $this;
    }

    public function setHttpEquiv($value = null)
    {
        $this->setAttribute('http-equiv', $value);
        return $this;
    }

    public function setScheme($value = null)
    {
        $this->setAttribute('scheme', $value);
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
        return array('content');
    }

    public static function getOptionalAttributes()
    {
        return array('dir', 'lang', 'xml:lang', 'name', 'http-equiv', 'scheme');
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

    public function getContent()
    {
        return $this->getAttribute('content');
    }

    public function getName()
    {
        return $this->attributes->get('name');
    }

    public function getHttpEquiv()
    {
        return $this->attributes->get('http-equiv');
    }

    public function getScheme()
    {
        return $this->attributes->get('scheme');
    }

    public function asHtml()
    {
        if ($this->getAttribute('name') && $this->getAttribute('http-equiv')) {
            throw new lcInvalidArgumentException('meta tag cannot contain both \'name\' and \'http-equiv\'');
        }

        if ($this->getAttribute('scheme') && !$this->getAttribute('name')) {
            throw new lcInvalidArgumentException('meta tag attribute \'scheme\' must be used with attribute \'name\'');
        }

        return parent::asHtml();
    }
}
