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
 * @changed $Id: lcConfigurationHolder.class.php 1455 2013-10-25 20:29:31Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */
class lcConfigurationHolder extends lcObj implements ArrayAccess, Serializable
{
    private $config_namespaces;
    private $idx;

    public function getNamespaces()
    {
        if (!$this->config_namespaces) {
            return null;
        }

        return array_keys($this->config_namespaces);
    }

    public function getAll()
    {
        return $this->config_namespaces;
    }

    public function get($namespace, $name)
    {
        return isset($this->config_namespaces[$namespace]) && isset($this->config_namespaces[$namespace][$name]) ? $this->config_namespaces[$namespace][$name] : null;
    }

    public function set($namespace, $name, $value = null)
    {
        if (!isset($this->config_namespaces[$namespace]) || !isset($this->config_namespaces[$namespace][$name])) {
            $this->config_namespaces[$namespace][$name] = $value;
        } else {
            $this->config_namespaces[$namespace][$name] = $value;
        }

        $this->idx[$namespace . '.' . $name] = array(
            $namespace,
            $name
        );

        return true;
    }

    public function setNamespace($namespace, array $values = null)
    {
        $this->config_namespaces[$namespace] = $values ? $values : array();

        if (isset($values)) {
            foreach ($values as $key => $val) {
                $this->idx[$namespace . '.' . $key] = array(
                    $namespace,
                    $key
                );

                unset($key, $val);
            }
        }
    }

    public function remove($namespace, $name)
    {
        if (!isset($this->config_namespaces[$namespace])) {
            return;
        }

        unset($this->config_namespaces[$namespace]);
        unset($this->idx[$namespace . '.' . $name]);
    }

    public function clear()
    {
        $this->config_namespaces = null;
        $this->idx = null;
    }

    public function getNamespace($namespace)
    {
        return isset($this->config_namespaces[$namespace]) ? $this->config_namespaces[$namespace] : null;
    }

    public function offsetExists($short_config_name)
    {
        if (!isset($this->idx[$short_config_name])) {
            return false;
        }

        return true;
    }

    public function offsetGet($short_config_name)
    {
        if (!$this->offsetExists($short_config_name)) {
            return null;
        }

        return $this->config_namespaces[$this->idx[$short_config_name][0]][$this->idx[$short_config_name][1]];
    }

    public function offsetSet($short_config_name, $value)
    {
        return false;
    }

    public function offsetUnset($short_config_name)
    {
        return false;
    }

    public function serialize()
    {
        $tmp = array(
            $this->config_namespaces,
            $this->idx
        );

        return serialize($tmp);
    }

    public function unserialize($serialized)
    {
        list($this->config_namespaces, $this->idx) = unserialize($serialized);
    }

}
