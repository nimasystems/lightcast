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

class lcIniFileParser extends lcFileParser
{
    const DEFAULT_EXT = '.ini';

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

        $data = @file_get_contents($filename);

        if (!$data || !is_array($data)) {
            return false;
        }

        $config_vars = isset($options['config_vars']) ? (array)$options['config_vars'] : [];

        $data = $this->parseConfigVars($data, $config_vars);

        $data = (array)array_filter(explode("\n", $data));

        $vals = [];

        foreach ($data as $k => $line) {
            if (!$line = trim($line)) {
                continue;
            }

            if ($line[0] == '#') {
                continue;
            }

            $ex = (array)array_filter(explode('=', $line));

            if (count($ex) != 2) {
                continue;
            }

            foreach ($ex as $kk => $vv) {
                $ex[$kk] = trim($vv);
                unset($kk, $vv);
            }

            $vals[$ex[0]] = $ex[1];

            unset($k, $line);
        }

        unset($data);

        return $vals;
    }

    public function writeData($data, array $options = null): bool
    {
        $data = is_array($data) ? $data : null;

        $str = [];

        foreach ($data as $name => $value) {
            // TODO: ini file data escaping must be done here
            $str[] = $name . ' = ' . $value;
            unset($name, $value);
        }

        $str = implode("\n", $str);

        return lcFiles::putFile($this->filename, $str);
    }
}
