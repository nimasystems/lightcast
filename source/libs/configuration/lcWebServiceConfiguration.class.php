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
 * @changed $Id: lcWebServiceConfiguration.class.php 1455 2013-10-25 20:29:31Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */
class lcWebServiceConfiguration extends lcApplicationConfiguration
{
    const DEFAULT_APP_NAME = 'ws';

    public function initialize()
    {
        parent::initialize();
    }

    public function getApplicationName()
    {
        return self::DEFAULT_APP_NAME;
    }

    public function getApiLevel()
    {
        return 1;
    }

    public function getProjectConfigDir()
    {
        return null;
    }

    public function getConfigHandleMap()
    {
        $parent_map = (array)parent::getConfigHandleMap();

        // maps the configuration values to handlers
        $config_map = array(
            array(
                'handler' => 'web_service',
                'dirs' => array(
                    $this->getBaseConfigDir(),
                    $this->getConfigDir()
                ),
                'config_key' => 'ws'
            ),
            array(
                'handler' => 'routing',
                'dirs' => array(
                    $this->getBaseConfigDir(),
                    $this->getConfigDir()
                ),
                'config_key' => 'ws_routing'
            ),
        );

        $app_map = array_merge($parent_map, $config_map);

        unset($parent_map, $config_map);

        return $app_map;
    }

    public function getConfigDir()
    {
        return $this->getProjectDir() . DS . 'config';
    }
}