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

class lcTagBase extends lcHtmlTag
{
    public function __construct($href = null)
    {
        parent::__construct('base', false);

        $this->setHref($href);
    }

    public function setHref($href)
    {
        $this->setAttribute('href', $href);
        return $this;
    }

    public static function create()
    {
        return new lcTagBase();
    }

    public static function getRequiredAttributes()
    {
        return ['href'];
    }

    public static function getOptionalAttributes()
    {
        return [];
    }

    public function getHref()
    {
        return $this->getAttribute('href');
    }
}
