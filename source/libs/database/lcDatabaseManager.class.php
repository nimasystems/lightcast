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
 * @changed $Id: lcDatabaseManager.class.php 1472 2013-11-16 14:30:20Z mkovachev
 * $
 * @author $Author: mkovachev $
 * @version $Revision: 1498 $
 */
class lcDatabaseManager extends lcResidentObj implements iProvidesCapabilities, iDatabaseManager, iDebuggable
{
    const DEFAULT_DB = 'primary';

    /** @var lcDatabaseMigrationsManager */
    protected $migrations_manager;
    /** @var lcLogger */
    protected $propel_logger;
    protected $propel_config;
    /** @var array lcDatabase[] */
    private $dbs = array();
    private $default_database = self::DEFAULT_DB;
    private $propel_initialized;

    public function initialize()
    {
        parent::initialize();

        $this->loadDatabaseConfiguration();
    }

    private function loadDatabaseConfiguration()
    {
        // use_database
        $use_database = $this->configuration['db.use_database'];

        if (!$use_database) {
            return;
        }

        $databases = $this->configuration['db.databases'];

        // default database
        if (isset($this->configuration['db.default_database'])) {
            $this->default_database = (string)$this->configuration['db.default_database'];
            $this->default_database = $this->default_database ? $this->default_database : self::DEFAULT_DB;
        }

        // propel usage
        $use_propel = $this->configuration['db.use_propel'];

        if ($use_propel) {
            $this->initializePropel();
        }

        if (isset($databases) && is_array($databases)) {
            foreach ($databases as $name => $db) {
                try {
                    if (isset($db['enabled']) && !$db['enabled']) {
                        continue;
                    }

                    if (!class_exists($db['classname'])) {
                        throw new lcDatabaseException('Class does not exist: ' . $db['classname']);
                    }

                    $db['name'] = $name;
                    $db['is_default'] = ($name == $this->default_database);

                    $db_object = new $db['classname']($db);

                    if (!($db_object instanceof lcDatabase)) {
                        throw new lcSystemException('Database object not valid - does not inherit from lcDatabase');
                    }

                    $db_object->setDatabaseManager($this);
                    $db_object->setEventDispatcher($this->event_dispatcher);
                    $db_object->setConfiguration($this->configuration);
                    $db_object->setClassAutoloader($this->class_autoloader);
                    $db_object->setOptions($db);
                    $db_object->initialize();

                    $this->dbs[$name] = $db_object;

                    if (DO_DEBUG) {
                        $this->debug('Database connection object (' . $name . '/' . get_class($db_object) . ') initialization');
                    }

                    // try to connect if option set
                    if (isset($db['autoconnect']) && (bool)$db['autoconnect']) {
                        $db_object->connect();
                    }

                    // send an event ONCE - for the default db only!
                    if ($db['is_default']) {
                        $res = array(
                            'is_default' => $db['is_default'],
                            'name' => $db['name'],
                            'connection' => $db_object->getConnection()
                        );

                        $connection = $this->dbs[$name];

                        // send a startup event
                        $this->event_dispatcher->notify(new lcEvent('db.connect', $connection, $res));

                        // attach to provide a late bound notification
                        $this->event_dispatcher->attachConnectListener('db.connect', $this, function () use ($res, $connection) {
                            return new lcEvent('db.connect', $connection, $res);
                        });

                        if (DO_DEBUG) {
                            $this->debug('Database connected (' . $name . ')');
                        }
                    }

                    unset($db, $db_object);
                } catch (Exception $e) {
                    throw new lcSystemException('Could not initialize database adapter (' . $name . '): ' . $e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        unset($databases);
    }

    protected function initializePropel()
    {
        if ($this->propel_initialized) {
            return;
        }

        // required by Propel
        // @codingStandardsIgnoreStart
        $magic_quotes_gpc = version_compare(PHP_VERSION, '5.3.0') ? false : (bool)ini_get('magic_quotes_gpc');
        // magic quotes are deprecated from 5.3.x up
        $magic_quotes_sybase = version_compare(PHP_VERSION, '5.3.0') ? false : (bool)ini_get('magic_quotes_sybase');
        // magic quotes are deprecated from 5.3.x up
        $ze1_compatibility_mode = (bool)ini_get('ze1_compatibility_mode');

        if ($magic_quotes_gpc || $magic_quotes_sybase || $ze1_compatibility_mode) {
            throw new lcSystemException("Propel requires the following PHP settings:\n
					- ze1_compatibility_mode = Off (Currently: " . ($ze1_compatibility_mode ? 'On' : 'Off') . ")
					- magic_quotes_sybase = Off (Currently: " . ($magic_quotes_sybase ? 'On' : 'Off') . ")
					- magic_quotes_gpc = Off (Currently: " . ($magic_quotes_gpc ? 'On' : 'Off') . ")");
        }
        // @codingStandardsIgnoreEnd

        $this->makePropelConfig();

        // instance pooling - we disable it by default from LC 1.5
        $enable_instance_pooling = isset($this->propel_config['instance_pooling']) ? (bool)$this->propel_config['instance_pooling'] : false;

        if ($enable_instance_pooling) {
            Propel::enableInstancePooling();
        } else {
            Propel::disableInstancePooling();
        }

        // this is how we give propel access to our app! by making event
        // dispatcher static!
        // and not allowing it in lcApp as a static to everyone.
        lcPropel::setEventDispatcher($this->event_dispatcher);
        lcPropel::setConfiguration($this->propel_config);
        lcPropel::setI18n($this->i18n);

        $cache = $this->configuration->getCache();

        if ($cache) {
            $cache_key = $this->configuration->getUniqueId() . '_lcPropel';
            lcPropel::setCache($this->configuration->getCache(), $cache_key);
        }

        if ($this->propel_logger) {
            lcPropel::setLogger($this->propel_logger);
        }

        assert(!lcPropel::isInit());

        lcPropel::initialize();

        $this->propel_initialized = true;
    }

    private function makePropelConfig()
    {
        $propel_config = array();

        $default_datasource = null;
        $dbs = $this->configuration['db.databases'];

        if ($dbs && is_array($dbs)) {

            $c = 0;

            foreach ($dbs as $db) {

                if (isset($db['enabled']) && !$db['enabled']) {
                    $c++;
                    continue;
                }

                if ($db['classname'] != 'lcPropelDatabase') {
                    $c++;
                    continue;
                }

                $cfg = $this->getPropelConfigForDatabase($db);

                if (!$cfg) {
                    assert(false);
                    continue;
                }

                $propel_config['propel']['datasources'][$cfg['datasource']] = $cfg['config'];

                if (!$default_datasource && ($c == 0 || (isset($db['default']) && $db['default']))) {
                    $default_datasource = $db['datasource'];
                }

                unset($db);

                $c++;
            }
        }

        $propel_config['propel']['datasources']['default'] = $default_datasource;

        $this->propel_config = $propel_config;
    }

    private function getPropelConfigForDatabase(array $db_config)
    {
        if (!isset($db_config['url']) || !isset($db_config['datasource'])) {
            assert(false);
            return null;
        }

        $params = array();

        $params_tmp = explode(':', $db_config['url']);

        $params['phptype'] = $params_tmp[0];

        $params_tmp = explode(';', $params_tmp[1]);

        foreach ($params_tmp as $val) {
            $val = explode('=', $val);
            $params[$val[0]] = $val[1];
            unset($val);
        }

        unset($params_tmp);

        $options = array();
        $attributes = array();

        $persistent_connections = isset($db_config['persistent_connections']) ? (bool)$db_config['persistent_connections'] : true;
        $emulated_prepare_statements = isset($db_config['emulate_prepares']) ? (bool)$db_config['emulate_prepares'] : false;

        // persistent connections are now enabled by default from LC 1.5
        if ($persistent_connections) {
            $options['ATTR_PERSISTENT'] = array('value' => true);
        }

        // emulated prepared statements - as of 1.5 it's disabled by default
        // http://stackoverflow.com/questions/10113562/pdo-mysql-use-pdoattr-emulate-prepares-or-not

        if ($emulated_prepare_statements) {
            $attributes = array('ATTR_EMULATE_PREPARES' => array('value' => true,),);
        }

        $username = isset($db_config['user']) ? (string)$db_config['user'] : null;
        $password = isset($db_config['password']) ? $db_config['password'] : null;

        $params['username'] = $username;
        $params['password'] = $password;

        //$propel_class = DO_DEBUG ? $this->propel_class_debug :
        // $this->propel_class_release;
        $propel_class = lcPropelDatabase::PROPEL_CONNECTION_CLASS;

        $ret = array(
            'datasource' => $db_config['datasource'],
            'config' => array(
                'adapter' => $params['phptype'],
                'connection' => array(
                    'dsn' => $db_config['url'],
                    'user' => $params['username'],
                    'password' => $params['password'],
                    'classname' => $propel_class,
                    'options' => $options,
                    'attributes' => $attributes,
                    'settings' => array('charset' => array('value' => isset($db_config['charset']) ? $db_config['charset'] : lcPropelDatabase::DEFAULT_CHARSET))
                )
            )
        );

        return $ret;
    }

    public function shutdown()
    {
        // shutdown migrations manager if available
        if ($this->migrations_manager) {
            $this->migrations_manager->shutdown();
            $this->migrations_manager = null;
        }

        // shutdown databases
        if ($this->dbs && is_array($this->dbs)) {
            foreach ($this->dbs as $name => $obj) {
                /** @var lcDatabase $obj */

                // send a shutdown event
                $this->getEventDispatcher()->notify(new lcEvent('db.shutdown', $obj));

                $obj->shutdown();
                unset($this->dbs[$name]);

                unset($obj);
            }
        }

        $this->dbs = $this->migrations_manager = null;

        // shutdown the Propel logger
        if ($this->propel_logger) {
            $this->propel_logger->shutdown();
            $this->propel_logger = null;
        }

        // shutdown lcPropel
        if ($this->propel_initialized) {
            lcPropel::shutdown();
        }

        parent::shutdown();
    }

    public function getCapabilities()
    {
        return array('database');
    }

    public function getDebugInfo()
    {
        $debug = array(
            'databases' => array_keys($this->dbs),
            'primary' => self::DEFAULT_DB
        );

        return $debug;
    }

    #pragma mark - Propel

    public function getShortDebugInfo()
    {
        $databases = $this->dbs;

        $debug_info = array();

        if ($databases) {
            foreach ($databases as $adapter_name => $db) {
                if ($db instanceof iDebuggable) {
                    $db_debug = $db->getShortDebugInfo();

                    if ($db_debug) {
                        foreach ($db_debug as $key => $val) {
                            $dbg_key = $adapter_name . '_' . $key;

                            $debug_info[$dbg_key] = $val;

                            unset($key, $val);
                        }
                    }

                    unset($db_debug);
                }

                unset($adapter_name, $db);
            }
        }

        return $debug_info;
    }

    public function getMigrationsManager()
    {
        // factory method
        if ($this->migrations_manager) {
            return $this->migrations_manager;
        }

        $cfg = $this->configuration;
        $clname = (string)$cfg['db.migrations.manager'];

        if (!$clname) {
            throw new lcConfigException('No migrations manager set in configuration');
        }

        try {
            if (!class_exists($clname)) {
                throw new lcSystemException('Migrations manager\'s class does not exist');
            }

            // init it
            /** @var lcDatabaseMigrationsManager $manager */
            $manager = new $clname();

            if (!$manager || !($manager instanceof iDatabaseMigrationsManager)) {
                throw new lcSystemException('Migrations manager is not valid');
            }

            $manager->setLogger($this->logger);
            $manager->setI18n($this->i18n);
            $manager->setConfiguration($this->configuration);
            $manager->setEventDispatcher($this->event_dispatcher);

            // start it up
            $manager->initialize();

            $this->migrations_manager = $manager;

            return $this->migrations_manager;
        } catch (Exception $e) {
            throw new lcSystemException('Could not initialize migrations manager \'' . $clname . '\': ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getProjectMigrationsTarget($name = lcDatabasesConfigHandler::DEFAULT_PRIMARY_ADAPTER_NAME)
    {
        $name = isset($name) ? (string)$name : lcDatabasesConfigHandler::DEFAULT_PRIMARY_ADAPTER_NAME;

        $cfg = $this->configuration;

        // check if the database is found and if it has a migrations object set
        if (!$this->dbs) {
            throw new lcNotAvailableException('No databases available');
        }

        $dbs = array_keys($this->dbs);

        if (!in_array($name, $dbs)) {
            throw new lcNotAvailableException('Database adapter \'' . $name . '\' does not exist or is not initialized');
        }

        $database = $this->getDatabase($name);

        if (!$database) {
            throw new lcDatabaseException('Could not obtain a valid database for adapter \'' . $name . '\'');
        }

        // include the main schema filename which may or may not include the
        // migration classes
        $migrations_dir = (string)$cfg['db.migrations.migrations_dir'];

        if (!$migrations_dir) {
            throw new lcConfigException('Invalid project migrations dir');
        }

        $migrations_filename = $cfg->getProjectDir() . DS . $migrations_dir . DS . $name . '.php';

        if (!file_exists($migrations_filename)) {
            throw new lcIOException('Migrations filename does not exist');
        }

        // try to include it
        /** @noinspection PhpIncludeInspection */
        include_once($migrations_filename);

        // check if the class exists
        $clname = 'project' . lcInflector::camelize($name, false) . 'MigrationsTarget';

        if (!class_exists($clname)) {
            throw new lcNotAvailableException('Migrations target for database adapter \'' . $name . '\' does not exist (' . $clname . ')');
        }

        // init and verify the class
        /** @var iDatabaseMigrationsTarget $cl */
        $cl = new $clname();

        if (!$cl || !($cl instanceof iDatabaseMigrationsTarget)) {
            throw new lcSystemException('Migrations target \'' . $clname . '\' is not valid');
        }

        // initialize it
        $cl->setDatabase($database);

        // assign logger
        if ($cl instanceof lcLoggingObj) {
            $cl->setLogger($this->getLogger());
        }

        return $cl;
    }

    /**
     * @param null $name
     * @return lcDatabase
     */
    public function getDatabase($name = null)
    {
        if (!isset($name)) {
            $name = $this->default_database;
        }

        return isset($this->dbs[$name]) ? $this->dbs[$name] : null;
    }

    public function getConnection($name = null)
    {
        $db = $this->getDatabase($name);

        if (!isset($db)) {
            return null;
        }

        return $db->getConnection();
    }

    public function getNames()
    {
        return array_keys($this->dbs);
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        parent::setEventDispatcher($event_dispatcher);

        if ($this->propel_initialized) {
            // pass to Propel
            lcPropel::setEventDispatcher($this->event_dispatcher);
        }
    }

    public function setConfiguration(lcConfiguration $configuration)
    {
        parent::setConfiguration($configuration);

        if ($this->propel_initialized) {
            // pass to Propel
            lcPropel::setConfiguration($this->configuration);
        }
    }

    public function setI18n(lcI18n $i18n = null)
    {
        parent::setI18n($i18n);

        if ($this->propel_initialized) {
            // pass to Propel
            lcPropel::setI18n($this->i18n);
        }
    }

    public function setLogger(lcLogger $logger = null)
    {
        parent::setLogger($logger);

        if ($logger) {
            if (!$this->propel_logger) {
                $this->propel_logger = new lcPropelLogger();
                $this->propel_logger->setEventDispatcher($this->event_dispatcher);
                $this->propel_logger->setConfiguration($this->configuration);
                $this->propel_logger->initialize();
            }

            $this->propel_logger->setLogger($logger);
        } else {
            $this->propel_logger = null;
        }

        lcPropel::setLogger($this->propel_logger);

        // pass to connections
        if ($this->dbs) {
            foreach ($this->dbs as $db) {

                if ($db instanceof lcPropelDatabase) {
                    $db->setPropelLogger($this->propel_logger);
                }

                unset($db);
            }
        }
    }
}
