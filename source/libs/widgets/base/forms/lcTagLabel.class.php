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
 * @changed $Id: lcTagLabel.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagLabel extends lcHtmlTag
{
    public function __construct($content = null, $for = null, $accesskey = null)
    {
        parent::__construct('label', true);

        $this->setContent($content);
        $this->setFor($for);
        $this->setAccessKey($accesskey);
    }

    public function setContent($content)
    {
        parent::setContent($content);
        return $this;
    }

    public function setFor($value = null)
    {
        $this->setAttribute('for', $value);
        return $this;
    }

    public function setAccessKey($accesskey = null)
    {
        $this->setAttribute('accesskey', $accesskey);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('for', 'accesskey');
    }

    public function getFor()
    {
        return $this->getAttribute('for');
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }
}
