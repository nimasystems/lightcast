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
 * @changed $Id: lcEvent.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
/*
 * This object MUST NOT IMPLEMENT ANY VIRTUAL methods
* and it must be AS PLAIN AS POSSIBLE
* for performance reasons!!!
*/

class lcEvent extends lcObj
{
    // we make these public as lcEvent
    // is highly used everywhere
    // to lower the number of function calls which can be expensive
    public $subject;
    public $event_name;
    public $params;
    public $processed;
    public $filtered_by;

    public $max_processing_iterations;
    public $actual_processing_iterations;

    public $return_value;

    public function __construct($event_name, & $subject = null, array $params = null)
    {
        $this->event_name = $event_name;
        $this->subject = &$subject;
        $this->max_processing_iterations = 0;
        $this->actual_processing_iterations = 0;

        $this->params = isset($params) ? $params : array();
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getEventName()
    {
        return $this->event_name;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setProcessed($processed = true)
    {
        $this->processed = $processed;
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    public function setReturnValue($value)
    {
        // mark automatically as processed
        $this->processed = true;
        $this->return_value = $value;
    }

    public function getReturnValue()
    {
        return $this->return_value;
    }

    public function __toString()
    {
        return
            'Event: ' . $this->event_name . ", \n" .
            'Subject: ' . (isset($this->subject) ? get_class($this->subject) : null) . ", \n" .
            'Is Processed: ' . $this->processed . ", \n";
    }
}
