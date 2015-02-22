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

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcEnvConfigHandler.class.php 1455 2013-10-25 20:29:31Z mkovachev
 * $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */

abstract class lcEnvConfigHandler extends lcConfigHandler
{
    const ENVIRONMENT_ALL = 'all';
    const ENVIRONMENT_DEBUG = 'debug';
    const ENVIRONMENT_RELEASE = 'release';
    const ENVIRONMENT_TESTING = 'testing';

    protected function preReadConfigData($environment, array $data)
    {
        $env_data = ($environment && isset($data[$environment])) ? (array)$data[$environment] : array();
        $all_data = isset($data[lcEnvConfigHandler::ENVIRONMENT_ALL]) ? (array)$data[lcEnvConfigHandler::ENVIRONMENT_ALL] : array();

        if (!$env_data && !$all_data)
        {
            return $data;
        }

        $env_data = lcArrays::mergeRecursiveDistinct($all_data, $env_data);

        return $env_data;
    }

}
?>