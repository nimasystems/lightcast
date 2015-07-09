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
 * @changed $Id: lcObj.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
abstract class lcObj
{
    private $tag;
    private $_reflection;

    public function __construct()
    {
        //
    }

    public function __destruct()
    {
        $this->_reflection = null;
    }

    public function __toString()
    {
        return e($this, true);
    }

    public function __call($method, array $params = null)
    {
        throw new Exception('Class Method \'' . get_class($this) . '::' . $method . '\' does not exist');
    }

    public function __set($property, $value = null)
    {
        throw new Exception('Class Property \'' . get_class($this) . '::' . $property . '\' does not exist');
    }

    public function __get($property)
    {
        throw new Exception('Class Property \'' . get_class($this) . '::' . $property . '\' does not exist');
    }

    public function methodExists($methodname)
    {
        return method_exists($this, $methodname);
    }

    public function propertyExists($property)
    {
        return property_exists($this, $property);
    }

    public function getClassName()
    {
        return get_class($this);
    }

    public function getParentName()
    {
        return get_parent_class($this);
    }

    public function isChildOf($classname)
    {
        return is_subclass_of($this, $classname);
    }

    public function getReflection()
    {
        $reflection = $this->_reflection ? $this->_reflection : new ReflectionClass($this);

        if (!$this->_reflection) {
            $this->_reflection = $reflection;
        }

        return $reflection;
    }

    public function getClassInfo()
    {
        return $this->getReflection();
    }

    public function isFinal()
    {
        return $this->getReflection()->isFinal();
    }

    public function getClassFilename()
    {
        return $this->getReflection()->getFileName();
    }

    public function getClassStartLine()
    {
        return $this->getReflection()->getStartLine();
    }

    public function getClassEndLine()
    {
        return $this->getReflection()->getEndline();
    }

    public function getClassModifiers()
    {
        return $this->getReflection()->getModifiers();
    }

    public function getClassImplements()
    {
        return $this->getReflection()->getInterfaces();
    }

    public function getClassConstants()
    {
        return $this->getReflection()->getConstants();
    }

    public function getClassProperties()
    {
        return $this->getReflection()->getProperties();
    }

    public function getClassMethods()
    {
        return $this->getReflection()->getMethods();
    }

    public function implementsInterface($interface_name)
    {
        return $this->getReflection()->implementsInterface($interface_name);
    }

    public function assert($condition, $error_str = null)
    {
        if ($condition) {
            return true;
        }

        return assert($condition);
    }

    public function description()
    {
        return $this->__toString();
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }
}