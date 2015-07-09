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
 * @changed $Id: lcPropelConnection.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcPropelConnection extends PropelPDO
{
    // override propel's default logging methods
    protected static $defaultLogMethods = array(
        'PropelPDO::exec',
        'PropelPDO::query',
        'PropelPDO::prepare',
        'PropelPDO::beginTransaction',
        'PropelPDO::commit',
        'PropelPDO::rollback',
        'DebugPDOStatement::execute',
        'lcPropelConnection::exec',
        'lcPropelConnection::query',
        'lcPropelConnection::prepare',
    );

    const QUERY_CACHE_TIMEOUT_DEFAULT = 600;    // in seconds
    const QUERY_CACHE_TIMEOUT_MINUTE = 60;
    const QUERY_CACHE_TIMEOUT_DAY = 86400;

    const DEFAULT_CACHE_NAMESPACE = 'propel_pdo';

    /** @var lcEventDispatcher */
    private $event_dispatcher;

    /** @var lcApplicationConfiguration */
    private $lc_configuration;

    /** @var iDatabaseCacheProvider */
    private $query_cache_backend;

    private $log_cached_queries = false;
    private $query_cache_enabled = false;

    private $cache_prefix = 'db::';
    private $cached_query_count = 0;
    private $query_count = 0;

    private $is_php53_or_lower;

    public function __construct($dsn, $username = null, $password = null, $driver_options = array())
    {
        parent::__construct($dsn, $username, $password, $driver_options);

        $this->is_php53_or_lower = (version_compare(PHP_VERSION, '5.3.0') <= 0);
    }

    public function getLightcastConfiguration()
    {
        return $this->lc_configuration;
    }

    public function setLightcastConfiguration(lcApplicationConfiguration $configuration)
    {
        $this->lc_configuration = $configuration;
    }

    public function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function getEventDispatcher()
    {
        return $this->event_dispatcher;
    }

    #pragma mark - Overriden methods

    public function execTransactionWithLock(array $statements, array $locked_tables)
    {
        if (!$statements || !$locked_tables) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $this->lockTablesInTransaction($locked_tables);

        try {
            foreach ($statements as $statement) {
                $this->exec($statement);
                unset($statement);
            }

            $this->commitAndUnlockTables();
        } catch (Exception $e) {
            $this->rollbackAndUnlockTables();

            throw $e;
        }

        return true;
    }

    public function prepare($sql, $driver_options = array())
    {
        if (!$sql) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $this->query_count++;

        return parent::prepare($sql, $driver_options);
    }

    public function exec($sql)
    {
        if (!$sql) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $this->query_count++;

        if (is_string($sql)) {
            return parent::exec($sql);
        } elseif (is_array($sql)) {
            // execute multiply statements in a transaction
            $this->beginTransaction();
            $this->disableAutocommit();

            foreach ($sql as $statement) {
                try {
                    parent::exec($statement);
                    unset($statement);
                } catch (Exception $e) {
                    parent::rollBack();
                    throw $e;
                }
            }

            parent::commit();
            $this->enableAutocommit();
        }

        return false;
    }

    public function query()
    {
        $this->query_count++;

        $args = func_get_args();

        // php 5.3 or lower handles this differently
        if ($this->is_php53_or_lower) {
            $return = call_user_func_array(array($this, 'parent::query'), $args);
        } else {
            $return = call_user_func_array(array('parent', 'query'), $args);
        }

        return $return;
    }

    #pragma mark - Caching

    public function setQueryCacheBacked(iDatabaseCacheProvider $query_cache_backend = null)
    {
        $this->query_cache_backend = $query_cache_backend;
    }

    public function setQueryCacheEnabled($enabled = true)
    {
        $this->query_cache_enabled = $enabled;
    }

    public function getQueryCacheEnabled()
    {
        return $this->query_cache_enabled;
    }

    public function getQueryCacheBackend()
    {
        return $this->query_cache_backend;
    }

    public function getCachedQueryCount()
    {
        return $this->cached_query_count;
    }

    public function getCacheTimeout()
    {
        return self::QUERY_CACHE_TIMEOUT_DEFAULT;
    }

    public function invalidateCacheNamespace($namespace)
    {
        if ($this->query_cache_enabled && $this->query_cache_backend) {
            $this->cacheRemoveForNamespace($namespace);
        }
    }

    public function invalidateCachedRows($cache_label, $namespace = null)
    {
        if (!$cache_label) {
            assert(false);
            return;
        }

        if ($this->query_cache_enabled && $this->query_cache_backend) {
            $cache_key = $this->computeCacheKey($cache_label);
            assert(!empty($cache_key));

            if ($cache_key) {
                $this->cacheRemove($namespace, $cache_key);
            }
        }
    }

    public function cachedQueryRows($query, $cache_label, $namespace = null, $timeout = self::QUERY_CACHE_TIMEOUT_DEFAULT, $enable_cache = true, &$is_cached = false)
    {
        $query = (string)$query;
        $cache_label = (string)$cache_label;
        $namespace = isset($namespace) ? (string)$namespace : self::DEFAULT_CACHE_NAMESPACE;
        $timeout = isset($timeout) && $timeout ? (int)$timeout : self::QUERY_CACHE_TIMEOUT_DEFAULT;
        $enable_cache = (bool)$enable_cache;
        $is_cached = false;

        if (!$query || ($enable_cache && !$cache_label)) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $cache_key = null;

        if ($this->query_cache_enabled && $this->query_cache_backend && $enable_cache) {
            // lookup in cache
            $cache_key = $this->computeCacheKey($cache_label);

            assert(!empty($cache_key));

            if ($cache_key) {
                $cached_data = $this->cacheLookup($namespace, $cache_key);

                if ($cached_data) {
                    if (DO_DEBUG && $this->log_cached_queries) {
                        $this->log('CACHED QUERY: \'' . $namespace . ':' . $cache_label . '\' ' . $query, null, 'PropelPDO::query');
                    }

                    // set stats
                    $this->cached_query_count++;

                    $is_cached = true;

                    // yes, it's in the cache - return it
                    return $cached_data;
                }
            }
        }

        // not in cache - make a new query and cache the results

        $res = $this->query($query);

        if (!$res || !$res->rowCount()) {
            return null;
        }

        $rows = $res->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows || !is_array($rows)) {
            return null;
        }

        // store in cache
        if ($this->query_cache_enabled && $this->query_cache_backend && $enable_cache) {
            assert(!empty($cache_key));

            $this->cacheStore($namespace, $cache_key, $rows, $timeout);
        }

        return $rows;
    }

    private function computeCacheKey($cache_label)
    {
        assert(isset($cache_label));

        if (!$this->lc_configuration) {
            return null;
        }

        $cache_key = null;

        if (!$cache_label) {
            assert(false);
            return $cache_key;
        }

        $cache_key = $this->cache_prefix . $cache_label;

        return $cache_key;
    }

    private function cacheRemoveForNamespace($namespace)
    {
        assert(isset($namespace));

        $this->query_cache_backend->removeDbCacheForNamespace($namespace);

        $this->log('Cache namespace removal: \'' . $namespace, null, 'PropelPDO::query');
    }

    private function cacheRemove($namespace, $cache_key)
    {
        assert(isset($cache_key));

        $this->query_cache_backend->removeDbCache($namespace, $cache_key);

        $this->log('Cache removal: \'' . $namespace . ':' . $cache_key, null, 'PropelPDO::query');
    }

    private function cacheStore($namespace, $cache_key, array $rows, $timeout)
    {
        assert(isset($namespace));
        assert(isset($cache_key));
        assert(count($rows));
        assert((int)$timeout);

        $this->query_cache_backend->setDbCache($namespace, $cache_key, $rows, $timeout);

        $this->log('Cache store: \'' . $namespace . ':' . $cache_key . ', timeout: ' . $timeout, null, 'PropelPDO::query');
    }

    private function cacheLookup($namespace, $cache_key)
    {
        $cached_data = $this->query_cache_backend->getDbCache($namespace, $cache_key);
        return $cached_data;
    }

    #pragma mark - Utils

    public function getQueryCount()
    {
        return $this->query_count;
    }

    public function commitTransactionIfRunning()
    {
        try {
            // we add both as propel is doing an awful emulation and we can't be sure
            // if the transaction is really commited or not
            $this->commit();
            parent::exec('COMMIT');
        } catch (Exception $e) {
            fnothing($e);
            // in case a transaction is not running and PDO is complaining
        }
    }

    /*
     * A somewhat Oracle-like isolation level with respect to consistent (nonlocking) reads: Each consistent read, even within the same transaction, sets and reads its own fresh snapshot. See Section 14.2.7.2, “Consistent Nonlocking Reads”.

    For locking reads (SELECT with FOR UPDATE or LOCK IN SHARE MODE), InnoDB locks only index records, not the gaps before them, and thus permits the free insertion of new records next to locked records. For UPDATE and DELETE statements, locking depends on whether the statement uses a unique index with a unique search condition (such as WHERE id = 100), or a range-type search condition (such as WHERE id > 100). For a unique index with a unique search condition, InnoDB locks only the index record found, not the gap before it. For range-type searches, InnoDB locks the index range scanned, using gap locks or next-key (gap plus index-record) locks to block insertions by other sessions into the gaps covered by the range. This is necessary because “phantom rows” must be blocked for MySQL replication and recovery to work.
    */
    public function transactionReadCommited()
    {
        $this->commitTransactionIfRunning();

        return $this->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    /*
     * This is the default isolation level for InnoDB. For consistent reads, there is an important difference from the READ COMMITTED isolation level: All consistent reads within the same transaction read the snapshot established by the first read. This convention means that if you issue several plain (nonlocking) SELECT statements within the same transaction, these SELECT statements are consistent also with respect to each other. See Section 14.2.7.2, “Consistent Nonlocking Reads”.

    For locking reads (SELECT with FOR UPDATE or LOCK IN SHARE MODE), UPDATE, and DELETE statements, locking depends on whether the statement uses a unique index with a unique search condition, or a range-type search condition. For a unique index with a unique search condition, InnoDB locks only the index record found, not the gap before it. For other search conditions, InnoDB locks the index range scanned, using gap locks or next-key (gap plus index-record) locks to block insertions by other sessions into the gaps covered by the range.
    */
    public function transactionRepeatableRead()
    {
        $this->commitTransactionIfRunning();

        return $this->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    }

    /*
     * SELECT statements are performed in a nonlocking fashion, but a possible earlier version of a row might be used. Thus, using this isolation level, such reads are not consistent. This is also called a “dirty read.” Otherwise, this isolation level works like READ COMMITTED.
    */
    public function transactionReadUncommited()
    {
        $this->commitTransactionIfRunning();

        return $this->exec('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED');
    }

    /*
     * This level is like REPEATABLE READ, but InnoDB implicitly converts all plain SELECT statements to SELECT ... LOCK IN SHARE MODE if autocommit is disabled. If autocommit is enabled, the SELECT is its own transaction. It therefore is known to be read only and can be serialized if performed as a consistent (nonlocking) read and need not block for other transactions. (To force a plain SELECT to block if other transactions have modified the selected rows, disable autocommit.)
    */
    public function transactionReadSerializable()
    {
        $this->commitTransactionIfRunning();

        return $this->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
    }

    public function enableAutocommit()
    {
        return $this->exec('SET autocommit = 1');
    }

    public function disableAutocommit()
    {
        return $this->exec('SET autocommit = 0');
    }

    public function disableForeignKeyChecks()
    {
        return $this->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    public function enableForeignKeyChecks()
    {
        return $this->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Logs the method call or SQL using the Propel::log() method or a registered logger class.
     *
     * @uses      self::getLogPrefix()
     * @see       self::setLogger()
     *
     * @param string $msg Message to log.
     * @param integer $level Log level to use; will use self::setLogLevel() specified level by default.
     * @param string $methodName Name of the method whose execution is being logged.
     * @param array $debugSnapshot Previous return value from self::getDebugSnapshot().
     */
    public function log($msg, $level = null, $methodName = null, array $debugSnapshot = null)
    {
        // If logging has been specifically disabled, this method won't do anything
        if (!$this->getLoggingConfig('enabled', true)) {
            return;
        }

        // If the method being logged isn't one of the ones to be logged, bail
        if (!in_array($methodName, $this->getLoggingConfig('methods', self::$defaultLogMethods))) {
            return;
        }

        // If a logging level wasn't provided, use the default one
        if ($level === null) {
            $level = Propel::LOG_DEBUG;
        }

        // Determine if this query is slow enough to warrant logging
        if ($this->getLoggingConfig("onlyslow", self::DEFAULT_ONLYSLOW_ENABLED)) {
            $now = $this->getDebugSnapshot();
            if ($now['microtime'] - $debugSnapshot['microtime'] < $this->getLoggingConfig("details.slow.threshold", self::DEFAULT_SLOW_THRESHOLD)) {
                return;
            }
        }

        // If the necessary additional parameters were given, get the debug log prefix for the log line
        if ($methodName && $debugSnapshot) {
            $msg = $this->getLogPrefix($methodName, $debugSnapshot) . $msg;
        }

        // We won't log empty messages
        if (!$msg) {
            return;
        }

        // Delegate the actual logging forward
        if ($this->logger) {
            $this->logger->log($msg, $level);
        } else {
            Propel::log($msg, $level);
        }
    }

    public function lockTables(array $tables)
    {
        if (!$tables) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $l = array();

        foreach ($tables as $table => $write_lock) {
            $write_lock = (bool)$write_lock;
            $table = (string)$table;

            $l[] = $table . ($write_lock ? ' WRITE' : ' READ');

            unset($table, $write_lock);
        }

        $sql = 'LOCK TABLES ' . implode(', ', $l);

        return $this->exec($sql);
    }

    public function unlockTables()
    {
        return $this->exec('UNLOCK TABLES');
    }

    public function lockTablesInTransaction(array $tables)
    {
        if (!$tables) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        // http://dev.mysql.com/doc/refman/5.0/en/lock-tables-and-transactions.html

        $this->beginTransaction();
        $this->disableAutocommit();
        $this->lockTables($tables);

        return true;
    }

    public function rollbackAndUnlockTables()
    {
        $this->unlockTables();
        $this->rollBack();
        $this->enableAutocommit();

        return true;
    }

    public function commitAndUnlockTables()
    {
        $this->unlockTables();
        $this->commit();
        $this->enableAutocommit();

        return true;
    }

    public function quoteTableName($string)
    {
        return '`' . $string . '`';
    }

    public function quoteTrimmed($string, $parameter_type = PDO::PARAM_STR)
    {
        $quoted = parent::quote($string, $parameter_type);

        if (!$quoted) {
            return null;
        }

        $quoted = substr($quoted, 1, -1);

        return $quoted;
    }
}