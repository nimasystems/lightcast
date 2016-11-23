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

class lcActionFilterChain extends lcSysObj
{
    /** @var lcActionFilter */
    protected $first_filter;

    /** @var lcActionFilter */
    protected $last_filter;

    public function shutdown()
    {
        $this->removeAllFilters();

        parent::shutdown();
    }

    public function removeAllFilters()
    {
        if ($this->first_filter) {
            $this->first_filter->shutdown();
            $this->first_filter = null;
        }

        $this->last_filter = null;
    }

    public function execute(lcController $parent_controller, $controller_name, $action_name,
                            array $request_params = null, array $controller_context = null, array $skip_filter_categories = null)
    {
        if (!$this->first_filter) {
            return false;
        }

        $ret = $this->first_filter->filterAction($parent_controller, $controller_name, $action_name,
            $request_params, $controller_context, $skip_filter_categories);
        return $ret;
    }

    public function addFilter(lcActionFilter $filter)
    {
        if (!$this->first_filter) {
            $this->first_filter = $filter;
            $this->last_filter = $this->first_filter;
        } else {
            $this->last_filter->setNext($filter);
            $this->last_filter = $filter;
        }
    }

    public function getFirstFilter()
    {
        return $this->first_filter;
    }

    public function getLastFilter()
    {
        return $this->last_filter;
    }
}
