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

class lcProjectConfigHandler extends lcEnvConfigHandler
{
    public function getDefaultValues()
    {
        return array(
            'project' => array('project_name' => 'Lightcast project'),

            'settings' => array(
                'timezone' => 'Europe/Sofia',
                'exception_http_header' => array(
                    'enabled' => false,
                    'header' => 'HTTP/1.1 500 Internal Server Error'
                )
            ),
            'tools' => array('htmldoc' => '/usr/bin/htmldoc'),
            'plugins' => array('locations' => array('addons/plugins')),
            'exceptions' => array(
                'module' => null,
                'action' => null,
                'mail' => array(
                    'enabled' => false,
                    'recipient' => null,
                    'skip_exceptions' => array(),
                    'only_exceptions' => array()
                )
            )
        );
    }
}
