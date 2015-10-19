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
 * @changed $Id: lcTagScript.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagScript extends lcHtmlBaseTag
{
    const DEFAULT_TYPE = 'text/javascript';

    public function __construct($src = null, $content = null, $type = self::DEFAULT_TYPE, $defer = false, $charset = null)
    {
        parent::__construct('script', true);

        $this->setContent($content);
        $this->setType($type);
        $this->setSrc($src);
        $this->setDefer($defer);
        $this->setCharset($charset);
    }

    public static function create()
    {
        return new lcTagScript();
    }

    public function setType($type = self::DEFAULT_TYPE)
    {
        $this->setAttribute('type', $type);
        return $this;
    }

    public function setSrc($value = null)
    {
        $this->setAttribute('src', $value);
        return $this;
    }

    public function setDefer($value = false)
    {
        $this->setAttribute('defer', $value ? 'defer' : null);
        return $this;
    }

    public function setCharset($value = null)
    {
        $this->setAttribute('charset', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('type');
    }

    public static function getOptionalAttributes()
    {
        return array('src', 'charset', 'defer');
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function getSrc()
    {
        return $this->getAttribute('src');
    }

    public function getCharset()
    {
        return $this->getAttribute('charset');
    }

    public function getDefer()
    {
        return $this->getAttribute('defer') ? true : false;
    }

    public function asHtml()
    {
        return
            '<script ' . $this->getAttributes()->asHtml() . '>' . "\n" .
            '/* <![CDATA[ */' . "\n" .
            $this->getContent() . "\n" .
            '/* ]]> */' . "\n" .
            '</script>';
    }
}
