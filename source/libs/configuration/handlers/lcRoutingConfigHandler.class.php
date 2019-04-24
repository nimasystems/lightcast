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

class lcRoutingConfigHandler extends lcEnvConfigHandler
{
    public function getDefaultValues()
    {
        return ['routing' => [
            'send_http_errors' => true,
            'default_module' => 'home',
            'default_action' => 'index',
        ]];
    }

    protected function postReadConfigData($environment, array $data)
    {
        // there is a problem with defaults merging here (order is broken)
        // so we add the default routes ONLY if there are no other routes
        // detected

        if (!isset($data['routing']['routes'])) {
            $data['routing']['routes'] = $this->getDefaultRoutes();
        }

        return $data;
    }

    public function getDefaultRoutes()
    {
        $routes = [
            'view_item' => [
                'url' => '/:module/view/:id',
                'params' => ['action' => 'view'],
            ],
            'default' => ['url' => '/:module/:action'],
            'module_default' => [
                'url' => '/:module',
                'params' => ['action' => 'index'],
            ],
            'homepage' => [
                'url' => '/',
                'params' => [
                    'module' => 'home',
                    'action' => 'index',
                ],
            ],
        ];

        return $routes;
    }
}