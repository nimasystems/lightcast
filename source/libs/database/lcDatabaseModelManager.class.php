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
class lcDatabaseModelManager extends lcSysObj implements iDatabaseModelManager, iCacheable
{
    protected array $model_paths = [];
    protected array $registered_models = [];

    /**
     * @var mixed
     */
    protected $db_select_column_mappings;

    private array $used_models = [];
    private ?string $models_gen_dir = null;

    public const DEFAULT_NAMESPACE = 'Gen\\Propel\\Models';

    protected string $model_namespace = self::DEFAULT_NAMESPACE;

    public function initialize()
    {
        parent::initialize();

        $cfg = $this->configuration;

        // setup models_gen_dir
        $propel_custom_gen_dir = (string)$cfg['db.propel_custom.gen_dir'];

        if ($propel_custom_gen_dir) {
            $this->models_gen_dir = $cfg->getGenDir() . DS . $propel_custom_gen_dir . DS . 'Models';
        }

        // observe use_models filter
        $this->event_dispatcher->connect('database_model_manager.register_models', $this, 'onRegisterModels');
    }

    public function shutdown()
    {
        $this->registered_models = $this->model_paths = $this->used_models = [];

        parent::shutdown();
    }

    /**
     * @param lcEvent $event
     * @param $models
     * @return mixed
     * @throws lcDatabaseException
     * @throws lcInvalidArgumentException
     */
    public function onRegisterModels(lcEvent $event, $models)
    {
        $path_to_models = $event->params['path_to_models'] ?? null;
        $namespace = $event->params['namespace'] ?? '';

        if ($path_to_models && $models && is_array($models)) {
            $this->registerModelClasses($path_to_models, $namespace, $models);
            $event->setProcessed();
        }

        return $models;
    }

    /**
     * @param string $path_to_models
     * @param string $namespace
     * @param array $models
     * @return void
     * @throws lcDatabaseException
     * @throws lcInvalidArgumentException
     */
    public function registerModelClasses(string $path_to_models, string $namespace, array $models)
    {
        if (!$path_to_models || !$models) {
            throw new lcInvalidArgumentException('Invalid path / models');
        }

        $path_index = array_keys($this->model_paths, $path_to_models);

        if (!$path_index) {
            $path_index = count($this->model_paths);
            $this->model_paths[$path_index] = $path_to_models;
        } else {
            $path_index = $path_index[0];
        }

        foreach ($models as $model) {
            $namespaced_model = ($namespace ? $namespace . '\\' : '') . $model;

            if (isset($this->registered_models[$namespaced_model])) {
                throw new lcDatabaseException('Duplicate model registration (' . $model . ' / ' . $path_to_models . '), ' .
                    'previously declared in: ' . $this->model_paths[$this->registered_models[$namespaced_model]]);
            }

            $this->registered_models[$namespaced_model] = [
                'index' => $path_index,
                'model' => $model,
                'namespace' => $namespace,
            ];

            unset($model);
        }

        if (DO_DEBUG) {
            $this->debug('Registered db models at path (' . $path_to_models . '): ' . print_r($models, true));
        }
    }

    /**
     * @param lcEvent $event
     * @param $models
     * @return mixed
     * @throws lcDatabaseException
     */
    public function onUseModels(lcEvent $event, $models)
    {
        if ($models && is_array($models)) {
            $this->useModels($models);
            $event->setProcessed();
        }

        return $models;
    }

    public function useModels(array $models)
    {
        foreach ($models as $model_name) {
            try {
                $this->useModel($model_name);
            } catch (Exception $e) {
                throw new lcDatabaseException('Could not use model \'' . $model_name .
                    '\': ' . $e->getMessage(), $e->getCode(), $e);
            }

            unset($model_name);
        }
    }

    /**
     * @param string $namespace
     * @param string $model_name
     * @return bool
     * @throws lcInvalidArgumentException
     * @throws lcNotAvailableException
     */
    public function useModel(string $namespace, string $model_name): bool
    {
        if (!$model_name) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $namespaced_model = ($namespace ? $namespace . '\\' : '') . $model_name;

        // check if already used
        if (in_array($namespaced_model, $this->used_models)) {
            return true;
        }

        if (!isset($this->registered_models[$namespaced_model])) {
            throw new lcNotAvailableException('Model not available');
        }

        $this->_useModel($namespace, $model_name);

        return true;
    }

