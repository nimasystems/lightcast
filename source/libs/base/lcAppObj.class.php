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
 * Class lcAppObj
 * @property PDO $pdc
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

    /** @var lcCacheStore */
    protected $cache;

    /**
     * @var lcMailer
     */
    protected $mailer;

    /**
     * @var lcPropelConnection
     */
    protected $dbc;

    /** @noinspection PhpMissingReturnTypeInspection */
    public function __get($property)
    {
        // @deprecated - 'dbc' has to be removed eventually someday!
        if ($property === 'dbc') {
            return $this->database_manager ? $this->database_manager->getConnection() : null;
        }
    }

    /**
     * Returns an instance of the Response loader
     *
     * @return lcRequest
     */
    public function getRequest(): ?lcRequest
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
    public function getResponse(): ?lcResponse
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

    /**
     * @return lcRouting
     */
    public function getRouter(): ?lcRouting
    {
        return $this->getRouting();
    }

    /**
     * @return lcRouting
     */
    public function getRouting(): ?lcRouting
    {
        return $this->routing;
    }

    /**
     * @param lcRouting|null $routing
     */
    public function setRouting(lcRouting $routing = null)
    {
        $this->routing = $routing;
    }

    /**
     * @return lcMailer
     */
    public function getMailer(): ?lcMailer
    {
        return $this->mailer;
    }

    public function setMailer(lcMailer $mailer = null)
    {
        $this->mailer = $mailer;
    }

    /**
     * @return lcDatabaseManager
     */
    public function getDatabaseManager(): ?lcDatabaseManager
    {
        return $this->database_manager;
    }

    public function setDatabaseManager(lcDatabaseManager $database_manager = null)
    {
        $this->database_manager = $database_manager;
    }

    /**
     * @param null $name
     * @return PDO|null
     */
    public function getDatabase($name = null): ?PDO
    {
        return $this->database_manager->getConnection($name);
    }

    /**
     * @return lcStorage
     */
    public function getStorage(): ?lcStorage
    {
        return $this->storage;
    }

    public function setStorage(lcStorage $storage = null)
    {
        $this->storage = $storage;
    }

    /**
     * @return lcAppSecurityUser
     */
    public function getUser(): ?lcAppSecurityUser
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

    /**
     * @return lcDataStorage
     */
    public function getDataStorage(): ?lcDataStorage
    {
        return $this->data_storage;
    }

    public function setDataStorage(lcDataStorage $data_storage = null)
    {
        $this->data_storage = $data_storage;
    }

    /**
     * @return lcCacheStore
     */
    public function getCache(): ?lcCacheStore
    {
        return $this->cache;
    }

    public function setCache(lcCacheStore $cache = null)
    {
        $this->cache = $cache;
    }
}
