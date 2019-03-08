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

class lcArrayCollection extends lcBaseCollection implements ArrayAccess
{
    public function __construct(array $values = null)
    {
        parent::__construct();

        if (isset($values)) {
            foreach ($values as $key => $val) {
                $this->append($key, $val);
                unset($key, $val);
            }
        }
    }

    public function append($key, $value = null)
    {
        if (!$this->setPositionByKey($key)) {
            parent::appendColl(new lcNameValuePair($key, $value));
        } else {
            $this->current()->setValue($value);
        }
    }

    private function setPositionByKey($key)
    {
        $this->first();

        $all = $this->getAll();

        foreach ($all as $el) {
            if ($el->getName() == $key) {
                return true;
            }

            unset($el);
        }

        unset($all);

        return false;
    }

    public function has($key)
    {
        return $this->setPositionByKey($key);
    }

    public function clear()
    {
        parent::clear();
    }

    public function mergeWithCollection(lcArrayCollection $collection)
    {
        foreach ($collection->getAll() as $item) {
            $this->set($item->getName(), $item->getValue());

            unset($item);
        }
    }

    public function set($key, $value = null)
    {
        $this->append($key, $value);
    }

    public function offsetExists($name)
    {
        return $this->get($name) ? true : false;
    }

    public function get($key)
    {
        if (!$this->setPositionByKey($key)) {
            return null;
        }

        return $this->current()->getValue();
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    public function offsetUnset($name)
    {
        $this->delete($name);
    }

    public function delete($key = null)
    {
        if (!$this->setPositionByKey($key)) {
            return;
        }

        parent::delete($this->key());
    }

    public function toArray()
    {
        return $this->getKeyValueArray();
    }

    public function getKeyValueArray()
    {
        $out = [];

        if ($this->count()) {
            $all = $this->getAll()->getArrayCopy();

            if ($all && is_array($all)) {
                foreach ($all as $val) {
                    /** @var lcNameValuePair $val */
                    $out[$val->getName()] = $val->getValue();

                    unset($val);
                }
            }

            unset($all);
        }

        return $out;
    }

    public function __toString()
    {
        $out = '';

        if ($this->count()) {
            $a = [];

            $all = $this->getAll()->getArrayCopy();

            if ($all && is_array($all)) {
                foreach ($all as $val) {
                    /** @var lcNameValuePair $val */
                    $value = $val->getValue();
                    $value = is_string($value) && strlen($value) > self::MAX_LOGGED_VAR_VAL_LEN ?
                        substr($value, 0, self::MAX_LOGGED_VAR_VAL_LEN) : $value;
                    $a[] = $val->getName() . '=' . (is_string($value) ? $value : var_export($value, true));

                    unset($val);
                }
            }

            $out = implode(', ', $a);

            unset($a, $all);
        }

        return (string)$out;
    }
}