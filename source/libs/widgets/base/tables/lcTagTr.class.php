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

class lcTagTr extends lcHtmlTag
{
    public function __construct($content = null, $align = null, $valign = null, $char = null, $charoff = null)
    {
        parent::__construct('tr', true);

        $this->setContent($content);
        $this->setAlign($align);
        $this->setValign($valign);
        $this->setChar($char);
        $this->setCharoff($charoff);
    }

    public static function create()
    {
        return new lcTagTr();
    }

    public function setAlign($value = null)
    {
        if (isset($value) &&
            ($value != 'left' && $value != 'center' && $value != 'right' && $value != 'justify' &&
                $value != 'char')
        ) {
            throw new lcInvalidArgumentException('Invalid value for \'align\' attribute for tag colgroup: ' . $value);
        }

        $this->setAttribute('align', $value);
        return $this;
    }

    public function setValign($value = null)
    {
        if (isset($value) &&
            ($value != 'top' && $value != 'middle' && $value != 'bottom' && $value != 'baseline')
        ) {
            throw new lcInvalidArgumentException('Invalid value for \'valign\' attribute for tag colgroup: ' . $value);
        }

        $this->setAttribute('valign', $value);
        return $this;
    }

    public function setChar($value = null)
    {
        $this->setAttribute('char', $value);
        return $this;
    }

    public function setCharoff($value = null)
    {
        $this->setAttribute('charoff', $value);
        return $this;
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('align', 'valign', 'char', 'charoff');
    }

    public function getAlign()
    {
        return $this->getAttribute('align');
    }

    public function getValign()
    {
        return $this->getAttribute('valign');
    }

    public function getChar()
    {
        return $this->getAttribute('char');
    }

    public function getCharoff()
    {
        return $this->getAttribute('charoff');
    }
}