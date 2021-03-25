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

class lcDataObj extends lcObj implements ArrayAccess, JsonSerializable
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data = null)
    {
        parent::__construct();

        $this->setData($data);
    }

    public function __call($method, array $params = null): ?bool
    {
        $sub = substr($method, 0, 3);

        if ($sub == 'set' || $sub == 'get') {
            $subp = lcInflector::underscore(substr($method, 3, strlen($method)));

            if ($sub == 'set') {
                $value = isset($params[0]) && $params[0] ? $params[0] : null;

                if ($value) {
                    $this->data[$subp] = $value;
                } else {
                    unset($this->data[$subp]);
                }

                return true;

            } else if ($sub == 'get') {
                return (isset($this->data[$subp]) ? $this->data[$subp] : null);
            }

        }

        parent::__call($method, $params);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return (isset($this->data[$offset]) ? $this->data[$offset] : null);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize(): array
    {
        return (array)$this->getData();
    }

    /**
     * @param null $key
     * @return mixed
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getData($key = null)
    {
        return ($key ? (isset($this->data[$key]) ? $this->data[$key] : null) : $this->data);
    }

    /**
     * @param mixed $data
     * @param null $value
     * @return lcDataObj
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function setData($data = null, $value = null)
    {
        if (!$data || is_array($data)) {
            $this->data = $data;
        } else {
            $this->data[$data] = $value;
        }
        return $this;
    }
}