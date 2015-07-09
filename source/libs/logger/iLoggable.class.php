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
 * @changed $Id: iLoggable.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
interface iLoggable
{
    public function emerg($message_code, $channel = null);

    public function alert($message_code, $channel = null);

    public function crit($message_code, $channel = null);

    public function err($message_code, $channel = null);

    public function warning($message_code, $channel = null);

    public function notice($message_code, $channel = null);

    public function info($message_code, $channel = null);

    public function debug($message_code, $channel = null);

    public function log($message_code, $severity = null, $channel = null);

    public function logExtended($message, $severity = null, $filename = null, $ignore_severity_check = false, $cleartext = false, $channel = null);
}
