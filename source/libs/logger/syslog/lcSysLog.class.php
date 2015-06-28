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
 * @changed $Id: lcSysLog.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1592 $
*/

class lcSysLog extends lcObj
{
	private $is_os_win;

	const DEFAULT_FACILITY = LOG_LOCAL7;
	const DEFAULT_PRIORITY = LOG_INFO;

	/*  syslog() code types:
	 *  LOG_EMERG system is unusable
	*  LOG_ALERT action must be taken immediately
	*  LOG_CRIT critical conditions
	*  LOG_ERR error conditions
	*  LOG_WARNING warning conditions
	*  LOG_NOTICE normal, but significant, condition
	*  LOG_INFO informational message
	*  LOG_DEBUG debug-level message
	*/


	/*
	 * System Log Facilities:
	LOG_AUTH security/authorization messages (use LOG_AUTHPRIV instead in systems where that constant is defined)
	LOG_AUTHPRIV security/authorization messages (private)
	LOG_CRON clock daemon (cron and at)
	LOG_DAEMON other system daemons
	LOG_KERN kernel messages
	LOG_LOCAL0 ... LOG_LOCAL7 reserved for local use, these are not available in Windows
	LOG_LPR line printer subsystem
	LOG_MAIL mail subsystem
	LOG_NEWS USENET news subsystem
	LOG_SYSLOG messages generated internally by syslogd
	LOG_USER generic user-level messages
	LOG_UUCP

	* LOG_USER - the only available on Windows
	*/

	public function __construct()
	{
		parent::__construct();

		$this->is_os_win = lcSys::isOSWin();
	}

	public function log($message, $priority = self::DEFAULT_PRIORITY, $facility = self::DEFAULT_FACILITY, $prefix = null)
	{
		if (!$message)
		{
			return false;
		}

		if ($this->is_os_win && $facility != LOG_USER)
		{
			$facility = LOG_USER;
		}

		if (!openlog($prefix, LOG_CONS | LOG_NDELAY | LOG_PID, $facility))
		{
			return false;
		}

		try
		{
			$res = syslog($priority, $message);
		}
		catch(Exception $e)
		{
			fnothing($e);
			closelog();
			assert(false);

			return false;
		}

		closelog();

		return $res;
	}
}

?>