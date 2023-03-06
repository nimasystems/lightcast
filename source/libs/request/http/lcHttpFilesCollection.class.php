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

class lcHttpFilesCollection extends lcBaseCollection implements ArrayAccess
{
    public function append(lcHttpFile $file)
    {
        parent::appendColl($file);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($value, $offset);
    }

    /**
     * @param lcHttpFile $value
     * @param $offset
     * @return void
     */
    public function set($value, $offset = null)
    {
        parent::offsetSetColl($offset, $value);
    }

    public function offsetUnset($index)
    {
        parent::offsetUnset($index);
    }

    public function delete($offset = null)
    {
        parent::delete($offset);
    }

    public function clear()
    {
        parent::clear();
    }

    // TODO - copied from DC
    public function getByFieldName($name)
    {
        $this->first();

        $res = [];

        foreach ($this->list as $el) {
            if ($el->getFieldName() == $name) {
                $res[] = $el;
            }
        }

        if (!count($res)) {
            return null;
        } else
            if (count($res) > 1) {
                return $res;
            } else {
                return $res[0];
            }
    }

    public function getByFormFieldName($name)
    {
        $this->first();

        foreach ($this->list as $el) {
            if ($el->getFormName() == $name) {
                return $el;
            }
        }

        return null;
    }

    public function getByName($name)
    {
        $this->first();

        foreach ($this->list as $el) {
            if ($el->getName() == $name) {
                return $el;
            }
        }

        return null;
    }
}
