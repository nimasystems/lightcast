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
 * @changed $Id: lcAppObj.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
abstract class lcAppObj extends lcResidentObj implements iI18nProvider, iLoggable
{
    /**
     * @var lcRequest
     */
    protected $request;

    /**
     * @var lcResponse
     */
    protected $response;

    /**
     * @var lcRouting
     */
    protected $routing;

    /**
     * @var lcDatabaseManager
     */
    protected $database_manager;

    /**
     * @var lcStorage
     */
    protected $storage;

    /**
     * @var lcAppSecurityUser
     */
    protected $user;

    /**
     * @var lcDataStorage
     */
    protected $data_storage;

    /** @var iCacheStorage */
    protected $cache;

    /**
     * @var lcMailer
     */
    protected $mailer;

    /**
     * @var lcPropelConnection
     */
    protected $dbc;

    /**
     * Returns an instance of the Response loader
     *
     * @return lcRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest(lcRequest $request = null)
    {
        $this->request = $request;
    }

    /**
     * Returns an instance of the Response loader
     *
     * @return lcResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse(lcResponse $response = null)
    {
        $this->response = $response;
    }

    /*
     * Returns an instance of the Routing loader
    *
    * @returns object lcRouting
    */

    public function setRouter(lcRouting $routing = null)
    {
        $this->setRouting($routing);
    }

    public function getRouter()
    {
        return $this->getRouting();
    }

    public function getRouting()
    {
        return $this->routing;
    }

    /*
     * Returns an instance of the Routing loader
    *
    * @returns object lcRouting
    */

    public function setRouting(lcRouting $routing = null)
    {
        $this->routing = $routing;
    }

    /*
     * Returns an instance of the Mailer loader
    *
    * @returns object lcMailer
    */

    public function getMailer()
    {
        return $this->mailer;
    }

    public function setMailer(lcMailer $mailer = null)
    {
        $this->mailer = $mailer;
    }

    /*
     * Returns an instance of the DatabaseManager loader
    *
    * @returns object lcDatabaseManager
    */
    public function getDatabaseManager()
    {
        return $this->database_manager;
    }

    public function setDatabaseManager(lcDatabaseManager $database_manager = null)
    {
        $this->database_manager = $database_manager;
        $this->dbc = $this->database_manager ? $this->database_manager->getConnection() : null;
    }

    /*
     * Returns an instance of the first instantiated database connection
    *
    * @returns object lcDatabase
    */
    public function getDatabase($name = null)
    {
        return $this->database_manager->getConnection($name);
    }

    /*
     * Returns an instance of the Storage loader
    *
    * @returns object lcStorage
    */
    public function getStorage()
    {
        return $this->storage;
    }

    public function setStorage(lcStorage $storage = null)
    {
        $this->storage = $storage;
    }

    /*
     * Returns an instance of the User loader
    *
    * @returns object lcUser
    */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(lcUser $user = null)
    {
        $this->user = $user;
    }

    public function throwIfUserUnauthorized()
    {
        if (!$this->user || !$this->user->isAuthenticated()) {
            throw new lcAuthException($this->t('Access Denied'));
        }
    }

    /*
     * Returns an instance of the DataStorage loader
    *
    * @returns object lcDataStorage
    */
    public function getDataStorage()
    {
        return $this->data_storage;
    }

    public function setDataStorage(lcDataStorage $data_storage = null)
    {
        $this->data_storage = $data_storage;
    }

    /*
     * Returns an instance of the Cache loader
    *
    * @returns object lcCache
    */
    public function getCache()
    {
        return $this->cache;
    }

    public function setCache(lcCacheStore $cache = null)
    {
        $this->cache = $cache;
    }
}