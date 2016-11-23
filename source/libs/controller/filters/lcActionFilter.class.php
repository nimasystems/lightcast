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

abstract class lcActionFilter extends lcSysObj
{
    /** @var lcActionFilter|null */
    protected $next;

    abstract public function getFilterCategory();

    public function shutdown()
    {
        if ($this->next) {
            $this->next->shutdown();
            $this->next = null;
        }

        parent::shutdown();
    }

    public function setNext(lcActionFilter $filter)
    {
        assert($filter !== $this);
        $this->next = $filter;
    }

    public function filterAction(lcController $parent_controller, $controller_name, $action_name,
                                 array $request_params = null, array $controller_context = null, array $skip_filter_categories = null)
    {
        $filter_category = $this->getFilterCategory();

        if ($this->getShouldApplyFilter() &&
            (!$filter_category ||
                (null !== $skip_filter_categories && !in_array($filter_category, $skip_filter_categories)) ||
                null === $skip_filter_categories)
        ) {
            try {
                // if a filter returns true it means it takes responsibility
                // over the action and we stop further processing
                $filter_result = $this->applyFilter($parent_controller, $controller_name, $action_name, $request_params, $controller_context);

                if ($filter_result) {
                    return array(
                        'filter' => &$this,
                        'result' => $filter_result
                    );
                }
            } catch (Exception $e) {
                throw new lcFilterException('Could not apply action filter (' . get_class($this) . '): ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        // process the next filter
        if ($this->next) {
            return $this->next->filterAction($parent_controller, $controller_name, $action_name,
                $request_params, $controller_context, $skip_filter_categories);
        }

        // no filter has taken responsibility - take no further action
        return false;
    }

    abstract protected function getShouldApplyFilter();

    abstract protected function applyFilter(lcController $parent_controller, $controller_name, $action_name, array $request_params = null, array $controller_context = null);
}
