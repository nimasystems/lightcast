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
class lcWebServiceConfiguration extends lcApplicationConfiguration
{
    public const DEFAULT_APP_NAME = 'ws';

    /**
     * @return string
     */
    public function getApplicationName(): string
    {
        return self::DEFAULT_APP_NAME;
    }

    /**
     * @return int
     */
    public function getApiLevel(): int
    {
        return 1;
    }

    /**
     * @return null
     */
    public function getProjectConfigDir()
    {
        return null;
    }

    /**
     * @return array|array[]|null
     */
    public function getConfigHandleMap(): ?array
    {
        $parent_map = (array)parent::getConfigHandleMap();

        // maps the configuration values to handlers
        $config_map = [
            [
                'handler' => 'web_service',
                'dirs' => [
                    $this->getBaseConfigDir(),
                    $this->getConfigDir(),
                ],
                'config_key' => 'ws',
            ],
            [
                'handler' => 'routing',
                'dirs' => [
                    $this->getBaseConfigDir(),
                    $this->getConfigDir(),
                ],
                'config_key' => 'ws_routing',
            ],
        ];

        $app_map = array_merge($parent_map, $config_map);

        unset($parent_map, $config_map);

        return $app_map;
    }

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->getProjectDir() . DS . 'config';
    }

    /**
     * @return array
     */
    public function getConfigParserVars(): array
    {
        return [];
    }
}