    /**
     * @param string $namespace
     * @param string $model_name
     * @param bool $already_camelized
     * @return void
     */
    protected function _useModel(string $namespace, string $model_name, bool $already_camelized = false)
    {
        assert(!is_null($model_name));

        $namespaced_model = ($namespace ? $namespace . '\\' : '') . $model_name;

        $path_to_model = $this->model_paths[$this->registered_models[$namespaced_model]];

        $model_inf = !$already_camelized ? lcInflector::camelize($model_name, false) : $model_name;

        $classes = [
            $model_inf => $path_to_model . DS . $model_inf . '.php',
            $model_inf . 'Peer' => $path_to_model . DS . $model_inf . 'Peer.php',
            $model_inf . 'Query' => $path_to_model . DS . $model_inf . 'Query.php',
        ];

        // use custom gen dir or in place with models
        $path_to_gen_classes = $this->models_gen_dir ?: $path_to_model;

        $gen_classes = [
            'Base' . $model_inf => $path_to_gen_classes . DS . 'om' . DS . 'Base' . $model_inf . '.php',
            'Base' . $model_inf . 'Peer' => $path_to_gen_classes . DS . 'om' . DS . 'Base' . $model_inf . 'Peer.php',
            'Base' . $model_inf . 'Query' => $path_to_gen_classes . DS . 'om' . DS . 'Base' . $model_inf . 'Query.php',
            $model_inf . 'TableMap' => $path_to_gen_classes . DS . 'map' . DS . $model_inf . 'TableMap.php',
        ];

        $classes = array_merge($classes, $gen_classes);

        $class_autoloader = $this->class_autoloader;

        foreach ($classes as $class_name => $filename) {
            $class_autoloader->addClass($class_name, $filename);
            unset($class_name, $filename);
        }

        $this->used_models[] = $model_name;

        // load the table map to include related tables also
        /*$tblmap_class = $model_inf . 'TableMap';

         if (!class_exists($tblmap_class))
         {
         throw new lcSystemException('TableMap of model \'' . $model_name . '\'
         cannot be found (' . $tblmap_class . ')');
         }

         // include models from related tables also
         $related_models = $tblmap_class::getForeignKeyRelations();

         if ($related_models)
         {
         try
         {
         $this->useModels($related_models);
         }
         catch(Exception $e)
         {
         throw new lcSystemException('Could not use models from relations: ' .
         $e->getMessage(),
         $e->getCode(),
         $e);
         }
         }*/

        if (DO_DEBUG) {
            $this->debug('Used db model: ' . $model_name);
        }
    }

    public function getRegisteredModelNames(): array
    {
        return array_keys($this->registered_models);
    }

    public function getRegisteredModels(): array
    {
        return $this->registered_models;
    }

    public function getUsedModels(): array
    {
        return $this->used_models;
    }

    /**
     * @param $container_identifier
     * @param $query_identifier
     * @return mixed|null
     */
    public function getQuerySelectColumns($container_identifier, $query_identifier)
    {
        $mappings = $this->getDbSelectColumnMappings();
        return ($mappings[$container_identifier][$query_identifier] ?? null);
    }

    public function getDbSelectColumnMappings(): array
    {
        if (empty($this->db_select_column_mappings)) {

            $plcs = $this->plugin_manager->getPluginConfigurations();

            $all = [];

            foreach ($plcs as $plc) {
                if ($plc instanceof iProvidesDbSelectColumnMappings) {
                    $selcols = $plc->getDbQuerySelectColumns();

                    if ($selcols && is_array($selcols)) {
                        foreach ($selcols as $package_identifier => $queries) {

                            foreach ($queries as $query_identifier => $config) {
                                $tmp = isset($all[$package_identifier][$query_identifier]) ?
                                    (array)$all[$package_identifier][$query_identifier] : [];

                                $tmp = array_merge($tmp, $config);

                                $all[$package_identifier][$query_identifier] = $tmp;

                                unset($query_identifier, $config, $tmp);
                            }

                            unset($package_identifier, $query_identifier);
                        }
                    }
                }

                unset($plc);
            }

            $this->db_select_column_mappings = $all;
        }

        return $this->db_select_column_mappings;
    }

    public function writeClassCache(): array
    {
        return [
            'db_select_column_mappings' => $this->db_select_column_mappings,
        ];
    }

    public function readClassCache(array $cached_data)
    {
        $this->db_select_column_mappings = $cached_data['db_select_column_mappings'] ?? null;
    }
}
