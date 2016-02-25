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

class lcProfiler extends lcObj
{
    /** @var lcTimer[] */
    private $timers;

    private $start_time;

    public function start()
    {
        $this->start_time = microtime(true);
    }

    public function getTimer($name)
    {
        if (!isset($this->timers[$name])) {
            $this->timers[$name] = new lcTimer($name);
        }

        $this->timers[$name]->start();

        return $this->timers[$name];
    }

    public function getTimers()
    {
        return $this->timers;
    }

    public function clearTimers()
    {
        $this->timers = null;
    }

    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getMicrotime()
    {
        return $this->start_time ? microtime(true) - $this->start_time : 0;
    }

    public function getTotalTime()
    {
        return $this->start_time ? sprintf('%.4f', (microtime(true) - $this->start_time) * 1000) : 0;
    }
}
