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

abstract class lcTranslationsParser extends lcObj
{
    const DEFAULT_CATEGORY_NAME = 'default';
    const ALL_SUBDIRS = '*';
    const TR_MULTILINE_DETECT_REP = '-----tn----';
    protected $event_dispatcher;
    protected $configuration;
    protected $base_dir;
    protected $conditions = [
        'template' => [
            ["/\{t\}(.*?)\{\/t\}/i" => '1'],
        ],
        'php' => [
            ["/this-\>(t|translate)\(('|\")(.*?)('|\")(\)?)/i" => '3'],
            ["/__\(('|\")(.*?)('|\")\)/i" => '2'],
        ],
    ];

    protected $results;

    public function __construct(lcEventDispatcher $event_dispatcher, lcConfiguration $configuration, $base_dir)
    {
        parent::__construct();

        $this->event_dispatcher = $event_dispatcher;
        $this->configuration = $configuration;
        $this->base_dir = $base_dir;
    }

    public function setConditions($file_type, array $conditions)
    {
        $this->conditions[$file_type] = $conditions;
    }

    /*
     * If translations should be separated and categorized by dirs
    * Should return an array of 'dir' => 'category name'
    */

    public function internalParseFile($filename, $category_name = self::DEFAULT_CATEGORY_NAME)
    {
        $scope = $this->getConditionScopeByFilename($filename);

        if (!$scope) {
            return false;
        }

        $parsed = $this->parseFile($filename, $scope);

        if (!$parsed) {
            return false;
        }

        $this->results[$category_name][] = [
            'filename' => $filename,
            'strings' => $parsed,
        ];

        return true;
    }

    private function getConditionScopeByFilename($filename)
    {
        if (substr(basename($filename), -3) == 'php') {
            return 'php';
        } else {
            return 'template';
        }
    }

    public function parseFile($filename, $filetype)
    {
        $filename = (string)$filename;
        $filetype = (string)$filetype;

        if (!$filename || !$filetype) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $fbase = basename($filename);

        // skip hidden files
        if ($fbase{0} == '.') {
            return false;
        }

        $matched = [];

        $conditions = isset($this->conditions[$filetype]) ? $this->conditions[$filetype] : null;

        if (!$conditions || !is_array($conditions)) {
            assert(false);
            return false;
        }

        try {
            $data = @file_get_contents($filename);

            if (!$data) {
                return false;
            }

            // remove new lines (make them a single space)
            $data = preg_replace("/\s*\n\s*/i", ' ', $data);

            foreach ($conditions as $condGroupKey => $cond) {
                $cond_orig = $cond;
                $ak = is_array($cond) ? array_keys($cond) : null;
                $preg_index = is_array($cond) ? $cond[$ak[0]] : 1;
                $cond = is_array($cond) ? $ak[0] : $cond;

                $res = preg_match_all($cond, $data, $matches);

                if ($res) {
                    if (!isset($matches[$preg_index])) {
                        continue;
                    }

                    foreach ($matches[$preg_index] as $match) {
                        if (in_array($match, $matched)) {
                            continue;
                        }

                        if (!$match || strlen($match) <= 1) {
                            continue;
                        }

                        $matched[] = stripslashes($match);
                        unset($match);
                    }

                    unset($matches);
                }

                unset($cond_orig, $cond, $res, $condGroupKey);
            }
        } catch (Exception $e) {
            throw new lcParsingException('Error while parsing translations in file ' . $filename . ': ' .
                $e->getMessage(), $e->getCode(), $e);
        }

        $matched = array_filter($matched);

        return $matched;
    }

    public function parse()
    {
        $this->results = [];

        $base_dir = $this->base_dir;

        // walk and parse
        $dirs = $this->getDirsToParse();

        // get the categorization map
        $categorization_map = $this->getCategorizationMap();

        if ($dirs) {
            if ($dirs == self::ALL_SUBDIRS) {
                lcDirs::recursiveFilesCallback($base_dir, [$this, 'internalParseFile']);
            } else if (is_array($dirs)) {
                // parse the base dir first
                lcDirs::recursiveFilesCallback($base_dir, [$this, 'internalParseFile'], [self::DEFAULT_CATEGORY_NAME], false);

                foreach ($dirs as $dir) {
                    $category = isset($categorization_map[$dir]) ? $categorization_map[$dir] : self::DEFAULT_CATEGORY_NAME;
                    $fullpath = $base_dir . DS . $dir;

                    lcDirs::recursiveFilesCallback($fullpath, [$this, 'internalParseFile'], [$category]);

                    unset($dir, $fullpath, $category);
                }
            }

            unset($dirs);
        }

        return $this->results;
    }

    abstract public function getDirsToParse();

    public function getCategorizationMap()
    {
        return false;
    }
}
