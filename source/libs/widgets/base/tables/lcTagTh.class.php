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
 * @changed $Id: lcTagTh.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagTh extends lcHtmlTag
{
    public function __construct($content = null, $colspan = null, $rowspan = null, $abbr = null, $axis = null,
                                $headers = null, $scope = null, $align = null, $valign = null, $char = null, $charoff = null)
    {
        parent::__construct('th', true);

        $this->setContent($content);
        $this->setColspan($colspan);
        $this->setRowspan($rowspan);
        $this->setAbbr($abbr);
        $this->setAxis($axis);
        $this->setHeaders($headers);
        $this->setScope($scope);

        $this->setAlign($align);
        $this->setValign($valign);
        $this->setChar($char);
        $this->setCharoff($charoff);
    }

    public static function getRequiredAttributes()
    {
        return array();
    }

    public static function getOptionalAttributes()
    {
        return array('colspan', 'rowspan', 'abbr', 'axis', 'headers', 'scope',
            'align', 'valign', 'char', 'charoff');
    }

    public function setScope($value = null)
    {
        $this->setAttribute('scope', $value);
        return $this;
    }

    public function getScope()
    {
        return $this->getAttribute('scope');
    }

    public function setHeaders($value = null)
    {
        $this->setAttribute('headers', $value);
        return $this;
    }

    public function getHeaders()
    {
        return $this->getAttribute('axis');
    }

    public function setAxis($value = null)
    {
        $this->setAttribute('axis', $value);
        return $this;
    }

    public function getAxis()
    {
        return $this->getAttribute('axis');
    }

    public function setAbbr($value = null)
    {
        $this->setAttribute('abbr', $value);
        return $this;
    }

    public function getAbbr()
    {
        return $this->getAttribute('abbr');
    }

    public function setRowspan($value = null)
    {
        $this->setAttribute('rowspan', $value);
        return $this;
    }

    public function getRowspan()
    {
        return $this->getAttribute('rowspan');
    }

    public function setColspan($value = null)
    {
        $this->setAttribute('colspan', $value);
        return $this;
    }

    public function getColspan()
    {
        return $this->getAttribute('colspan');
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

    public function getAlign()
    {
        return $this->getAttribute('align');
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

    public function getValign()
    {
        return $this->getAttribute('valign');
    }

    public function setChar($value = null)
    {
        $this->setAttribute('char', $value);
        return $this;
    }

    public function getChar()
    {
        return $this->getAttribute('char');
    }

    public function setCharoff($value = null)
    {
        $this->setAttribute('charoff', $value);
        return $this;
    }

    public function getCharoff()
    {
        return $this->getAttribute('charoff');
    }
}