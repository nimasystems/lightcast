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

class lcControllerStack extends lcSysObj
{
    /** @var lcControllerStackItem[] */
    protected $stack = [];

    public function shutdown()
    {
        if ($this->stack) {
            foreach ($this->stack as $idx => $item) {
                $item->shutdown();
                unset($this->stack[$idx]);
                unset($idx, $item);
            }
        }

        $this->stack = null;

        parent::shutdown();
    }

    public function add(lcController $controller_instance)
    {
        $item = new lcControllerStackItem();
        $item->setControllerInstance($controller_instance);
        $item->initialize();

        $this->stack[] = $item;

        return $item;
    }

    public function count()
    {
        return count($this->stack);
    }

    public function & getAll()
    {
        return $this->stack;
    }

    public function & get($index)
    {
        $v = ($index > -1 && $index < count($this->stack)) ?
                $this->stack[$index] :
                null;
        return $v;
    }

    public function pop()
    {
        return array_pop($this->stack);
    }

    public function first()
    {
        return isset($this->stack[0]) ? $this->stack[0] : null;
    }

    public function last()
    {
        return isset($this->stack[0]) ?
            $this->stack[count($this->stack) - 1] :
            null;
    }

    public function size()
    {
        return count($this->stack);
    }

    public function __toString()
    {
        // return a string representation of the current controllers in the stack
        $ret = [];

        if ($this->stack) {
            $i = 1;

            foreach ($this->stack as $stack_item) {
                $controller_instance = $stack_item->getControllerInstance();

                $ret[] = $i . '. ' .
                    $controller_instance->getControllerName() . '/' .
                    $controller_instance->getActionName() . ' (' .
                    $controller_instance->getActionType() . ')';

                $i++;

                unset($stack_item, $controller_instance);
            }
        }

        return implode("\n", $ret);
    }
}
