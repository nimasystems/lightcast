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

class lcTagTable extends lcHtmlTag
{
    public function __construct($content = null, $border = null, $cellpadding = null, $cellspacing = null,
                                $summary = null, $frame = null, $rules = null, $width = null)
    {
        parent::__construct('table', true);

        $this->setContent($content);
        $this->setBorder($border);
        $this->setCellSpacing($cellspacing);
        $this->setCellPadding($cellpadding);
        $this->setSummary($summary);
        $this->setFrame($frame);
        $this->setRules($rules);
        $this->setWidth($width);
    }

    public function setBorder($value = null)
    {
        $this->setAttribute('border', $value);
        return $this;
    }

    public function setCellSpacing($value = null)
    {
        $this->setAttribute('cellspacing', $value);
        return $this;
    }

    public function setCellPadding($value = null)
    {
        $this->setAttribute('cellpadding', $value);
        return $this;
    }

    public function setSummary($value = null)
    {
        $this->setAttribute('summary', $value);
        return $this;
    }

    public function setFrame($value = null)
    {
        $this->setAttribute('frame', $value);
        return $this;
    }

    public function setRules($value = null)
    {
        $this->setAttribute('rules', $value);
        return $this;
    }

    public function setWidth($value = null)
    {
        $this->setAttribute('width', $value);
        return $this;
    }

    public static function create()
    {
        return new lcTagTable();
    }

    public static function getRequiredAttributes()
    {
        return [];
    }

    public static function getOptionalAttributes()
    {
        return ['summary', 'border', 'cellpadding', 'cellspacing', 'frame', 'rules', 'width'];
    }

    public function getWidth()
    {
        return $this->getAttribute('width');
    }

    public function getRules()
    {
        return $this->getAttribute('rules');
    }

    public function getFrame()
    {
        return $this->getAttribute('frame');
    }

    public function getBorder()
    {
        return $this->getAttribute('border');
    }
}