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
 * @changed $Id: lcDatabaseMigrationsManager.class.php 1455 2013-10-25 20:29:31Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcDatabaseMigrationsManager extends lcSysObj implements iDatabaseMigrationsManager
{
    const DEFAULT_MIGRATION_TABLE_NAME = 'db_migration';

    const ERR_UNKNOWN = 1;
    const ERR_INVALID_PARAMS = 2;
    const ERR_SCHEMA_ALREADY_INSTALLED = 10;
    const ERR_SCHEMA_NOT_INSTALLED = 11;
    const ERR_SCHEMA_UPGRADE = 12;

    protected $migration_table_name = self::DEFAULT_MIGRATION_TABLE_NAME;
    private $migrations_target_class_name;

    private $current_target;
    private $conn;
    private $current_migrations_version;

    public function initialize()
    {
        parent::initialize();

        $cfg = $this->configuration;

        // custom migration table name
        $migration_table_name = isset($cfg['db.migrations.table_name']) ? (string)$cfg['db.migrations.table_name'] : self::DEFAULT_MIGRATION_TABLE_NAME;

        if (!$migration_table_name) {
            throw new lcConfigException('Invalid database migration table: \'' . $migration_table_name . '\'');
        }

        //$migrations_target_class_name = $cfg['db.migrations.migrations_target_class_name'];

        $this->migration_table_name = $migration_table_name;
    }

    public function getProjectMigrations()
    {
        $prcfg = $this->configuration->getProjectConfiguration();
        $filename = $prcfg->getConfigDir() . DS . 'migrations.php';

        if (!file_exists($filename)) {
            return null;
        }

        require_once($filename);

        if (!class_exists('ProjectMigrations')) {
            return null;
        }


    }

    public function getPluginMigrations($plugin_name)
    {

    }

    public function installSchema(lcBaseMigrationsTarget $target)
    {
        // validate it
        $this->prepareWithTarget($target);

        // if version > 0 - then it is already installed
        if ($this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Schema already installed', self::ERR_SCHEMA_ALREADY_INSTALLED);
        }

        $this->info('Installing schema (' . $target . ')');

        // execute before
        $target->beforeExecute();

        // call the migration method
        $target->executeSchemaInstall();

        // execute after
        $target->afterExecute();

        $new_version = 1;

        // update the schema version to 1 - which means INSTALLED
        $this->updateSchemaVersionToMigrationTable($target, $new_version);

        $this->current_migrations_version = $new_version;

        return true;
    }

    public function removeSchema(lcBaseMigrationsTarget $target)
    {
        // validate it
        $this->prepareWithTarget($target);

        // if version > 0 - then it is already installed
        if (!$this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Schema is not installed', self::ERR_SCHEMA_NOT_INSTALLED);
        }

        $this->info('Removing schema (' . $target . ')');

        // execute before
        $target->beforeExecute();

        // call the migration method
        $target->executeSchemaRemove();

        // execute after
        $target->afterExecute();

        // remove the schema version locally
        $this->removeSchemaFromMigrationTable($target);

        $this->current_migrations_version = 0;

        $this->info('Schema (' . $target . ') removed');

        return true;
    }

    public function installData(lcBaseMigrationsTarget $target)
    {
        // validate it
        $this->prepareWithTarget($target);

        $this->info('Installing schema fixtures (' . $target . ')');

        // execute before
        $target->beforeExecute();

        assert(false);

        // execute after
        $target->afterExecute();

        return true;
    }

    public function removeData(lcBaseMigrationsTarget $target)
    {
        // validate it
        $this->prepareWithTarget($target);

        $this->info('Removing schema fixtures (' . $target . ')');

        // execute before
        $target->beforeExecute();

        assert(false);

        // execute after
        $target->afterExecute();

        return true;
    }

    public function upgradeSchema(lcBaseMigrationsTarget $target, $to = null)
    {
        // validate it
        $this->prepareWithTarget($target);

        $to = isset($to) ? (int)$to : (int)$target->getMigrationsVersion();
        assert($to);

        // check if schema is installed
        if (!$this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Schema is not installed', self::ERR_SCHEMA_NOT_INSTALLED);
        }

        if (!$to || $to < 2) {
            throw new lcInvalidArgumentException('Schema version is invalid');
        }

        if ($to > $target->getMigrationsVersion()) {
            throw new lcInvalidArgumentException('Schema version is higher than the highest possible one (' . $target->getMigrationsVersion());
        }

        // check the version
        if ($to == $this->current_migrations_version) {
            // version is the same nothing to upgrade
            return true;
        } elseif ($to < $this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Invalid version to upgrade to. The current version is higher: ' . $this->current_migrations_version);
        }

        $this->info('Upgrading schema (' . $target . ') from version: ' . $this->current_migrations_version . ' to version: ' . $to);

        $reached_version = $this->current_migrations_version;

        // execute before
        $target->beforeExecute();

        // for each found version call the schema migration method
        // between each successful iteration - store the version back to the
        // schema table
        for ($i = $this->current_migrations_version; $i < $to; $i++) {
            $_from = $i;
            $_to = $i + 1;
            $this->info('Running (' . $_from . ' -> ' . $_to . ')...');

            try {
                // call the migration method
                $target->executeMigrationUpgrade($this->current_migrations_version, $_to);

                $reached_version++;
                assert($reached_version > 0);

                // update the stored version
                $this->updateSchemaVersionToMigrationTable($target, $reached_version);

                $this->current_migrations_version = $reached_version;
            } catch (Exception $e) {
                throw new lcDatabaseSchemaException('Schema upgrade error (' . $_from . ' -> ' . $_to . '): ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        assert($reached_version == $to);

        // execute after
        $target->afterExecute();

        $this->info('Schema upgrade complete');

        return true;
    }

    public function downgradeSchema(lcBaseMigrationsTarget $target, $to = null)
    {
        // validate it
        $this->prepareWithTarget($target);

        $to = isset($to) ? (int)$to : (int)$target->getMigrationsVersion() - 1;
        assert($to);

        // check if schema is installed
        if (!$this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Schema is not installed', self::ERR_SCHEMA_NOT_INSTALLED);
        }

        if (!$to || $to < 1) {
            throw new lcInvalidArgumentException('Schema version is invalid');
        }

        if ($to >= $target->getMigrationsVersion()) {
            throw new lcInvalidArgumentException('Schema version is higher than the highest possible one (' . ($target->getMigrationsVersion() - 1) . ')');
        }

        // check the version
        if ($to == $this->current_migrations_version) {
            // version is the same nothing to upgrade
            return true;
        } elseif ($to > $this->current_migrations_version) {
            throw new lcDatabaseSchemaException('Invalid version to downgrade to. The current version is lower: ' . $this->current_migrations_version);
        }

        $this->info('Downgrading schema (' . $target . ') to version: ' . $to);

        $reached_version = $this->current_migrations_version;

        // execute before
        $target->beforeExecute();

        // for each found version call the schema migration method
        // between each successful iteration - store the version back to the
        // schema table
        for ($i = $this->current_migrations_version; $i > $to; $i--) {
            $_from = $i;
            $_to = $i - 1;
            $this->info('Running (' . $_from . ' -> ' . $_to . ')...');

            try {
                // call the migration method
                $target->executeMigrationDowngrade($this->current_migrations_version, $_to);

                $reached_version--;
                assert($reached_version > 0);

                // update the stored version
                $this->updateSchemaVersionToMigrationTable($target, $reached_version);

                $this->current_migrations_version = $reached_version;
            } catch (Exception $e) {
                throw new lcDatabaseSchemaException('Schema downgrade error (' . $_from . ' -> ' . $_to . '): ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        assert($reached_version == $to);

        // execute after
        $target->afterExecute();

        $this->info('Schema downgrade complete');

        return true;
    }

    private function prepareWithTarget(lcBaseMigrationsTarget $target)
    {
        // validate it
        $this->validateMigrationsTarget($target);

        // create migration table if missing
        $this->createMigrationTable($target->getDatabase());

        // fetch and cache info for target
        $this->initInternal($target);
    }

    private function validateMigrationsTarget(lcBaseMigrationsTarget $target)
    {
        $valid = $target && $target->getDatabase() && $target->getSchemaIdentifier() && $target->getMigrationsVersion() && is_numeric($target->getMigrationsVersion());

        if (!$valid) {
            throw new lcSystemException('Schema migration target ' . ($target ? get_class($target) : null) . ' is invalid: ' . $target);
        }
    }

    private function updateSchemaVersionToMigrationTable(lcBaseMigrationsTarget $target, $version)
    {
        $version = (int)$version;

        assert($version);
        assert(isset($target));

        $conn = $target->getDatabase()->getConnection();

        $sql = 'REPLACE INTO ' . $conn->quoteTableName($this->migration_table_name) . ' (schema_identifier, schema_version, last_updated) VALUES(' . $conn->quote($target->getSchemaIdentifier()) . ', ' . $version . ', CURRENT_TIMESTAMP)';
        $conn->exec($sql);

        if (DO_DEBUG) {
            $this->debug('Schema table updated (Changed version: ' . $this->migration_table_name . ': ' . $target->getSchemaIdentifier() . ' -> ' . $version . ')');
        }
    }

    private function removeSchemaFromMigrationTable(lcBaseMigrationsTarget $target)
    {
        assert(isset($target));

        $conn = $target->getDatabase()->getConnection();

        $sql = 'DELETE FROM ' . $conn->quoteTableName($this->migration_table_name) . ' WHERE schema_identifier = ' . $conn->quote($target->getSchemaIdentifier());
        $conn->exec($sql);

        if (DO_DEBUG) {
            $this->debug('Schema table updated (Removed schema: ' . $this->migration_table_name . ': ' . $target->getSchemaIdentifier() . ')');
        }
    }

    private function initInternal(lcBaseMigrationsTarget $target)
    {
        $this->current_target = $target;
        $this->conn = $target->getDatabase()->getConnection();

        // fetch current schema migrations version
        $query = 'SELECT schema_version FROM ' . $this->conn->quoteTableName($this->migration_table_name) . ' WHERE schema_identifier = ' . $this->conn->quote($target->getSchemaIdentifier()) . ' LIMIT 1';
        $res = $this->conn->query($query);

        $current_schema_version = 0;

        if ($res->rowCount()) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            $current_schema_version = (int)$row['schema_version'];
        }

        $this->current_migrations_version = $current_schema_version;

        $this->info('Obtained current schema version: ' . $current_schema_version);

        // check if for some reason the installed version is higher than the
        // target's one
        // that should never happen!
        if ($this->current_migrations_version > $target->getMigrationsVersion()) {
            throw new lcDatabaseSchemaException('The schema object\'s version identifier is invalid - it is lower than the currently active installed schema version!' . ' (currently installed: ' . $this->current_migrations_version . ', Schema target version: ' . $target->getMigrationsVersion() . ')');
        }
    }

    private function createMigrationTable(lcDatabase $database)
    {
        if (!$database) {
            throw new lcInvalidArgumentException('Invalid database');
        }

        $conn = $database->getConnection();

        $mtable = $this->migration_table_name;

        assert(!is_null($mtable));

        // check if it exists first
        $query = 'SHOW TABLES LIKE ' . $conn->quote($mtable);
        $ret = $conn->query($query)->fetch(PDO::FETCH_ASSOC);

        if (!$ret) {
            $this->info('Creating schema migrations table (' . $mtable . ')');

            // not existing - create it
            $sql = 'CREATE TABLE ' . $conn->quoteTableName($mtable) . ' (`schema_identifier` varchar(50) NOT NULL DEFAULT \'\',`schema_version` int(11) NOT NULL DEFAULT \'1\',`last_updated` DATETIME NOT NULL,PRIMARY KEY (`schema_identifier`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
            $conn->exec($sql);
        }
    }

}
