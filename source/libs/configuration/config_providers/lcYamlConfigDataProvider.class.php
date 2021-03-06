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

class lcYamlConfigDataProvider extends lcObj implements iConfigDataProvider
{
    const DEFAULT_EXT = '.yml';
    const INDENT_VALUE = 2;
    const WORD_WRAP_VALUE = 0;

    public function readConfigData($config_key, array $options = null, array $config_vars = null)
    {
        $dir = isset($options['dir']) ? (string)$options['dir'] : null;

        if (!$config_key || !$dir) {
            throw new lcInvalidArgumentException('Invalid config file / directory');
        }

        $filename = $dir . DS . $config_key . self::DEFAULT_EXT;

        $yaml_parser = new lcYamlFileParser($filename);
        return $yaml_parser->parse([
            'config_vars' => $config_vars,
        ]);
    }

    public function writeConfigData($config_key, array $config_data, array $options = null): bool
    {
        $dir = isset($options['dir']) ? (string)$options['dir'] : null;

        if (!$config_key || !$dir) {
            throw new lcInvalidArgumentException('Invalid config file / directory');
        }

        $indent = isset($options['indent']) ? $options['indent'] : self::INDENT_VALUE;
        $word_wrap = isset($options['word_wrap']) ? $options['word_wrap'] : self::WORD_WRAP_VALUE;

        $filename = $dir . DS . $config_key . self::DEFAULT_EXT;

        $yaml_parser = new lcYamlFileParser($filename);
        return $yaml_parser->writeData($config_data, [
            'indent' => $indent,
            'word_wrap' => $word_wrap,
        ]);
    }

    protected function trimYamlValue($val)
    {
        if (is_array($val)) {
            return $val;
        }

        $val = trim($val);

        return $val;
    }

}
