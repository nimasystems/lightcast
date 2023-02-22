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
class lcArrayCollection extends lcBaseCollection implements ArrayAccess
{
    public function __construct(array $values = null)
    {
        parent::__construct();

        if (null !== $values) {
            foreach ($values as $key => $val) {
                $this->append((string)$key, $val);
                unset($key, $val);
            }
        }
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function append(string $key, $value = null)
    {
        if (!$this->setPositionByKey($key)) {
            parent::appendColl(new lcNameValuePair($key, $value));
        } else {
            $this->current()->setValue($value);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    private function setPositionByKey(string $key): bool
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

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->setPositionByKey($key);
    }

    public function mergeWithCollection(lcArrayCollection $collection)
    {
        foreach ($collection->getAll() as $item) {
            $this->set($item->getName(), $item->getValue());

            unset($item);
        }
    }

    /**
     * @param  $value
     * @param  $offset
     * @return void
     */
    public function set($value, $offset = null)
    {
        $this->append((string)$value, $offset);
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return (bool)$this->get($offset);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        if (!$this->setPositionByKey($key)) {
            return null;
        }

        return $this->current()->getValue();
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param $offset
     * @param $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * @param $offset
     * @return void
     */
    protected function delete($offset = null)
    {
        if (!$this->setPositionByKey($offset)) {
            return;
        }

        parent::delete($this->key());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->getKeyValueArray();
    }

    /**
     * @return array
     */
    public function getKeyValueArray(): array
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

    /**
     * @return string
     */
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

        return $out;
    }
}
