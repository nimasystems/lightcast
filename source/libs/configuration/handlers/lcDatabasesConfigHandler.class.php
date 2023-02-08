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

/**
 *
 */
class lcDatabasesConfigHandler extends lcEnvConfigHandler
{
    public const DEFAULT_PRIMARY_ADAPTER_NAME = 'primary';

    /**
     * @return array[]
     */
    public function getDefaultValues(): array
    {
        return ['db' => [
            'use_database' => false,
            'use_propel' => true,
            'databases' => [
                self::DEFAULT_PRIMARY_ADAPTER_NAME => [
                    'classname' => 'lcPropelDatabase',
                    'caching' => true,
                    'persistent_connections' => true,
                    'logging' => true,
                    'datasource' => null,
                    'driver' => 'mysql',
                    'url' => 'mysql:host=localhost;dbname=',
                    'user' => null,
                    'password' => null,
                    'charset' => 'utf8',
                ],
            ],
            'migrations' => [
                'helper_class' => 'lcDatabaseMigrationsHelper',
            ],
            'propel_custom' => [
                'propel.lightcastOverrideBuildPath' => true,
                'propel.lightcastBuildPath' => 'gen/propel/models/',
                'gen_dir' => 'propel',
            ],
            'models' => [],
            'views' => [],
            'propel' => [
                'propel.database' => 'mysql',
                'propel.mysql.tableType' => 'innodb',
                'propel.emulateForeignKeyConstraints' => true,
                'propel.database.encoding' => 'UTF-8',
                'propel.php.dir' => '${propel.output.dir}',
                'propel.sql.dir' => '${propel.output.dir}/data/sql',
                'propel.graph.dir' => '${propel.output.dir}/data/graphviz',
                'propel.addGenericAccessors' => true,
                'propel.addGenericMutators' => true,
                'propel.useDateTimeClass' => true,
                'propel.addSaveMethod' => true,
                'propel.addTimeStamp' => true,
                'propel.basePrefix' => 'Base',
                'propel.saveException' => 'PropelException',
                'propel.packageObjectModel' => true,
                'propel.schema.validate' => true,
                'propel.defaultTimeStampFormat' => 'Y-m-d H:i:s',
                'propel.defaultTimeFormat' => '%X',

                /* It is important to leave the following setting like this
                 * (aka: %Y-%m-%d)
                 * Otherwise propel validators WILL fail when verified
                 * (propel uses a default %X which
                 * is using the current locale)
                 */
                'propel.defaultDateFormat' => '%F',
                'propel.builder.query.class' => 'lcPropelBaseQueryBuilder',
                'propel.builder.peer.class' => 'lcPropelBasePeerBuilder',
                'propel.builder.object.class' => 'lcPropelBaseObjectBuilder',
                'propel.builder.objectstub.class' => 'lcPropelObjectStubBuilder',
                'propel.builder.peerstub.class' => 'lcPropelPeerStubBuilder',
                'propel.builder.tablemap.class' => 'lcPropelTableMapBuilder',
                'propel.builder.addIncludes' => false,
                'propel.builder.addComments' => true,
                'propel.builder.addBehaviors' => true,
                'propel.defaultTranslator' => 'lcPropelTranslator',
            ],
        ]];
    }
}
