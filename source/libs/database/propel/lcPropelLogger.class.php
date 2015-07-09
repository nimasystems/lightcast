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
 * @changed $Id: lcPropelLogger.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcPropelLogger extends lcSysObj
{
    public function initialize()
    {
        parent::initialize();
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    private function getInternalLoggerSeverity($propel_severity)
    {
        $propel_severity = (int)$propel_severity;

        switch ($propel_severity) {
            case Propel::LOG_EMERG:
                return lcLogger::LOG_EMERG;
            case Propel::LOG_ALERT:
                return lcLogger::LOG_ALERT;
            case Propel::LOG_CRIT:
                return lcLogger::LOG_CRIT;
            case Propel::LOG_ERR:
                return lcLogger::LOG_ERR;
            case Propel::LOG_WARNING:
                return lcLogger::LOG_WARNING;
            case Propel::LOG_NOTICE:
                return lcLogger::LOG_NOTICE;
            case Propel::LOG_INFO:
                return lcLogger::LOG_INFO;
            case Propel::LOG_DEBUG:
                return lcLogger::LOG_DEBUG;
            default:
                return lcLogger::LOG_DEBUG;
        }
    }

    public function log($message, $level = null, $channel = null)
    {
        fnothing($channel);
        $level = $level ? $level : Propel::LOG_DEBUG;

        $logger_severity = $this->getInternalLoggerSeverity($level);

        parent::log($message, $logger_severity, 'database');
    }
}