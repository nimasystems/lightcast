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
interface iConfigDataProvider
{
    /**
     * @param $config_key
     * @param array|null $options
     * @param array|null $config_vars
     * @return mixed
     */
    public function readConfigData($config_key, array $options = null, array $config_vars = null);

    /**
     * @param $config_key
     * @param array $config_data
     * @param array|null $options
     * @return mixed
     */
    public function writeConfigData($config_key, array $config_data, array $options = null);
}