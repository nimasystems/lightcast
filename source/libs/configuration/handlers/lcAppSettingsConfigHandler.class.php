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

class lcAppSettingsConfigHandler extends lcEnvConfigHandler
{
    public function getDefaultValues()
    {
        return [
            'settings' => [
                'profiler' => false,
                'base_url' => null,
                'server' => null,
                'charset' => 'utf-8',
                'admin_email' => null,
                'enabled_modules' => [],
                'disabled_modules' => []
            ],
            'exceptions' => [
                'module' => null,
                'action' => null
            ],
            'logger' => [
                'enabled' => true,
                'email_to' => '',
                'email_threshold' => 'crit',
                'log_files' => []
            ],
            'controller' => [
                'max_forwards' => 10,
                'filters' => []
            ],
            'storage' => [
                'enabled' => true,
                'timeout' => 60
            ],
            'data_storage' => ['enabled' => true],
            'user' => [
                'enabled' => true,
                'timeout' => 60
            ],
            'cache' => ['enabled' => true,],
            'i18n' => [
                'enabled' => true,
                'locale' => 'en_US',
                'translate_view' => true,
                'do_append' => true,
                'do_not_append_to' => 'nolang',
                'save_cookie' => true,
                'append_to' => ['a' => 'href'],
                'media_localization' => [
                    'enabled' => false,
                    'match_string' => 'localized',
                    'uri' => '/localized',
                    'search_tags' => [
                        'img' => 'src',
                        'script' => 'src'
                    ]
                ],
                'skip_append_for' => [
                    '^\/img',
                    '^\/files',
                    '^\/images',
                    '^\/vfs'
                ],
                'lang_code_match' => '^\/(([a-z]{2}(_[A-Z]{2})?\/)|([a-z]{2}(_[A-Z]{2})?)$)\/*',
                'autodetect' => true
            ],
            'mailer' => [
                'charset' => 'UTF-8',
                'content_type' => 'text/html',
                'encoding' => '8bit',
                'attachment_encoding' => 'base64',
                'testing_mode' => false,
                'use' => 'mail',
                'debug' => false,
                'smtp_host' => 'localhost',
                'smtp_port' => 25,
                'security' => null,
                'smtp_user' => null,
                'smtp_pass' => null
            ]
        ];
    }

}
