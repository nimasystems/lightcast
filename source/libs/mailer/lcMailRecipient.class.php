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

class lcMailRecipient extends lcObj
{
    private $email;
    private $name;

    public function __construct($email, $name = '')
    {
        parent::__construct();

        $this->name = isset($name) ? (string)$name : null;
        $this->email = (string)$email;
    }

    public static function create($email, $name = null)
    {
        if (!$email) {
            return null;
        }

        $obj = new lcMailRecipient($email, $name);
        return $obj;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __toString()
    {
        $name = $this->getName();
        $email = $this->getEmail();

        if (!$email) {
            return '';
        }

        return ($name ? $name . ' <' : '') .
            $email .
            ($name ? '>' : '');
    }
}