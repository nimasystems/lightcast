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
 * @changed $Id: lcSysObj.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
abstract class lcResidentObj extends lcSysObj implements iEventsListener
{

    /**
     *  returns array|null
     */
    public function getListenerEvents()
    {
        return null;
    }

    protected function beforeAttachRegisteredEvents()
    {
        //
    }

    protected function afterAttachRegisteredEvents()
    {
        //
    }

    public function attachRegisteredEvents()
    {
        $this->beforeAttachRegisteredEvents();

        $events = $this->getListenerEvents();

        if ($events) {
            foreach ($events as $event => $listener) {
                $this->event_dispatcher->connect($event, $this, $listener);
            }
        }

        $this->afterAttachRegisteredEvents();
    }
}