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
 * @changed $Id: lcTagA.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagA extends lcHtmlTag
{
    public function __construct($content = null, $href = null, $target = null,
                                $rel = null, $rev = null, $accesskey = null, $tabindex = null)
    {
        parent::__construct('a', true);

        $this->setContent($content);
        $this->setHref($href);
        $this->setTarget($target);
        $this->setRel($rel);
        $this->setRev($rev);
        $this->setAccessKey($accesskey);
        $this->setTabIndex($tabindex);
    }

    public function setHref($href = null)
    {
        $this->setAttribute('href', $href);
        return $this;
    }

    public function setTarget($tabindex = null)
    {
        $this->setAttribute('target', $tabindex);
        return $this;
    }

    public function setRel($rel = null)
    {
        $this->setAttribute('rel', $rel);
        return $this;
    }

    public function setRev($rev = null)
    {
        $this->setAttribute('rev', $rev);
        return $this;
    }

    public function setAccessKey($accesskey = null)
    {
        $this->setAttribute('accesskey', $accesskey);
        return $this;
    }

    public function setTabIndex($tabindex = null)
    {
        $this->setAttribute('tabindex', $tabindex);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('href', 'target', 'charset', 'type', 'hreflang', 'rel', 'rev', 'accesskey', 'tabindex');
    }

    public function getHref()
    {
        return $this->getAttribute('href');
    }

    public function getCharset()
    {
        return $this->getAttribute('charset');
    }

    public function setCharset($charset = null)
    {
        $this->setAttribute('charset', $charset);
        return $this;
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function setType($type = null)
    {
        $this->setAttribute('type', $type);
        return $this;
    }

    public function getHrefLang()
    {
        return $this->getAttribute('hreflang');
    }

    public function setHrefLang($hreflang = null)
    {
        $this->setAttribute('hreflang', $hreflang);
        return $this;
    }

    public function getRel()
    {
        return $this->getAttribute('rel');
    }

    public function getRev()
    {
        return $this->getAttribute('rev');
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }

    public function getTarget()
    {
        return $this->getAttribute('target');
    }
}
