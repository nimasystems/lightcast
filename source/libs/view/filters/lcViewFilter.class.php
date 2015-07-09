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

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcViewFilter.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcViewFilter extends lcSysObj
{
    /** @var lcView */
    protected $view;

    /** @var lcViewFilter */
    protected $next;

    public function shutdown()
    {
        if ($this->next) {
            $this->next->shutdown();
            $this->next = null;
        }

        $this->view = null;

        parent::shutdown();
    }

    public function setNext(lcViewFilter $view_filter)
    {
        assert($view_filter !== $this);
        $this->next = $view_filter;
    }

    public function filterView(lcView $view, $content, $content_type = null)
    {
        $this->view = $view;

        if ($this->getShouldApplyFilter()) {
            try {
                $content = $this->applyFilter($content, $content_type);
            } catch (Exception $e) {
                throw new lcFilterException('Could not apply view filter (' . get_class($this) . '): ' .
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        // process the next filter
        if ($this->next) {
            $content = $this->next->filterView($view, $content, $content_type);
        }

        return $content;
    }

    abstract protected function getShouldApplyFilter();

    abstract protected function applyFilter($content, $content_type = null);
}
