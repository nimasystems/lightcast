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
 * @changed $Id: lcPDODatabase.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcPDODatabase extends lcDatabase
{
	protected $conn;

	protected $driver;
	protected $charset;
	protected $persistentc;
	protected $connection_url;
	protected $username;
	protected $password;

	public function initialize()
	{
		parent::initialize();

		$driver = isset($this->options['driver']) ? (string)$this->options['driver'] : null;
		$url = isset($this->options['url']) ? (string)$this->options['url'] : null;
		$password = isset($this->options['password']) ? (string)$this->options['password'] : null;
		$user = isset($this->options['user']) ? (string)$this->options['user'] : null;
		
		if (!$url)
		{
			throw new lcConfigException('No database connection url defined');
		}
		
		if ($driver)
		{
			$this->setDriver($driver);
		}
		
		$this->setConnectionUrl($url);
		$this->setUsername($user);
		$this->setPassword($password);
	}

	public function shutdown()
	{
		$this->conn = null;

		parent::shutdown();
	}

	public function getSQLCount()
	{
		return false;
	}

	public function setOptions(array $options)
	{
		$this->options = $options;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function setDriver($driver_name)
	{
		$this->driver = $driver_name;
	}

	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	public function setPersistentConnections($persistent_connections = false)
	{
		$this->persistentc = $persistent_connections;
	}

	public function setConnectionUrl($url)
	{
		$this->connection_url = $url;
	}

	public function setUsername($username)
	{
		$this->username = $username;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	// getters
	public function getDriver()
	{
		return $this->driver;
	}

	public function getCharset()
	{
		return $this->charset;
	}

	public function getPersistentConnectionsUsage()
	{
		return $this->persistentc;
	}

	public function getConnectionUrl()
	{
		return $this->connection_url;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function getConnection()
	{
		return $this->connect();
	}

	public function isConnected()
	{
		return $this->conn ? true : false;
	}

	public function connect()
	{
		if ($this->conn) 
		{
			return true;
		}

		assert($this->options);

		$options = ($this->persistentc) ? array(PDO::ATTR_PERSISTENT => true) : array();

		try
		{
			$pdo_class = 'PDO';

			$this->conn = new $pdo_class($this->connection_url, $this->username, $this->password, $options);
		}
		catch(Exception $e)
		{
			throw new lcDatabaseException('PDO cannot connect to database: '.$e->getMessage(), null, $e);
		}

		parent::connect();

		return $this->conn;
	}

	public function reconnect()
	{
		$this->disconnect();

		return $this->connect();
	}

	public function disconnect()
	{
		if (!$this->connected) 
		{
			return true;
		}

		$this->conn = null;

		return true;
	}
}

?>