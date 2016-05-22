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

abstract class lcPackageDatabaseMigrationSchema extends lcSysObj implements iDatabaseMigrationSchema
{
    protected $log_channel = 'db_migrations';

    /**
     * @var lcPluginConfiguration
     */
    private $plugin_configuration;

    public function initialize()
    {
        parent::initialize();

        //$this->log_channel = 'db_migrations_' . $this->getSchemaIdentifier();
    }

    /**
     * @param lcPluginConfiguration $plugin_configuration
     * @return lcPackageDatabaseMigrationSchema
     */
    public function setPluginConfiguration($plugin_configuration)
    {
        $this->plugin_configuration = $plugin_configuration;
        return $this;
    }

    /**
     * @return string
     */
    public function getSchemaIdentifier()
    {
        return $this->plugin_configuration->getName() . '_' . $this->plugin_configuration->getIdentifier();
    }

    /**
     * @param lcPropelConnection $db
     * @param int $from_version
     * @param int $to_version
     * @return bool
     */
    public function migrateUp(lcPropelConnection $db, $from_version, $to_version)
    {
        $method_name = 'migrateUp_' . $to_version;

        if (method_exists($this, $method_name)) {
            return $this->$method_name($db);
        }

        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @param int $from_version
     * @param int $to_version
     * @return bool
     */
    public function migrateDown(lcPropelConnection $db, $from_version, $to_version)
    {
        $method_name = 'migrateDown_' . $to_version;

        if (method_exists($this, $method_name)) {
            return $this->$method_name($db);
        }

        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function schemaInstall(lcPropelConnection $db)
    {
        // to be inherited by children
        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function schemaUninstall(lcPropelConnection $db)
    {
        // to be inherited by children
        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function dataInstall(lcPropelConnection $db)
    {
        // to be inherited by children
        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function dataUninstall(lcPropelConnection $db)
    {
        // to be inherited by children
        return false;
    }

    /**
     * @param lcPropelConnection $db
     * @param string $action
     */
    public function beforeExecute(lcPropelConnection $db, $action)
    {
        $this->info('beforeExecute: ' . $action);

        // to be inherited by children
    }

    /**
     * @param lcPropelConnection $db
     * @param string $action
     */
    public function afterExecute(lcPropelConnection $db, $action)
    {
        $this->info('afterExecute: ' . $action);

        // to be inherited by children
    }

    public function __toString()
    {
        return (string)$this->getSchemaIdentifier();
    }
}
