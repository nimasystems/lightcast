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

class lcYamlFileParser extends lcFileParser
{
    const DEFAULT_EXT = '.yml';
    const INDENT_VALUE = 2;
    const WORD_WRAP_VALUE = 0;

    /**
     * @param string $content
     * @param array $vars
     * @return string
     */
    protected function parseConfigVars(string $content, array $vars): string
    {
        if ($vars) {
            foreach ($vars as $key => $value) {
                $content = str_replace($key, $value, $content);
            }
        }

        return $content;
    }

    public function parse(array $options = null)
    {
        $filename = $this->filename;

        try {
            require_once(ROOT . DS . 'source' . DS . '3rdparty' . DS . 'spyc' . DS . DS . 'spyc.php');

            $config_vars = isset($options['config_vars']) ? (array)$options['config_vars'] : [];

            // syck / yaml are MUCH FASTER!
            if (function_exists('yaml_parse')) {
                $contents = @file_get_contents($filename);

                if (!$contents) {
                    return false;
                }

                $contents = $this->parseConfigVars($contents, $config_vars);

                $data = yaml_parse($contents);
            } else if (function_exists('syck_load')) {
                $contents = @file_get_contents($filename);

                if (!$contents) {
                    return false;
                }

                $data = syck_load($contents);
            } else if (class_exists('Spyc')) {
                // manually load the configuration and parse it
                // Spyc does not strictly adhere to YAML 1.1 so if there is no
                // space
                // before array members it messes them up - so we add them
                // ourselves!
                $data = @file_get_contents($filename);

                $data = $this->parseConfigVars($data, $config_vars);

                $data = $data ? $this->fixSpycContent($data) : null;
                $data = $data ? Spyc::YAMLLoadString($data) : null;
            } else {
                throw new lcSystemException('No YAML parser available');
            }

            return $data;
        } catch (Exception $e) {
            throw new lcSystemException('Could not parse config file (' . $filename . '): ' . $e->getMessage());
        }
    }

    private function fixSpycContent($yaml_content): string
    {
        // iterate thru string
        $final = '';
        $lines = explode("\n", $yaml_content);

        foreach ($lines as $line) {
            // check for dash...
            $trim = ltrim($line);
            if (substr($trim, 0, 1) === '-') {
                // bump space
                $line = '  ' . $line;
            }

            // add back to string
            $final .= $line . "\n";
        }

        // return
        return $final;
    }

    public function writeData($data, array $options = null): bool
    {
        $filename = $this->filename;
        $data = is_array($data) ? $data : null;

        $indent = isset($options['indent']) ? $options['indent'] : self::INDENT_VALUE;
        $word_wrap = isset($options['word_wrap']) ? $options['word_wrap'] : self::WORD_WRAP_VALUE;

        try {
            try {
                // trim all values to fix non-visual empty spaces which may cause
                // problems later
                if ($data && !array_walk_recursive($data, [
                        $this,
                        'trimYamlValue',
                    ])
                ) {
                    throw new lcSystemException('Could not walk YAML configuration');
                }

                // syck / yaml are MUCH FASTER!
                if (function_exists('yaml_emit')) {
                    $data = yaml_emit($data);
                } else if (function_exists('syck_dump')) {
                    $data = syck_dump($data);
                } else if (class_exists('Spyc')) {
                    $data = Spyc::YAMLDump($data, $indent, $word_wrap);
                } else {
                    throw new lcSystemException('YAML Parser missing');
                }
            } catch (Exception $ee) {
                throw new lcSystemException('YAML Dump error: ' . $ee->getMessage(), $ee->getCode(), $ee);
            }

            return lcFiles::putFile($filename, $data);
        } catch (Exception $e) {
            throw new lcSystemException('Error while trying to save data to config file (' . $filename . '): ' . $e->getMessage(), $e->getCode(), $e);
        }
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
