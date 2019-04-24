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

abstract class lcBaseCollection extends lcObj
{
    const MAX_LOGGED_VAR_VAL_LEN = 500;

    const SPL_OBJECT_NAME = 'ArrayIterator';
    /**
     * @var ArrayIterator
     */
    protected $list;

    /*
     * Construct a new array iterator from anything that has a hash table.
    * That is any Array or Object.
    */

    public function __construct(array $data = null)
    {
        parent::__construct();

        if (!class_exists(self::SPL_OBJECT_NAME, false)) {
            throw new lcSystemException('Cannot load SPL Object: ' . self::SPL_OBJECT_NAME);
        }

        $splclass = self::SPL_OBJECT_NAME;
        $this->list = new $splclass($data ? $data : []);
    }

    public function __destruct()
    {
        unset($this->list);

        parent::__destruct();
    }

    public function offsetGet($index)
    {
        return $this->list->offsetGet($index);
    }

    public function getArrayCopy()
    {
        return $this->list->getArrayCopy();
    }

    public function isValid()
    {
        return $this->list->valid();
    }

    public function selected()
    {
        return $this->current();
    }

    public function current()
    {
        return $this->list->current();
    }

    public function previous()
    {
        if (!$this->offsetExists($this->list->key() - 1)) {
            return null;
        }

        $this->list->seek($this->list->key() - 1);
    }

    public function offsetExists($index)
    {
        return $this->list->offsetExists($index);
    }

    public function next()
    {
        return $this->list->current();
    }

    public function seek($position)
    {
        $this->list->seek($position);
    }

    /*
     * Get vars
    */

    public function first()
    {
        $this->rewind();
    }

    public function rewind()
    {
        $this->list->rewind();
    }

    public function last()
    {
        if ($this->pos() == $this->count() - 1) {
            return;
        }

        $this->list->seek($this->count() - 1);
    }

    public function pos()
    {
        return $this->key();
    }

    /*
     * Manage Position of List
    */

    public function key()
    {
        return $this->list->key();
    }

    public function count()
    {
        return $this->list->count();
    }

    public function getAll()
    {
        return $this->list;
    }

    public function getSPLFlags()
    {
        return $this->list->getFlags();
    }

    public function __toString()
    {
        $out = '';

        if ($this->count()) {
            $a = [];
            $list = $this->list;

            if ($list && is_array($list)) {
                foreach ($list as $val) {
                    $val = is_string($val) && strlen($val) > self::MAX_LOGGED_VAR_VAL_LEN ?
                        substr($val, 0, self::MAX_LOGGED_VAR_VAL_LEN) : $val;

                    $a[] = (is_string($val) ? $val : var_export($val, true));

                    unset($val);
                }
            }

            $out = implode(', ', $a);
            unset($a);
        }

        return $out;
    }

    public function clear()
    {
        $spl = self::SPL_OBJECT_NAME;
        $this->list = new $spl;
    }

    protected function appendColl($value)
    {
        $this->list->append($value);
    }

    protected function offsetSetColl($index, $value)
    {
        $this->list->offsetSet($index, $value);
    }

    protected function offsetUnset($index)
    {
        $this->list->offsetUnset($index);
    }

    /*
     * Sorting functions
    */

    protected function setColl($value, $offset = null)
    {
        $this->list->offsetSet($offset ? $offset : $this->list->key(), $value);
    }

    protected function delete($offset = null)
    {
        $this->list->offsetUnset($offset ? $offset : $this->key());
    }

    protected function asort()
    {
        $this->list->asort();
    }

    protected function ksort()
    {
        $this->list->ksort();
    }

    protected function natcasesort()
    {
        $this->list->natcasesort();
    }

    protected function natsort()
    {
        $this->list->natsort();
    }

    /*
     * Other
    */

    protected function uasort($cpm_function)
    {
        $this->list->uasort($cpm_function);
    }

    protected function uksort($cpm_function)
    {
        $this->list->uksort($cpm_function);
    }

    protected function setSPLFlags($flags)
    {
        $this->list->setFlags($flags);
    }
}
