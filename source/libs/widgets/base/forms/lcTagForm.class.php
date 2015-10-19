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
 * @changed $Id: lcTagForm.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagForm extends lcHtmlTag
{
    public function __construct($content = null, $action = null, $method = null, $enctype = null,
                                $accept = null, $accept_charset = null)
    {
        parent::__construct('form', true);

        if (isset($content)) {
            $this->setContent($content);
        }

        if (isset($action)) {
            $this->setAction($action);
        }

        $this->setMethod($method ? $method : 'post');

        if (isset($enctype)) {
            $this->setEnctype($enctype);
        }

        if (isset($accept)) {
            $this->setAccept($accept);
        }

        if (isset($accept_charset)) {
            $this->setAcceptCharset($accept_charset);
        }
    }

    public static function create()
    {
        return new lcTagForm();
    }

    public function setContent($content)
    {
        parent::setContent($content);
        return $this;
    }

    public function setAction($value)
    {
        $this->setAttribute('action', $value);
        return $this;
    }

    public function setMethod($value)
    {
        if ($value != 'post' && $value != 'get') {
            throw new lcInvalidArgumentException('Invalid form method type: ' . $value);
        }

        $this->setAttribute('method', $value);
        return $this;
    }

    public function setEnctype($accesskey = null)
    {
        $this->setAttribute('enctype', $accesskey);
        return $this;
    }

    public function setAccept($value = null)
    {
        $this->setAttribute('accept', $value);
        return $this;
    }

    public function setAcceptCharset($value = null)
    {
        $this->setAttribute('accept-charset', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array('action');
    }

    public static function getOptionalAttributes()
    {
        return array('method', 'enctype', 'accept');
    }

    public function setName($name)
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function getAction()
    {
        return $this->getAttribute('action');
    }

    public function getMethod()
    {
        return $this->getAttribute('method');
    }

    public function getEnctype()
    {
        return $this->getAttribute('enctype');
    }

    public function getAccept()
    {
        return $this->getAttribute('accept');
    }

    public function getAcceptCharset()
    {
        return $this->getAttribute('accept-charset');
    }
}
