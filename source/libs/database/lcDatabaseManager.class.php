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

class lcDatabaseManager extends lcResidentObj implements iProvidesCapabilities, iDatabaseManager, iDebuggable
{
    public const DEFAULT_DB = 'primary';

    /** @var ?lcPropelLogger */
    protected ?lcPropelLogger $propel_logger = null;

    protected array $propel_config = [];

    /**
     * @var ?lcDatabaseMigrationsHelper
     */
    protected ?lcDatabaseMigrationsHelper $migration_helper = null;

    /** @var array lcDatabase[] */
    private array $dbs = [];
    private string $default_database = self::DEFAULT_DB;
    private bool $propel_initialized = false;

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
            $this->default_database = $this->default_database ?: self::DEFAULT_DB;
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
                    if (isset($db['autoconnect']) && $db['autoconnect']) {
                        $db_object->connect();
                    }

                    // send an event ONCE - for the default db only!
                    if ($db['is_default']) {
                        $res = [
                            'is_default' => $db['is_default'],
                            'name' => $db['name'],
                            // Removed as it causes a side effect - the db connection is initialized at all times, even if we don't need it
                            //                            'connection' => $db_object->getConnection(),
                        ];

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
        $magic_quotes_gpc = !version_compare(PHP_VERSION, '5.3.0') && ini_get('magic_quotes_gpc');
        // magic quotes are deprecated from 5.3.x up
        $magic_quotes_sybase = !version_compare(PHP_VERSION, '5.3.0') && ini_get('magic_quotes_sybase');
        // magic quotes are deprecated from 5.3.x up
        $ze1_compatibility_mode = (bool)ini_get('ze1_compatibility_mode');

        if ($magic_quotes_gpc || $magic_quotes_sybase || $ze1_compatibility_mode) {
            throw new lcSystemException("Propel requires the following PHP settings:\n
                    - ze1_compatibility_mode = Off (Currently: " . ($ze1_compatibility_mode ? 'On' : 'Off') . ')
                    - magic_quotes_sybase = Off (Currently: ' . ($magic_quotes_sybase ? 'On' : 'Off') . ')
                    - magic_quotes_gpc = Off (Currently: ' . ($magic_quotes_gpc ? 'On' : 'Off') . ')');
        }
        // @codingStandardsIgnoreEnd

        $this->makePropelConfig();

        // instance pooling - we disable it by default from LC 1.5
        $enable_instance_pooling = isset($this->propel_config['instance_pooling']) && $this->propel_config['instance_pooling'];

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
        $propel_config = [];

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

    private function getPropelConfigForDatabase(array $db_config): ?array
    {
        if (!isset($db_config['url']) || !isset($db_config['datasource'])) {
            return null;
        }

        $params = [];

        $params_tmp = explode(':', $db_config['url']);

        $params['phptype'] = $params_tmp[0];

        $params_tmp = explode(';', $params_tmp[1]);

        foreach ($params_tmp as $val) {
            $val = explode('=', $val);
            $params[$val[0]] = $val[1];
            unset($val);
        }

        unset($params_tmp);

        $options = [];
        $attributes = [];

        $persistent_connections = !isset($db_config['persistent_connections']) || $db_config['persistent_connections'];
        $emulated_prepare_statements = isset($db_config['emulate_prepares']) && $db_config['emulate_prepares'];

        // persistent connections are now enabled by default from LC 1.5
        if ($persistent_connections) {
            $options['ATTR_PERSISTENT'] = ['value' => true];
        }

        // emulated prepared statements - as of 1.5 it's disabled by default
        // http://stackoverflow.com/questions/10113562/pdo-mysql-use-pdoattr-emulate-prepares-or-not

        if ($emulated_prepare_statements) {
            $attributes = ['ATTR_EMULATE_PREPARES' => ['value' => true,],];
        }

        $username = isset($db_config['user']) ? (string)$db_config['user'] : null;
        $password = $db_config['password'] ?? null;

        $params['username'] = $username;
        $params['password'] = $password;

        //$propel_class = DO_DEBUG ? $this->propel_class_debug :
        // $this->propel_class_release;
        $propel_class = lcPropelDatabase::PROPEL_CONNECTION_CLASS;

        return [
            'datasource' => $db_config['datasource'],
            'config' => [
                'adapter' => $params['phptype'],
                'connection' => [
                    'dsn' => $db_config['url'],
                    'user' => $params['username'],
                    'password' => $params['password'],
                    'classname' => $propel_class,
                    'options' => $options,
                    'attributes' => $attributes,
                    'settings' => ['charset' => ['value' => $db_config['charset'] ?? lcPropelDatabase::DEFAULT_CHARSET]],
                ],
            ],
        ];
    }

    /**
     * @return lcDatabaseMigrationsHelper|lcSysObj
     * @throws lcConfigException
     * @throws lcSystemException
     */
    public function getMigrationsHelper()
    {
        if (!$this->migration_helper) {

            $cfg = $this->configuration;
            $clname = (string)$cfg['db.migrations.helper_class'];

            if (!$clname) {
                throw new lcConfigException('No migrations helper set in configuration');
            }

            if (!class_exists($clname)) {
                throw new lcSystemException('Migrations helper class does not exist');
            }

            // init it
            $manager = new $clname();

            if (!($manager instanceof lcSysObj)) {
                throw new lcSystemException('Migrations helper is not a valid object');
            }

            if ($manager instanceof lcDatabaseMigrationsHelper) {
                /** @noinspection PhpParamsInspection */
                $manager->setDatabaseConnection($this->getConnection());
            }

            $manager->setLogger($this->logger);
            $manager->setI18n($this->i18n);
            $manager->setConfiguration($this->configuration);
            $manager->setEventDispatcher($this->event_dispatcher);

            // start it up
            $manager->initialize();

            $this->migration_helper = $manager;
        }

        return $this->migration_helper;
    }

    /**
     * @param $name
     * @return PDO|null
     */
    public function getConnection($name = null): ?PDO
    {
        $db = $this->getDatabase($name);

        if (!isset($db)) {
            return null;
        }

        return $db->getConnection();
    }

    /**
     * @param null $name
     * @return lcDatabase
     */
    public function getDatabase($name = null): ?lcDatabase
    {
        if (!isset($name)) {
            $name = $this->default_database;
        }

        return $this->dbs[$name] ?? null;
    }

    public function shutdown()
    {
        // shutdown databases
        if ($this->dbs) {
            foreach ($this->dbs as $name => $obj) {
                /** @var lcDatabase $obj */

                // send a shutdown event
                $this->getEventDispatcher()->notify(new lcEvent('db.shutdown', $obj));

                $obj->shutdown();
                unset($this->dbs[$name]);

                unset($obj);
            }
        }

        $this->dbs = [];

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

    #pragma mark - Propel

    public function getCapabilities(): array
    {
        return ['database'];
    }

    public function getDebugInfo(): array
    {
        return [
            'databases' => array_keys($this->dbs),
            'primary' => self::DEFAULT_DB,
        ];
    }

    public function getShortDebugInfo(): array
    {
        $databases = $this->dbs;

        $debug_info = [];

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

    public function getNames(): array
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
