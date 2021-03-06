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

class lcWebServiceConfigHandler extends lcEnvConfigHandler
{
    public function getDefaultValues()
    {
        return [
            'routing' => [
                'module_prefix' => 'service',
                'action_prefix' => 'method',
            ],
            'settings' => ['admin_email' => '',],
            'controller' => [
                'max_forwards' => 10,
                'filters' => [],
            ],
            'view' => [
                'filters' => [],
                'content_type' => 'application/json',
                'charset' => 'utf-8',
            ],
            'logger' => [
                'enabled' => true,
                'email_to' => '',
                'email_threshold' => 'crit',
                'log_files' => [],
            ],
            'mailer' => [
                'charset' => 'UTF-8',
                'content_type' => 'text/html',
                'encoding' => '8bit',
                'testing_mode' => false,
                'use' => 'smtp',
                'debug' => false,
                'smtp_host' => '',
                'smtp_port' => 25,
                'security' => [
                    'smtp_user' => '',
                    'smtp_pass' => '',
                ],
            ],
            'loaders' => [
                'logger' => 'lcFileLoggerNG',
                'request' => 'lcWebRequest',
                'response' => 'lcWebResponse',
                'database_manager' => 'lcDatabaseManager',
                'controller' => 'lcFrontWebServiceController',
                'router' => 'lcPHPRouting',
                'cache' => '',
                'storage' => 'lcInternalStorage',
                'i18n' => '',
                'user' => '',
                'mailer' => 'lcPHPMailer',
                'data_storage' => '',
            ],
        ];
    }
}
