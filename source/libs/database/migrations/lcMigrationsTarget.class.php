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
 * @changed $Id: lcBaseMigrationsTarget.class.php 1455 2013-10-25 20:29:31Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
abstract class lcMigrationsTarget extends lcSysObj implements iDatabaseMigrationsTarget, iLoggable
{
    const MAGIC_METHOD_SCHEMA_UPGRADE_PREFIX = 'upgradeSchemaTo_';
    const MAGIC_METHOD_SCHEMA_DOWNGRADE_PREFIX = 'downgradeSchemaTo_';

    const DEFAULT_LOG_CHANNEL = 'migrations';

    protected $conn;
    protected $database;
    protected $logger;

    public function getDatabase()
    {
        return $this->database;
    }

    public function setDatabase(lcDatabase $database)
    {
        $this->database = $database;
        $this->conn = $this->database->getConnection();
    }

    public function executeSchemaInstall()
    {
        // may be overriden
    }

    public function executeSchemaRemove()
    {
        // may be overriden
    }

    public function executeDataInstall()
    {
        // may be overriden
    }

    public function executeDataRemove()
    {
        // may be overriden
    }

    public function beforeExecute()
    {
        // may be overriden
    }

    public function afterExecute()
    {
        // may be overriden
    }

    public function executeMigrationUpgrade($from_version, $to_version)
    {
        $from_version = (int)$from_version;
        $to_version = (int)$to_version;

        if (!$from_version || !$to_version) {
            throw new lcInvalidArgumentException('Invalid from/to migration version');
        }

        // call a magic method
        $method_name = self::MAGIC_METHOD_SCHEMA_UPGRADE_PREFIX . $to_version;

        if ($this->methodExists($method_name)) {
            $this->$method_name();
        }

        return true;
    }

    public function executeMigrationDowngrade($from_version, $to_version)
    {
        $from_version = (int)$from_version;
        $to_version = (int)$to_version;

        if (!$from_version || !$to_version) {
            throw new lcInvalidArgumentException('Invalid from/to migration version');
        }

        // call a magic method
        $method_name = self::MAGIC_METHOD_SCHEMA_DOWNGRADE_PREFIX . $to_version;

        if ($this->methodExists($method_name)) {
            $this->$method_name();
        }

        return true;
    }

    #pragma mark - Logger

    public function setLogger(iLoggable $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function emerg($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_EMERG, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function alert($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_ALERT, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function crit($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_CRIT, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function err($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_ERR, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function warning($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_WARNING, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function notice($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_NOTICE, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function info($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_INFO, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function debug($message_code, $channel = null)
    {
        $this->log($message_code, lcLogger::LOG_DEBUG, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
    }

    public function log($message_code, $severity = null, $channel = null)
    {
        if ($this->logger) {
            $this->loggger->log($message_code, $severity, (isset($channel) ? $channel : self::DEFAULT_LOG_CHANNEL));
        }
    }

    #pragma mark - Helpers

    public function __toString()
    {
        $description = $this->getSchemaIdentifier() . ' / ' . $this->getMigrationsVersion();
        return $description;
    }

}
