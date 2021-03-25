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

    public function __call($method, array $params = null)
    {
        throw new Exception('Class Method \'' . get_class($this) . '::' . $method . '\' does not exist');
    }

    public function __get($property)
    {
        throw new Exception('Class Property \'' . get_class($this) . '::' . $property . '\' does not exist');
    }

    public function __set($property, $value = null)
    {
        throw new Exception('Class Property \'' . get_class($this) . '::' . $property . '\' does not exist');
    }

    public function __isset($property)
    {
        if (DO_DEBUG) {
            throw new Exception('Class Property \'' . get_class($this) . '::' . $property . '\' is not set');
        }
    }

    public function methodExists($methodname): bool
    {
        return method_exists($this, $methodname);
    }

    public function propertyExists($property): bool
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

    public function isChildOf($classname): bool
    {
        return is_subclass_of($this, $classname);
    }

    public function getClassInfo(): ReflectionClass
    {
        return $this->getReflection();
    }

    public function getReflection(): ReflectionClass
    {
        $reflection = $this->_reflection ?: new ReflectionClass($this);

        if (!$this->_reflection) {
            $this->_reflection = $reflection;
        }

        return $reflection;
    }

    public function isFinal(): bool
    {
        return $this->getReflection()->isFinal();
    }

    public function getClassFilename(): string
    {
        return $this->getReflection()->getFileName();
    }

    public function getClassStartLine(): int
    {
        return $this->getReflection()->getStartLine();
    }

    public function getClassEndLine(): int
    {
        return $this->getReflection()->getEndLine();
    }

    public function getClassModifiers(): int
    {
        return $this->getReflection()->getModifiers();
    }

    public function getClassImplements(): array
    {
        return $this->getReflection()->getInterfaces();
    }

    public function getClassConstants(): array
    {
        return $this->getReflection()->getConstants();
    }

    public function getClassProperties(): array
    {
        return $this->getReflection()->getProperties();
    }

    public function getClassMethods(): array
    {
        return $this->getReflection()->getMethods();
    }

    public function implementsInterface($interface_name): bool
    {
        return $this->getReflection()->implementsInterface($interface_name);
    }

    public function assert($condition): bool
    {
        return assert($condition);
    }

    public function description(): string
    {
        return $this->__toString();
    }

    /** @noinspection PhpMissingReturnTypeInspection */
    public function __toString()
    {
        return (string)e($this, true);
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;
    }
}