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
 * @changed $Id: lcTimer.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTimer extends lcObj
{
    private $start_time = null;
    private $total_time = null;
    private $name = '';
    private $calls = 0;

    public function __construct($name = '')
    {
        $this->name = $name;
        $this->start();
    }

    public function start()
    {
        $this->start_time = microtime(true);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCalls()
    {
        return $this->calls;
    }

    public function getElapsedTime()
    {
        if (null === $this->total_time) {
            $this->addTime();
        }

        return $this->total_time;
    }

    public function addTime()
    {
        $spend = microtime(true) - $this->start_time;
        $this->total_time += $spend;
        ++$this->calls;

        return $spend;
    }
}
