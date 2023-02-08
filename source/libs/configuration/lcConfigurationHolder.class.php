<?php
declare(strict_types=1);

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
 *
 */
class lcConfigurationHolder extends lcObj implements ArrayAccess, Serializable
{
    private array $config_namespaces = [];
    private array $idx = [];

    /**
     * @return int[]|string[]|null
     */
    public function getNamespaces(): ?array
    {
        if (!$this->config_namespaces) {
            return null;
        }

        return array_keys($this->config_namespaces);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->config_namespaces;
    }

    /**
     * @param $namespace
     * @param $name
     * @return mixed|null
     */
    public function get($namespace, $name)
    {
        return isset($this->config_namespaces[$namespace]) && isset($this->config_namespaces[$namespace][$name]) ? $this->config_namespaces[$namespace][$name] : null;
    }

    /**
     * @param $namespace
     * @param $name
     * @param $value
     * @return true
     */
    public function set($namespace, $name, $value = null): bool
    {
        $this->config_namespaces[$namespace][$name] = $value;

        $this->idx[$namespace . '.' . $name] = [
            $namespace,
            $name,
        ];

        return true;
    }

    /**
     * @param $namespace
     * @param array|null $values
     * @return void
     */
    public function setNamespace($namespace, array $values = null)
    {
        $this->config_namespaces[$namespace] = $values ?: [];

        if (null !== $values) {
            foreach ($values as $key => $val) {
                $this->idx[$namespace . '.' . $key] = [
                    $namespace,
                    $key,
                ];

                unset($key, $val);
            }
        }
    }

    /**
     * @param $namespace
     * @param $name
     * @return void
     */
    public function remove($namespace, $name)
    {
        if (!isset($this->config_namespaces[$namespace])) {
            return;
        }

        unset($this->config_namespaces[$namespace], $this->idx[$namespace . '.' . $name]);
    }

    public function clear()
    {
        $this->config_namespaces =
        $this->idx = [];
    }

    /**
     * @param $namespace
     * @return mixed|null
     */
    public function getNamespace($namespace)
    {
        return $this->config_namespaces[$namespace] ?? null;
    }

    /**
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->config_namespaces[$this->idx[$offset][0]][$this->idx[$offset][1]];
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        if (!isset($this->idx[$offset])) {
            return false;
        }

        return true;
    }

    /**
     * @param $offset
     * @param $value
     * @return false
     */
    public function offsetSet($offset, $value): bool
    {
        return false;
    }

    /**
     * @param $offset
     * @return false
     */
    public function offsetUnset($offset): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function serialize(): ?string
    {
        $tmp = [
            $this->config_namespaces,
            $this->idx,
        ];

        return serialize($tmp);
    }

    /**
     * @param $data
     * @return void
     */
    public function unserialize($data)
    {
        [$this->config_namespaces, $this->idx] = unserialize($data);
    }

}
