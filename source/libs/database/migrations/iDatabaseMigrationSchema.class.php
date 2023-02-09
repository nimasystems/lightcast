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

interface iDatabaseMigrationSchema
{
    public const ACTION_MIGRATE_UP = 'migrate_up';
    public const ACTION_MIGRATE_DOWN = 'migrate_down';
    public const ACTION_SCHEMA_INSTALL = 'schema_install';
    public const ACTION_SCHEMA_UNINSTALL = 'schema_uninstall';
    public const ACTION_DATA_INSTALL = 'data_install';
    public const ACTION_DATA_UNINSTALL = 'data_uninstall';

    /**
     * @return string
     */
    public function getSchemaIdentifier(): string;

    /**
     * @return int
     */
    public function getSchemaVersion(): int;

    /**
     * @param lcPropelConnection $db
     * @param int $from_version
     * @param int $to_version
     * @return bool
     */
    public function migrateUp(lcPropelConnection $db, int $from_version, int $to_version): bool;

    /**
     * @param lcPropelConnection $db
     * @param int $from_version
     * @param int $to_version
     * @return bool
     */
    public function migrateDown(lcPropelConnection $db, int $from_version, int $to_version): bool;

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function schemaInstall(lcPropelConnection $db): bool;

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function schemaUninstall(lcPropelConnection $db): bool;

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function dataInstall(lcPropelConnection $db): bool;

    /**
     * @param lcPropelConnection $db
     * @return bool
     */
    public function dataUninstall(lcPropelConnection $db): bool;

    /**
     * @param lcPropelConnection $db
     * @param string $action
     */
    public function beforeExecute(lcPropelConnection $db, string $action);

    /**
     * @param lcPropelConnection $db
     * @param string $action
     */
    public function afterExecute(lcPropelConnection $db, string $action);
}
