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
abstract class lcEnvConfigHandler extends lcConfigHandler
{
    public const ENV_ALL = 'all';

    public const ENV_DEV = 'dev';
    public const ENV_PROD = 'prod';
    public const ENV_TEST = 'test';

    /**
     * @param $environment
     * @param array $data
     * @return array
     */
    protected function preReadConfigData($environment, array $data): array
    {
        $env_data = ($environment && isset($data[$environment])) ? (array)$data[$environment] : [];
        $all_data = isset($data[lcEnvConfigHandler::ENV_ALL]) ? (array)$data[lcEnvConfigHandler::ENV_ALL] : [];

        if (!$env_data && !$all_data) {
            return $data;
        }

        return lcArrays::mergeRecursiveDistinct($all_data, $env_data);
    }
}
