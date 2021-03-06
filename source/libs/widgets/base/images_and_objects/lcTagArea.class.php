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

class lcTagArea extends lcHtmlTag
{
    public function __construct($alt = null, $content = null, $shape = null, $coords = null,
                                $href = null, $nohref = null, $accesskey = null, $tabindex = null)
    {
        parent::__construct('area', true);

        $this->setContent($content);
        $this->setAlt($alt);
        $this->setShape($shape);
        $this->setCoords($coords);
        $this->setHref($href);
        $this->setNoHref($nohref);
        $this->setAccessKey($accesskey);
        $this->setTabIndex($tabindex);
    }

    public function setAlt($value)
    {
        $this->setAttribute('alt', $value);
        return $this;
    }

    public function setShape($value = null)
    {
        if (isset($value) &&
            !(
                $value == 'rect' || $value == 'circle' || $value == 'poly' || $value == 'default'
            )
        ) {
            throw new lcInvalidArgumentException('tag \'area\' - shape \'' . $value . '\' is not a valid argument');
        }

        $this->setAttribute('shape', $value);
        return $this;
    }

    public function setCoords($value = null)
    {
        $this->setAttribute('coords', $value);
        return $this;
    }

    public function setHref($value = null)
    {
        $this->setAttribute('href', $value);
        return $this;
    }

    public function setNoHref($value = false)
    {
        $this->setAttribute('nohref', $value);
        return $this;
    }

    public function setAccessKey($value = null)
    {
        $this->setAttribute('accesskey', $value);
        return $this;
    }

    public function setTabIndex($value = null)
    {
        $this->setAttribute('tabindex', $value);
        return $this;
    }

    public static function create()
    {
        return new lcTagArea();
    }

    public static function getRequiredAttributes()
    {
        return ['alt'];
    }

    public static function getOptionalAttributes()
    {
        return ['shape', 'coords', 'href', 'nohref', 'accesskey', 'tabindex'];
    }

    public function getAlt()
    {
        return $this->getAttribute('alt');
    }

    public function getShape()
    {
        return $this->getAttribute('shape');
    }

    public function getCoords()
    {
        return $this->getAttribute('coords');
    }

    public function getHref()
    {
        return $this->getAttribute('href');
    }

    public function getNoHref()
    {
        return $this->getAttribute('nohref') ? true : false;
    }

    public function getAccessKey()
    {
        return $this->getAttribute('accesskey');
    }

    public function getTabIndex()
    {
        return $this->getAttribute('tabindex');
    }
}