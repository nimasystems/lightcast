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
 * @changed $Id: lcTagBody.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagBody extends lcHtmlTag
{
    public function __construct($content = null, $onload = null, $onunload = null)
    {
        parent::__construct('body', true);

        $this->setContent($content);
        $this->setOnLoad($onload);
        $this->setOnUnload($onunload);
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('onload', 'onunload');
    }

    public function getOnLoad()
    {
        return $this->attributes->get('onload');
    }

    public function setOnLoad($value = null)
    {
        $this->setAttribute('onload', $value);
        return $this;
    }

    public function getOnUnload()
    {
        return $this->attributes->get('onunload');
    }

    public function setOnUnload($value = null)
    {
        $this->setAttribute('onunload', $value);
        return $this;
    }
}
