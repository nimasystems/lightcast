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

abstract class lcDatabase extends lcSysObj
{
    /**
     * @var lcDatabaseManager
     */
    protected $database_manager;

    /**
     * @var array
     */
    protected $options;

    public function initialize()
    {
        parent::initialize();
    }

    public function shutdown()
    {
        $this->database_manager =
        $this->options =
            null;

        parent::shutdown();
    }

    public function setDatabaseManager(lcDatabaseManager $database_manager)
    {
        $this->database_manager = $database_manager;
        return $this;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @return PDO
     */
    public function connect()
    {
        return $this->getConnection();
    }

    /**
     * @return PDO
     */
    abstract public function getConnection();

    /**
     * @return void
     */
    abstract public function disconnect();

    /**
     * @return bool
     */
    abstract public function isConnected();

    /**
     * @return int
     */
    abstract public function getSQLCount();
}
