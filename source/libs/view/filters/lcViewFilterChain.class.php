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

class lcViewFilterChain extends lcSysObj
{
    /** @var lcViewFilter */
    protected $first_view_filter;

    /** @var lcViewFilter */
    protected $last_view_filter;

    public function shutdown()
    {
        $this->removeAllFilters();

        parent::shutdown();
    }

    public function removeAllFilters()
    {
        if ($this->first_view_filter) {
            $this->first_view_filter->shutdown();
            $this->first_view_filter = null;
        }

        $this->last_view_filter = null;
    }

    public function execute(lcView $view, $content, $content_type = null)
    {
        if (!$this->first_view_filter) {
            return $content;
        }

        return $this->first_view_filter->filterView($view, $content, $content_type);
    }

    public function addViewFilter(lcViewFilter $view_filter)
    {
        if (!$this->first_view_filter) {
            $this->first_view_filter = $view_filter;
            $this->last_view_filter = $this->first_view_filter;
        } else {
            $this->last_view_filter->setNext($view_filter);
            $this->last_view_filter = $view_filter;
        }
    }

    public function getFirstViewFilter()
    {
        return $this->first_view_filter;
    }

    public function getLastViewFilter()
    {
        return $this->last_view_filter;
    }
}
