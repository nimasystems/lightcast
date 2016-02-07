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

class lcRouteCollection extends lcBaseCollection
{
    public function connect($name, $route, array $params = null, array $requirements = null)
    {
        parent::appendColl(new lcNamedRoute($name, $route, $params, $requirements));
    }

    public function prepend($name, $route, array $params = null, array $requirements = null)
    {
        $all = $this->list;

        $this->clear();

        $this->append(new lcNamedRoute($name, $route, $params, $requirements));

        foreach ($all as $route) {
            $this->append($route);
            unset($route);
        }

        unset($all);
    }

    public function append(lcNamedRoute $route)
    {
        parent::appendColl($route);
    }

    public function get($name)
    {
        $this->first();

        $all = $this->getAll();

        foreach ($all as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
            unset($route);
        }

        unset($all);

        return null;
    }

    public function offsetExists($name)
    {
        $this->first();

        $all = $this->getAll();

        foreach ($all as $route) {
            if ($route->getName() == $name) {
                return true;
            }
            unset($route);
        }

        unset($all);

        return false;
    }

    public function offsetGet($name)
    {
        $this->first();

        $all = $this->getAll();

        foreach ($all as $route) {
            if ($route->getName() == $name) {
                return $route;
            }
            unset($route);
        }

        unset($all);

        return null;
    }

    public function offsetSet($name, $value)
    {
        throw new lcUnsupportedException('Cannot change collection params');
    }

    public function offsetUnset($name)
    {
        throw new lcUnsupportedException('Cannot change collection params');
    }

    public function __toString()
    {
        $all = $this->getAll();

        if (!$all->count()) {
            return false;
        }

        $str = array();

        foreach ($all as $route) {
            $str[] = $route->getName() . ' (' . $route->getRoute() . ')';
            unset($route);
        }

        unset($all);

        $str = 'Routes: ' . implode(', ', $str);

        return $str;
    }
}
