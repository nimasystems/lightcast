<?php
declare(strict_types=1);

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

class lcPDODatabase extends lcDatabase
{
    public const DEFAULT_CHARSET = 'utf8';
    public const DEFAULT_COLLATION = 'utf8_general_ci';

    protected $charset = self::DEFAULT_CHARSET;
    protected $collation = self::DEFAULT_COLLATION;

    /**
     * @var PDO
     */
    protected $conn;

    protected $driver;
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
        $charset = isset($this->options['charset']) ? (string)$this->options['charset'] : null;
        $collation = isset($this->options['collation']) ? (string)$this->options['collation'] : null;

        if (!$url) {
            throw new lcConfigException('No database connection url defined');
        }

        if ($driver) {
            $this->setDriver($driver);
        }

        $this->setConnectionUrl($url);
        $this->setUsername($user);
        $this->setPassword($password);
        $this->setCharset($charset);
        $this->setCollation($collation);
    }

    public function shutdown()
    {
        $this->conn = null;

        parent::shutdown();
    }

    public function getSQLCount(): int
    {
        return 0;
    }

    public function setOptions(array $options): lcDatabase
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setPersistentConnections($persistent_connections = false)
    {
        $this->persistentc = $persistent_connections;
    }

    public function getDriver()
    {
        return $this->driver;
    }

    public function setDriver($driver_name)
    {
        $this->driver = $driver_name;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function getCollation()
    {
        return $this->collation;
    }

    public function setCollation($collation)
    {
        $this->collation = $collation;
    }

    public function getPersistentConnectionsUsage()
    {
        return $this->persistentc;
    }

    // getters

    public function getConnectionUrl()
    {
        return $this->connection_url;
    }

    public function setConnectionUrl($url)
    {
        $this->connection_url = $url;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getConnection(): PDO
    {
        return $this->connect();
    }

    public function connect(): PDO
    {
        if ($this->conn) {
            return $this->conn;
        }

        assert($this->options);

        $options = ($this->persistentc) ? [PDO::ATTR_PERSISTENT => true] : [];

        try {
            $pdo_class = 'PDO';

            $this->conn = new $pdo_class($this->connection_url, $this->username, $this->password, $options);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->charset) {
                $this->conn->exec('SET NAMES ' . $this->charset .
                    ($this->collation ? ' COLLATE ' . $this->collation : null));
            }

            $tz = isset($this->options['timezone']) ? (string)$this->options['timezone'] : null;

            if ($tz) {
                $this->conn->exec('SET time_zone = ' . $this->conn->quote($tz));
            }

        } catch (Exception $e) {
            throw new lcDatabaseException('PDO cannot connect to database: ' . $e->getMessage(), null, $e);
        }

        parent::connect();

        return $this->conn;
    }

    public function isConnected(): bool
    {
        return (bool)$this->conn;
    }

    public function reconnect()
    {
        $this->disconnect();

        return $this->connect();
    }

    public function disconnect(): bool
    {
        if (!$this->conn) {
            return true;
        }

        $this->conn = null;

        return true;
    }
}
