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
 * E-Mail: info@nimasystems.com */

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcIniConfigDataProvider.class.php 1455 2013-10-25 20:29:31Z
 * mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */

require_once ('parsers' . DS . 'lcIniFileParser.class.php');

class lcIniConfigDataProvider extends lcObj implements iConfigDataProvider
{
    const DEFAULT_EXT = '.ini';

    public function readConfigData($config_key, array $options = null)
    {
        $dir = isset($options['dir']) ? (string)$options['dir'] : null;

        if (!$config_key || !$dir)
        {
            throw new lcInvalidArgumentException('Invalid config file / directory');
        }

        $filename = $dir . DS . $config_key . self::DEFAULT_EXT;

        $ini_parser = new lcIniFileParser($filename);
        return $ini_parser->parse();
    }

    public function writeConfigData($config_key, array $config_data, array $options = null)
    {
        $dir = isset($options['dir']) ? (string)$options['dir'] : null;

        if (!$config_key || !$dir)
        {
            throw new lcInvalidArgumentException('Invalid config file / directory');
        }

        $filename = $dir . DS . $config_key . self::DEFAULT_EXT;

        $ini_parser = new lcIniFileParser($filename);
        return $ini_parser->writeData($config_data, $options);
    }

}
?>