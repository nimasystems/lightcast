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
 * @changed $Id: lcHtmlAttributeCollection.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcHtmlAttributeCollection implements ArrayAccess, iAsHTML
{
    protected $always_added_attribs;
    protected $attributes;

    public function __construct(array $attributes = null)
    {
        $this->attributes = isset($attributes) ?
            $attributes :
            array();
    }

    public function set($name, $value = null)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function get($name)
    {
        return isset($this->attributes[$name]) ?
            $this->attributes[$name] :
            null;
    }

    public function setAlwaysAddedAttribs(array $attribs)
    {
        $this->always_added_attribs = $attribs;
    }

    public function getAll()
    {
        return $this->attributes;
    }

    public function remove($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
        return $this;
    }

    public function clear()
    {
        $this->attributes = array();
        return $this;
    }

    public function offsetExists($name)
    {
        return isset($this->attributes[$name]) ?
            $this->attributes[$name] :
            false;
    }

    public function offsetGet($name)
    {
        if (!isset($this->attributes[$name])) {
            throw new lcInvalidArgumentException('The html attribute collection does not have an attribute \'' . $name . '\'');
        }

        return $this->attributes[$name];
    }

    public function offsetSet($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->attributes[$name]);
    }

    public function __toString()
    {
        return $this->asHtml();
    }

    public function asHtml()
    {
        if (!count($this->attributes)) {
            return '';
        }

        $out = array();

        foreach ($this->attributes as $name => $value) {
            if (!in_array($name, (array)$this->always_added_attribs)) {
                if (!is_int($value) && !$value) {
                    continue;
                }
            }

            $out[] = htmlspecialchars($name) . '="' . htmlspecialchars($value) . '"';
            unset($name, $value);
        }

        return implode(' ', $out);
    }
}
