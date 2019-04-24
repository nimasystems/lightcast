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

class lcDatabaseMigrationsHelper extends lcSysObj
{
    const DEFAULT_DB_SCHEMA_TABLE = 'core_db_schema';
    const DEFAULT_DB_MIGRATIONS_HISTORY_TABLE = 'core_db_migration_history';

    protected $schema_table_name = self::DEFAULT_DB_SCHEMA_TABLE;
    protected $schema_migrations_history_table_name = self::DEFAULT_DB_MIGRATIONS_HISTORY_TABLE;

    /**
     * @var lcPropelConnection
     */
    protected $dbc;

    public function setDatabaseConnection(lcPropelConnection $dbc)
    {
        $this->dbc = $dbc;
    }

    public function uninstallSchema(iDatabaseMigrationSchema $target)
    {
        // check and create migration table if missing
        $this->createSchemaTables();

        // validate it
        $this->prepareTarget($target);

        if (!$this->isSchemaInstalled($target)) {
            return true;
        }

        $schema_info = $this->getSchemaInfoFromMigrationTable($target->getSchemaIdentifier());
        $schema_id = (int)$schema_info['schema_id'];
        $current_version = (int)$schema_info['schema_version'];

        $this->info('Uninstalling schema (' . $target->getSchemaIdentifier() . '): currently installed version: ' . $current_version);

        /// Execute schema uninstall

        $this->dbc->beginTransaction();

        try {
            /// Execute data uninstall
            $this->uninstallData($target);

            // execute before
            $target->beforeExecute($this->dbc, iDatabaseMigrationSchema::ACTION_SCHEMA_UNINSTALL);

            // call the migration method
            $target->schemaUninstall($this->dbc);

            // execute after
            $target->afterExecute($this->dbc, iDatabaseMigrationSchema::ACTION_SCHEMA_UNINSTALL);

            // remove the schema record
            $this->removeSchemaFromMigrationTable($schema_id, $target->getSchemaIdentifier());

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function createSchemaTables()
    {
        // schema
        $schemac = $this->createTableFromDDL(
            $this->schema_table_name,
            'CREATE TABLE `core_db_schema` (
              `schema_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `schema_identifier` varchar(50) NOT NULL,
              `schema_version` int(11) NOT NULL,
              `data_installed` enum(\'yes\',\'no\') NOT NULL DEFAULT \'no\',
              `last_updated` datetime NOT NULL,
              PRIMARY KEY (`schema_id`),
              UNIQUE KEY `schema_identifier` (`schema_identifier`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
        );

        // history
        $schemah = $this->createTableFromDDL(
            $this->schema_migrations_history_table_name,
            'CREATE TABLE `core_db_migration_history` (
              `migration_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `schema_id` int(11) unsigned NOT NULL,
              `created_on` datetime NOT NULL,
              `update_type` enum(\'schema_in\',\'schema_out\',\'data_in\',\'data_out\',\'schema_up\',\'schema_down\') NOT NULL DEFAULT \'schema_up\',
              `from_schema_version` int(11) DEFAULT NULL,
              `to_schema_version` int(11) DEFAULT NULL,
              PRIMARY KEY (`migration_id`),
              KEY `schema_id` (`schema_id`),
              CONSTRAINT `core_db_migration_history_ibfk_1` FOREIGN KEY (`schema_id`) REFERENCES `core_db_schema` (`schema_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
        );

        return ($schemac && $schemah);
    }

    protected function createTableFromDDL($table_name, $ddl)
    {
        // check if it exists first
        $query = 'SHOW TABLES LIKE ' . $this->dbc->quote($table_name);
        $ret = $this->dbc->query($query)->fetch(PDO::FETCH_ASSOC);

        if (!$ret) {
            $this->info('Creating schema migrations table (' . $table_name . ')');
            return $this->dbc->exec($ddl);
        }

        return true;
    }

    /**
     * @param iDatabaseMigrationSchema $target
     * @return lcDatabaseMigrationsHelper
     * @throws lcSystemException
     */
    protected function prepareTarget(iDatabaseMigrationSchema $target)
    {
        // validate it
        $this->validateMigrationsTarget($target);

        return $this;
    }

    /**
     * @param iDatabaseMigrationSchema $target
     * @return lcDatabaseMigrationsHelper
     * @throws lcSystemException
     */
    protected function validateMigrationsTarget(iDatabaseMigrationSchema $target)
    {
        $valid = ($target->getSchemaIdentifier() && (int)$target->getSchemaVersion());

        if (!$valid) {
            throw new lcSystemException('Database schema is not valid');
        }

        return $this;
    }

    public function isSchemaInstalled(iDatabaseMigrationSchema $target)
    {
        return ((bool)$this->getSchemaInstalledVersion($target));
    }

    public function getSchemaInstalledVersion(iDatabaseMigrationSchema $target)
    {
        return $this->getSchemaVersionFromMigrationTable($target->getSchemaIdentifier());
    }

    protected function getSchemaVersionFromMigrationTable($schema_identifier)
    {
        $info = $this->getSchemaInfoFromMigrationTable($schema_identifier);
        return ($info && (int)$info['schema_version'] ? (int)$info['schema_version'] : null);
    }

    public function getSchemaInfoFromMigrationTable($schema_identifier)
    {
        $sql = 'SELECT * FROM ' . $this->dbc->quoteTableName($this->schema_table_name) .
            ' WHERE schema_identifier = ' . $this->dbc->quote($schema_identifier);
        return $this->dbc->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function uninstallData(iDatabaseMigrationSchema $target)
    {
        return $this->installUninstallData($target, false);
    }

    protected function installUninstallData(iDatabaseMigrationSchema $target, $install = true)
    {
        // check and create migration table if missing
        $this->createSchemaTables();

        // validate it
        $this->prepareTarget($target);

        // install the schema if not done yet
        if (!$this->isSchemaInstalled($target)) {
            $this->installSchema($target);
        }

        $schema_info = $this->getSchemaInfoFromMigrationTable($target->getSchemaIdentifier());
        $schema_id = (int)$schema_info['schema_id'];
        $data_installed = ($schema_info['data_installed'] == 'yes');

        if ($install && $data_installed) {
            return true;
        } else if (!$install && !$data_installed) {
            throw new lcNotAvailableException('Data has not been installed');
        }

        $this->info(($install ? 'Installing' : 'Uninstalling') . ' schema data (' . $target->getSchemaIdentifier() . ')');

        $this->dbc->beginTransaction();

        try {
            $action = ($install ? iDatabaseMigrationSchema::ACTION_DATA_INSTALL :
                iDatabaseMigrationSchema::ACTION_DATA_UNINSTALL);

            // execute before
            $target->beforeExecute($this->dbc, $action);

            // execute the migration method
            if ($install) {
                $target->dataInstall($this->dbc);
            } else {
                $target->dataUninstall($this->dbc);
            }

            // execute after
            $target->afterExecute($this->dbc, $action);

            $this->updateSchemaDataInstalledToMigrationTable($schema_id, $target->getSchemaIdentifier(), $install);

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }

        return true;
    }

    public function installSchema(iDatabaseMigrationSchema $target)
    {
        // check and create migration table if missing
        $this->createSchemaTables();

        // validate it
        $this->prepareTarget($target);

        if ($this->isSchemaInstalled($target)) {
            return true;
        }

        $new_version = $target->getSchemaVersion();
        $new_version = $new_version ? $new_version : 1;

        $this->info('Installing schema (' . $target->getSchemaIdentifier() . '): initial version: ' . $new_version);

        $this->dbc->beginTransaction();

        try {
            /// Execute schema install

            // add the schema record
            $this->addSchemaToMigrationTable($target->getSchemaIdentifier(), $new_version);

            // execute before
            $target->beforeExecute($this->dbc, iDatabaseMigrationSchema::ACTION_SCHEMA_INSTALL);

            // call the migration method
            $target->schemaInstall($this->dbc);

            // execute after
            $target->afterExecute($this->dbc, iDatabaseMigrationSchema::ACTION_SCHEMA_INSTALL);

            /// Execute data install
            $this->installData($target);

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }

        return true;
    }

    protected function addSchemaToMigrationTable($schema_identifier, $initial_version = null)
    {
        $initial_version = ($initial_version ? (int)$initial_version : 1);

        $this->dbc->beginTransaction();

        $schema_id = null;

        try {
            $sql = 'INSERT INTO ' . $this->dbc->quoteTableName($this->schema_table_name) .
                ' (schema_identifier, schema_version, last_updated) ' .
                ' VALUES( ' .
                $this->dbc->quote($schema_identifier) . ', ' .
                $initial_version . ', ' .
                'Now()' .
                ')';
            $this->dbc->exec($sql);

            $schema_id = $this->dbc->lastInsertId();

            // history
            $this->addSchemaUpdateHistory(
                $schema_id,
                'schema_in',
                null,
                $initial_version
            );

            if (DO_DEBUG) {
                $this->debug('Schema table updated (Installed schema: ' . $schema_identifier . ')');
            }

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }

        return $schema_id;
    }

    public function installData(iDatabaseMigrationSchema $target)
    {
        return $this->installUninstallData($target, true);
    }

    protected function updateSchemaDataInstalledToMigrationTable($schema_id, $schema_identifier, $data_installed)
    {
        $schema_id = (int)$schema_id;
        $data_installed = (bool)$data_installed;

        $this->dbc->beginTransaction();

        try {
            // add or updte
            $sql = 'UPDATE ' . $this->dbc->quoteTableName($this->schema_table_name) . ' SET ' . '
            data_installed = \'' . ($data_installed ? 'yes' : 'no') . '\',
            last_updated = Now()
            WHERE 
            schema_id = ' . $schema_id;
            $this->dbc->exec($sql);

            // history
            $this->addSchemaUpdateHistory(
                $schema_id,
                ($data_installed ? 'data_in' : 'data_out')
            );

            if (DO_DEBUG) {
                $this->debug('Schema table updated (Data ' . ($data_installed ? 'installed' : 'uninstalled') . ': ' . $schema_identifier . ')');
            }

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }
    }

    protected function addSchemaUpdateHistory($schema_id, $update_type, $from_ver = null, $to_ver = null)
    {
        $sql = 'INSERT INTO ' . $this->dbc->quoteTableName($this->schema_migrations_history_table_name) .
            ' (schema_id, created_on, update_type, from_schema_version, to_schema_version) ' .
            ' VALUES(' .
            (int)$schema_id . ', ' .
            'Now(), ' .
            $this->dbc->quote($update_type) . ', ' .
            ((int)$from_ver ? (int)$from_ver : 'NULL') . ', ' .
            ((int)$to_ver ? (int)$to_ver : 'NULL') . ' ' .
            ')';
        $this->dbc->exec($sql);
    }

    protected function removeSchemaFromMigrationTable($schema_id, $schema_identifier)
    {
        $schema_id = (int)$schema_id;

        $this->dbc->beginTransaction();

        try {
            $sql = 'DELETE FROM ' . $this->dbc->quoteTableName($this->schema_table_name) .
                ' WHERE schema_id = ' . $schema_id;
            $this->dbc->exec($sql);

            if (DO_DEBUG) {
                $this->debug('Schema table updated (Removed schema: ' . $schema_identifier . ')');
            }

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }
    }

    public function migrateSchema(iDatabaseMigrationSchema $target, $to_custom_schema_version = null)
    {
        $to_custom_schema_version = isset($to_custom_schema_version) ? (int)$to_custom_schema_version : null;

        // check and create migration table if missing
        $this->createSchemaTables();

        // validate it
        $this->prepareTarget($target);

        // install the schema if not done yet
        if (!$this->isSchemaInstalled($target)) {
            $this->installSchema($target);
        }

        $schema_info = $this->getSchemaInfoFromMigrationTable($target->getSchemaIdentifier());
        $schema_id = (int)$schema_info['schema_id'];

        $current_schema_version = (int)$schema_info['schema_version'];
        $target_schema_version = ($to_custom_schema_version ? $to_custom_schema_version : $target->getSchemaVersion());

        if ($current_schema_version == $target_schema_version) {
            return true;
        }

        $is_migrate_up = $target_schema_version > $current_schema_version;

        if ($is_migrate_up) {
            $from = $current_schema_version;
            $to = $target_schema_version;
        } else {
            $from = $current_schema_version;
            $to = $target_schema_version;
        }

        $schema_identifier = $target->getSchemaIdentifier();

        if ($to == $from) {
            return true;
        }

        $this->info('Migrating schema ' . ($is_migrate_up ? 'UP' : 'DOWN') .
            ' (' . $schema_identifier . ') from version: ' . $from . ' to version: ' . $to);

        $action = ($is_migrate_up ? iDatabaseMigrationSchema::ACTION_MIGRATE_UP :
            iDatabaseMigrationSchema::ACTION_MIGRATE_DOWN);

        // execute before
        $target->beforeExecute($this->dbc, $action);

        // for each found version call the schema migration method
        // between each successful iteration - store the version back to the
        // schema table
        if ($is_migrate_up) {
            for ($i = $from; $i <= $to - 1; $i++) {
                $_from = $i;
                $_to = $i + 1;

                $this->info('Running schema migrate up (' . $_from . ' -> ' . $_to . ')...');

                $this->dbc->beginTransaction();

                try {
                    // call the migration method
                    $migrated = $target->migrateUp($this->dbc, $_from, $_to);

                    if ($migrated) {
                        // update the stored version
                        $this->updateSchemaVersionToMigrationTable($schema_id, $schema_identifier, $_from, $_to);
                    }

                    $this->dbc->commit();
                } catch (Exception $e) {
                    $this->dbc->rollback();
                    throw new lcDatabaseSchemaException('Schema upgrade error (' . $_from . ' -> ' . $_to . '): ' . $e->getMessage(), $e->getCode(), $e);
                }
            }

            // update the stored version
            $this->updateSchemaVersionToMigrationTable($schema_id, $schema_identifier, $from, $to, false);

        } else {
            // we need to uninstall only what we installed!
            $installed_schema_versions = $this->getInstalledSchemaVersionsFromMigrationTable($target);

            if ($installed_schema_versions) {
                for ($i = $from - 1; $i >= $to - 1; $i--) {
                    $_from = $i;
                    $_to = $i - 1;

                    $this->info('Running schema migrate down (' . $_from . ' -> ' . $_to . ')...');

                    $this->dbc->beginTransaction();

                    try {
                        // call the migration method
                        if (in_array($_from, $installed_schema_versions)) {

                            $migrated = $target->migrateDown($this->dbc, $_from, $_to);

                            if ($migrated) {
                                // update the stored version
                                $this->updateSchemaVersionToMigrationTable($schema_id, $schema_identifier, $_from, $_to);
                            }
                        }

                        $this->dbc->commit();
                    } catch (Exception $e) {
                        $this->dbc->rollback();
                        throw new lcDatabaseSchemaException('Schema upgrade error (' . $_from . ' -> ' . $_to . '): ' . $e->getMessage(), $e->getCode(), $e);
                    }
                }
            }

            // update the stored version
            $this->updateSchemaVersionToMigrationTable($schema_id, $schema_identifier, $from, $to, false);
        }

        // execute after
        $target->afterExecute($this->dbc, $action);

        $this->info('Schema migrate ' . ($is_migrate_up ? 'UP' : 'DOWN') . ' complete (from: ' . $from . ', to: ' . $to);

        return true;
    }

    protected function updateSchemaVersionToMigrationTable($schema_id, $schema_identifier, $from_version, $to_version, $add_history = true)
    {
        $schema_id = (int)$schema_id;
        $from_version = (int)$from_version;
        $to_version = (int)$to_version;

        $this->dbc->beginTransaction();

        try {
            // add or updte
            $sql = 'UPDATE ' . $this->dbc->quoteTableName($this->schema_table_name) . ' SET ' . '
            schema_version = ' . $to_version . ',
            last_updated = Now()
            WHERE 
            schema_id = ' . $schema_id;
            $this->dbc->exec($sql);

            if ($add_history) {
                // history
                $this->addSchemaUpdateHistory(
                    $schema_id,
                    ($to_version >= $from_version ? 'schema_up' : 'schema_down'),
                    $from_version,
                    $to_version
                );
            }

            if (DO_DEBUG) {
                $this->debug('Schema table updated (Changed version: ' . $schema_identifier . ', ' . $from_version . ' -> ' . $to_version . ')');
            }

            $this->dbc->commit();
        } catch (Exception $e) {
            $this->dbc->rollback();
            throw $e;
        }
    }

    public function getInstalledSchemaVersionsFromMigrationTable($schema_identifier)
    {
        $sql = 'SELECT to_schema_version FROM ' . $this->dbc->quoteTableName($this->schema_migrations_history_table_name) .
            ' th INNER JOIN ' . $this->dbc->quoteTableName($this->schema_table_name) . ' ts ON ts.schema_id = th.schema_id ' .
            ' WHERE ts.schema_identifier = ' . $this->dbc->quote($schema_identifier) . ' AND th.update_type = \'schema_up\'';
        return $this->dbc->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }
}
