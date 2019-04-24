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

abstract class lcDataStorage extends lcResidentObj implements iProvidesCapabilities
{
    public function initialize()
    {
        parent::initialize();
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getCapabilities()
    {
        return [
            'data_storage',
        ];
    }

    // Save a file into the data storage - pass an array of $item_info metadata objects
    // Optionally pass either the file data or a pointer to the data
    // returns lcDataStorageItem on success, throws an exception on error
    abstract public function set($item_info, $data = null, &$data_pointer = null);

    // must return a lcDataStorageItem instance or null if unavailable
    abstract public function get($item_info);

    // returns true on success, throws an exception on error
    abstract public function remove($item_info);

    // returns true if item exists, false if it does not
    abstract public function has($item_info);
}
