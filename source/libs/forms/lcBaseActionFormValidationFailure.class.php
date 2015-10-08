<?php

/**
 * class lcBaseActionFormValidationFailure
 *
 * Lightcast - A Complete MVC/PHP/XSLT based Framework
 * Copyright (C) 2005-2008 Nimasystems Ltd
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
 * General E-Mail: info@nimasystems.com
 *
 * $HeadURL: https://svn.nimasystems.com/ogledai-web/trunk/addons/plugins/forms/lib/action_forms/lcActionFormValidationFailure.class.php $
 * $Revision: 443 $
 * $Author: mkovachev $
 * $Date: 2014-05-17 17:22:03 +0300 (Сб , 17 Май 2014) $
 * $Id: lcBaseActionFormValidationFailure.class.php 443 2014-05-17 14:22:03Z mkovachev $
 *
 * @defgroup AdminLayout
 *
 */
class lcBaseActionFormValidationFailure extends lcObj
{
    protected $field_name;
    protected $message;

    public function __construct($field_name = null, $message = null)
    {
        parent::__construct();

        $this->field_name = $field_name;
        $this->message = $message;
    }

    public function setFieldName($field_name)
    {
        $this->field_name = $field_name;
    }

    public function getFieldName()
    {
        return $this->field_name;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
