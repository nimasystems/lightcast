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

class lcHttpVersion
{
    const HTTPVER_1_0 = 10;
    const HTTPVER_1_0_STRING = 'HTTP/1.0';

    const HTTPVER_1_1 = 11;
    const HTTPVER_1_1_STRING = 'HTTP/1.1';

    public static function getString($code)
    {
        if ($code == self::HTTPVER_1_0) {
            return self::HTTPVER_1_0_STRING;
        } else if ($code == self::HTTPVER_1_1) {
            return self::HTTPVER_1_1_STRING;
        } else {
            return false;
        }
    }
}