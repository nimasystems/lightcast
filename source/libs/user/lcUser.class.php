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
 * @changed $Id: lcUser.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcUser extends lcResidentObj implements iProvidesCapabilities, ArrayAccess, iKeyValueProvider, iDebuggable
{
    protected $attributes = array();

    abstract public function setFlash($flash_message = null);

    abstract public function hasFlash();

    abstract public function getFlash();

    abstract public function clearFlash();

    abstract public function getAndClearFlash();

    public function initialize()
    {
        parent::initialize();
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getCapabilities()
    {
        return array(
            'user'
        );
    }

    public function getDebugInfo()
    {
        $attributes = $this->getAttributes();

        $out = array();

        if ($attributes) {
            foreach ($attributes as $key => $value) {
                if (!$value) {
                    continue;
                }

                if (!is_numeric($value) && !is_string($value) && !is_array($value) && !is_bool($value)) {
                    $value = '(complex)';
                }

                // shorten
                if (is_string($value) && strlen($value) > 255) {
                    $value = substr($value, 0, 255) . '...';
                }

                $out[$key] = $value;

                unset($key, $value);
            }
        }

        $debug = array(
            'attributes' => $out
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name)
    {
        $value = isset($this->attributes[$name]) ?
            $this->attributes[$name] : null;
        return $value;
    }

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function setAttributes(array $attributes = null)
    {
        $this->attributes = isset($attributes) ? $attributes : array();
    }

    public function unsetAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }
    }

    public function unsetAttributes()
    {
        $this->attributes = array();
    }

    public function hasAttribute($name)
    {
        return isset($this->attributes[$name]);
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->unsetAttribute($offset);
    }

    public function getAttributeNames()
    {
        if (!$this->attributes) {
            return false;
        }

        $ret = array_keys($this->attributes);

        return $ret;
    }

    public function __toString()
    {
        $str = "lcUser: " .
            "Attributes: " . var_export($this->attributes, true) . "\n\n";

        return $str;
    }

    #pragma mark - iKeyValueProvider

    public function getAllKeys()
    {
        $ret = $this->getAttributeNames();
        return $ret;
    }

    public function getValueForKey($key)
    {
        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $ret = $this->getAttribute($key);
        return $ret;
    }
}
