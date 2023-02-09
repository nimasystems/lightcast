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
abstract class lcBaseCollection extends lcObj
{
    public const MAX_LOGGED_VAR_VAL_LEN = 500;

    public const SPL_OBJECT_NAME = 'ArrayIterator';
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
        $this->list = new $splclass($data ?: []);
    }

    public function __destruct()
    {
        unset($this->list);

        parent::__destruct();
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->list->offsetGet($offset);
    }

    /**
     * @return array
     */
    public function getArrayCopy(): array
    {
        return $this->list->getArrayCopy();
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->list->valid();
    }

    /**
     * @return mixed
     */
    public function selected()
    {
        return $this->current();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->list->current();
    }

    /**
     * @return void|null
     */
    public function previous()
    {
        if (!$this->offsetExists($this->list->key() - 1)) {
            return null;
        }

        $this->list->seek($this->list->key() - 1);
    }

    /**
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->list->offsetExists($offset);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return $this->list->current();
    }

    /**
     * @param $position
     * @return void
     */
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

    /**
     * @return int|string|null
     */
    public function pos()
    {
        return $this->key();
    }

    /*
     * Manage Position of List
    */

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->list->count();
    }

    /**
     * @return ArrayIterator|mixed
     */
    public function getAll()
    {
        return $this->list;
    }

    /**
     * @return int
     */
    public function getSPLFlags(): int
    {
        return $this->list->getFlags();
    }

    /**
     * @param $value
     * @param $offset
     * @return void
     */
    public function set($value, $offset = null)
    {
        $this->list->offsetSet($offset ?: $this->list->key(), $value);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $out = '';

        if ($this->count()) {
            $a = [];
            $list = $this->list;

            if ($list && is_array($list)) {
                foreach ($list as $val) {
                    $a[] = (is_string($val) ? $val : var_export($val, true));

                    unset($val);
                }
            }

            $out = implode(', ', $a);
            unset($a);
        }

        return $out;
    }

    /**
     * @param $value
     * @return void
     */
    protected function appendColl($value)
    {
        $this->list->append($value);
    }

    /**
     * @param $offset
     * @param $value
     * @return void
     */
    protected function offsetSetColl($offset, $value)
    {
        $this->list->offsetSet($offset, $value);
    }

    /**
     * @param $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->list->offsetUnset($offset);
    }

    /**
     * @param $value
     * @param $offset
     * @return void
     */
    protected function setColl($value, $offset = null)
    {
        $this->list->offsetSet($offset ?: $this->list->key(), $value);
    }

    /**
     * @param $offset
     * @return void
     */
    protected function delete($offset = null)
    {
        $this->list->offsetUnset($offset ?: $this->key());
    }

    /*
     * Sorting functions
    */

    /**
     * @return int|string|null
     */
    public function key()
    {
        return $this->list->key();
    }

    public function clear()
    {
        $spl = self::SPL_OBJECT_NAME;
        $this->list = new $spl();
    }

    public function asort()
    {
        $this->list->asort();
    }

    public function ksort()
    {
        $this->list->ksort();
    }

    public function natcasesort()
    {
        $this->list->natcasesort();
    }

    public function natsort()
    {
        $this->list->natsort();
    }

    /*
     * Other
    */

    /**
     * @param $cpm_function
     * @return void
     */
    protected function uasort($cpm_function)
    {
        $this->list->uasort($cpm_function);
    }

    /**
     * @param $cpm_function
     * @return void
     */
    protected function uksort($cpm_function)
    {
        $this->list->uksort($cpm_function);
    }

    /**
     * @param $flags
     * @return void
     */
    protected function setSPLFlags($flags)
    {
        $this->list->setFlags($flags);
    }
}
