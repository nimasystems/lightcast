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
class lcLoadersConfigHandler extends lcEnvConfigHandler
{
    // TODO: think of a better place for this method
    /**
     * @return string[]
     */
    public static function getLoadingOrderConfig(): array
    {
        return [
            'logger',
            'database_manager',
            'i18n',
            'cache',
            'mailer',
            'request',
            'response',
            'router',
            'storage',
            'user',
            'data_storage',
            'controller',
        ];
    }

    // TODO: think of a better place for this method

    /**
     * @return array[]
     */
    public static function getLoaderRequirements(): array
    {
        return [
            'logger' => [
                'required' => false,
                'inheritance' => 'lcLogger',
            ],
            'cache' => [
                'required' => false,
                'inheritance' => 'lcCacheStore',
                'config_enabled_key' => 'cache.enabled',
            ],
            'request' => [
                'required' => true,
                'inheritance' => 'lcRequest',
            ],
            'response' => [
                'required' => true,
                'inheritance' => 'lcResponse',
            ],
            'database_manager' => [
                'required' => false,
                'inheritance' => 'iDatabaseManager',
                'config_enabled_key' => 'db.use_database',
            ],
            'controller' => [
                'required' => true,
                'inheritance' => 'iFrontController',
            ],
            'router' => [
                'required' => true,
                'inheritance' => 'lcRouting',
            ],
            'storage' => [
                'required' => false,
                'inheritance' => 'lcStorage',
                'config_enabled_key' => 'storage.enabled',
            ],
            'i18n' => [
                'required' => false,
                'inheritance' => 'lcI18n',
                'config_enabled_key' => 'i18n.enabled',
            ],
            'user' => [
                'required' => false,
                'inheritance' => 'lcUser',
                'config_enabled_key' => 'user.enabled',
            ],
            'mailer' => [
                'required' => true,
                'inheritance' => 'lcMailer',
            ],
            'data_storage' => [
                'required' => false,
                'inheritance' => 'lcDataStorage',
                'config_enabled_key' => 'data_storage.enabled',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getDefaultValues(): array
    {
        return ['loaders' => [
            'logger' => 'lcFileLoggerNG',
            'cache' => '',
            'request' => 'lcWebRequest',
            'response' => 'lcWebResponse',
            'database_manager' => 'lcDatabaseManager',
            'controller' => 'lcFrontWebController',
            'router' => 'lcPatternRouting',
            'storage' => '',
            'i18n' => '',
            'user' => '',
            'mailer' => 'lcPHPMailer',
            'data_storage' => '',
        ],];
    }
}
