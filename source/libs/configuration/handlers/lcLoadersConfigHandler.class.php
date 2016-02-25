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

class lcLoadersConfigHandler extends lcEnvConfigHandler
{
    // TODO: think of a better place for this method
    public static function getLoadingOrderConfig()
    {
        return array(
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
            'controller'
        );
    }

    // TODO: think of a better place for this method
    public static function getLoaderRequirements()
    {
        return array(
            'logger' => array(
                'required' => false,
                'inheritance' => 'lcLogger'
            ),
            'cache' => array(
                'required' => false,
                'inheritance' => 'lcCacheStore',
                'config_enabled_key' => 'cache.enabled'
            ),
            'request' => array(
                'required' => true,
                'inheritance' => 'lcRequest'
            ),
            'response' => array(
                'required' => true,
                'inheritance' => 'lcResponse'
            ),
            'database_manager' => array(
                'required' => false,
                'inheritance' => 'iDatabaseManager',
                'config_enabled_key' => 'db.use_database'
            ),
            'controller' => array(
                'required' => true,
                'inheritance' => 'iFrontController'
            ),
            'router' => array(
                'required' => true,
                'inheritance' => 'lcRouting'
            ),
            'storage' => array(
                'required' => false,
                'inheritance' => 'lcStorage',
                'config_enabled_key' => 'storage.enabled'
            ),
            'i18n' => array(
                'required' => false,
                'inheritance' => 'lcI18n',
                'config_enabled_key' => 'i18n.enabled'
            ),
            'user' => array(
                'required' => false,
                'inheritance' => 'lcUser',
                'config_enabled_key' => 'user.enabled'
            ),
            'mailer' => array(
                'required' => true,
                'inheritance' => 'lcMailer'
            ),
            'data_storage' => array(
                'required' => false,
                'inheritance' => 'lcDataStorage',
                'config_enabled_key' => 'data_storage.enabled'
            ),
        );
    }

    public function getDefaultValues()
    {
        return array('loaders' => array(
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
        ),);
    }
}
