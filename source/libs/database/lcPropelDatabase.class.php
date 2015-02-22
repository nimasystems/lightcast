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
 * @changed $Id: lcPropelDatabase.class.php 1498 2014-01-09 04:57:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1498 $
 */

class lcPropelDatabase extends lcDatabase implements iDebuggable, iDatabaseWithCache
{
    const PROPEL_CONNECTION_CLASS = 'lcPropelConnection';
    const DEFAULT_CHARSET = 'utf8';

    protected $conn;
    protected $db_cache;
	protected $propel_logger;
    
    public function shutdown()
    {
        // check if we have a loose transaction somewhere
        if ($this->conn && $this->conn->isInTransaction())
        {
            $this->err('Unfinished propel transactions were detected: ' . $this->conn->getNestedTransactionCount());

            if (DO_DEBUG)
            {
                throw new lcDatabaseException($this->t('Unfinished propel transactions (' . $this->conn->getNestedTransactionCount() . ')'));
            }
        }

        $this->disconnect();

        $this->db_cache = $this->conn = $this->propel_logger = null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug = array(
            'sql_count' => $this->getSQLCount(),
            'cached_sql_count' => $this->getCachedSQLCount(),
            'cache_enabled' => $this->getIsCacheEnabled(),
            'cache_timeout' => $this->getCacheTimeout()
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug = array(
            'sql_count' => $this->getSQLCount(),
            'cached_sql_count' => $this->getCachedSQLCount(),
            'cache_enabled' => $this->getIsCacheEnabled()
        );

        return $debug;
    }
    
    public function getDatabaseCache() 
    {
    	return $this->db_cache;
    }
    
    public function setDatabaseCache(iDatabaseCacheProvider $cache_storage = null)
    {
    	$this->db_cache = $cache_storage;
    	
    	if ($this->conn) {
	    	$this->conn->setQueryCacheBacked($this->db_cache);
    	}
    }
    
    public function setPropelLogger(lcPropelLogger $logger = null) 
    {
    	$this->propel_logger = $logger;
    	
    	if ($this->conn) {
    		$this->conn->setLogger($this->propel_logger);
    	}
    }

    public function getSQLCount()
    {
        $this->connect();

        return $this->conn->getQueryCount();
    }
    
    public function getConnection()
    {
        if (!$this->conn)
        {
            $this->connect();
        }

        return $this->conn;
    }

    public function isConnected()
    {
        return $this->conn ? true : false;
    }

    public function connect()
    {
        if ($this->conn)
        {
            return $this->conn;
        }

        try
        {
            $this->conn = lcPropel::getConnection($this->options['datasource']);

            // debugging
            if (DO_DEBUG)
            {
                $this->conn->useDebug(true);
            }

            if ($this->propel_logger)
            {
            	$this->conn->setLogger($this->propel_logger);
            }
            
            // set the default charset
            // IMPORTANT: SQL quote / escaping methods are HIGHLY affected by
            // this!
            $charset = isset($this->options['charset']) ? (string)$this->options['charset'] : self::DEFAULT_CHARSET;
            
            $this->conn->exec('SET NAMES \'' . $this->conn->quoteTrimmed($charset) . '\'');

            // initialize the connection with lightcast specific vars
            $this->conn->setEventDispatcher($this->event_dispatcher);
            $this->conn->setLightcastConfiguration($this->configuration);

            // cache enabled or not
            if (isset($this->options['caching']) && (bool)$this->options['caching'])
            {
            	$this->conn->setQueryCacheEnabled(true);
            }
            
            if ($this->db_cache && $this->db_cache instanceof iDatabaseCacheProvider)
            {
                $this->conn->setQueryCacheBacked($this->db_cache);
            }

            return $this->conn;
        }
        catch(Exception $e)
        {
            throw new lcDatabaseException('Cannot connect to database: ' . $e->getMessage(), null, $e);
        }
    }

    public function reconnect()
    {
        $this->disconnect();

        if ($res = $this->connect())
        {
            if (DO_DEBUG)
            {
                $this->debug('Database reconnected');
            }
        }

        return $res;
    }

    public function disconnect()
    {
        if (!$this->conn)
        {
            return true;
        }

        $this->conn = null;

        if (DO_DEBUG)
        {
            $this->debug('Database disconnected');
        }

        return true;
    }

    #pragma mark - iDatabaseWithCache methods

    public function getCachedSQLCount()
    {
        return $this->conn->getCachedQueryCount();
    }

    public function getCacheTimeout()
    {
        return $this->conn->getCacheTimeout();
    }

    public function getIsCacheEnabled()
    {
        return $this->conn->getQueryCacheEnabled();
    }

}
?>