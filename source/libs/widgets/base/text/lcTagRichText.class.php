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

class lcTagRichText extends lcTagInput
{
    /** @var componentFckEdit */
    protected $fck_component;

    private $width = '100%';

    public function __construct($name = null, $width = null)
    {
        $this->width = ($width ? $width : $this->width);

        parent::__construct('', $name);
    }

    public static function create()
    {
        return new lcTagRichText();
    }

    public function setFckComponent(componentFckEdit $editor)
    {
        $this->fck_component = $editor;
    }

    public function asHtml()
    {
        $fck = $this->fck_component;

        assert(isset($fck));

        $fck->setWidth($this->width);
        $fck->setInstanceName($this->getName());
        $fck->setValue($this->getValue());

        $e = $fck->execute();

        return (string)$e;
    }
}