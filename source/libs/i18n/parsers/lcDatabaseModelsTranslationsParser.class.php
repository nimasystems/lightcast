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

class lcDatabaseModelsTranslationsParser extends lcTranslationsParser
{
    protected $models;

    public function getDirsToParse()
    {
        return [
            'map' => [
                'file_endings' => ['TableMap.php']
            ],
            'om' => [
                'file_endings' => ['.php', 'Peer.php', 'Query.php']
            ]
        ];
    }

    public function getCategorizationMap()
    {
        return [
            'map' => 'database_models',
            'om' => 'database_models',
        ];
    }

    public function setModels($models)
    {
        $this->models = $models;
    }

    public function parse()
    {
        if (!$this->models) {
            throw new lcInvalidArgumentException('Models are required');
        }

        $this->results = [];

        $base_dir = $this->base_dir;

        // walk and parse
        $dirs = $this->getDirsToParse();

        $categorization_map = $this->getCategorizationMap();
        $models = $this->models;

        if ($dirs) {
            foreach ($dirs as $dir => $dir_options) {
                $category = isset($categorization_map[$dir]) ? $categorization_map[$dir] : self::DEFAULT_CATEGORY_NAME;
                $fullpath = $base_dir . DS . $dir;

                foreach ($models as $model) {
                    foreach ($dir_options['file_endings'] as $fend) {

                        $f = $fullpath . DS . lcInflector::camelize($model) . $fend;

                        if (file_exists($f)) {
                            $this->internalParseFile($f, $category);
                        }

                        unset($fend);
                    }

                    unset($model);
                }

                //lcDirs::recursiveFilesCallback($fullpath, array($this, 'internalParseFile'), array($category));

                unset($dir, $fullpath, $category, $dir_options);
            }
        }

        return $this->results;
    }
}