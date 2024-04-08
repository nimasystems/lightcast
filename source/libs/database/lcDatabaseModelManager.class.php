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
    protected array $all_registered_model_classes = [];

    /**
     * @var mixed
     */
    protected $db_select_column_mappings;

    private bool $spl_registered = false;
//    private array $used_models = [];
    private ?string $models_gen_dir = null;

    public const DEFAULT_BASE_MODEL_NAMESPACE = 'Gen\\Propel\\Models';

    protected string $base_model_namespace = self::DEFAULT_BASE_MODEL_NAMESPACE;

    public function initialize()
    {
        parent::initialize();

        $cfg = $this->configuration;

        // setup models_gen_dir
        $propel_custom_gen_dir = (string)$cfg['db.propel_custom.gen_dir'];

        if ($propel_custom_gen_dir) {
            $this->models_gen_dir = $cfg->getGenDir() . DS . $propel_custom_gen_dir . DS . 'Models';
        }

        // register into system
        $this->splRegister();

        // observe use_models filter
        $this->event_dispatcher->connect('database_model_manager.register_models', $this, 'onRegisterModels');
//        $this->event_dispatcher->connect('database_model_manager.use_models', $this, 'onUseModels');
    }

    public function splRegister(): bool
    {
        if ($this->spl_registered) {
            return true;
        }

        $registered = spl_autoload_register([$this, 'loadClass']);

        $this->spl_registered = $registered;

        return $registered;
    }

    public function splUnregister(): bool
    {
        if (!$this->spl_registered) {
            return false;
        }

        $registered = spl_autoload_unregister([$this, 'loadClass']);

        $this->spl_registered = false;

        return $registered;
    }

    public function isSplRegistered(): bool
    {
        return $this->spl_registered;
    }

    public function shutdown()
    {
        $this->splUnregister();

        $this->registered_models =
        $this->all_registered_model_classes =
        $this->model_paths = [];

        parent::shutdown();
    }

    protected function loadClass(string $class_name)
    {
        $path = $this->all_registered_model_classes[$class_name] ?? null;

        if ($path) {
            include_once($path);
        }
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

            $path_to_gen_classes = $this->models_gen_dir ?: $path_to_models;
            $gen_classes = $this->getGenClasses($model, $path_to_gen_classes);

            $this->all_registered_model_classes[$namespaced_model] = $path_to_models . DS . $model . '.php';
            $this->all_registered_model_classes = array_merge($this->all_registered_model_classes,
                $gen_classes,
            );

            unset($model);
        }

        if (DO_DEBUG) {
            $this->debug('Registered db models at path (' . $path_to_models . '): ' . print_r($models, true));
        }
    }

    protected function getGenClasses(string $model_name, string $path): array
    {
        return [
            $this->base_model_namespace . '\\Om\\Base' . $model_name => $path . DS . 'Om' . DS .
                'Base' . $model_name . '.php',
            $this->base_model_namespace . '\\Om\\Base' . $model_name . 'Peer' => $path . DS . 'Om' .
                DS . 'Base' . $model_name . 'Peer.php',
            $this->base_model_namespace . '\\Om\\Base' . $model_name . 'Query' => $path . DS . 'Om' .
                DS . 'Base' . $model_name . 'Query.php',
            $this->base_model_namespace . '\\Map\\Base' . $model_name . 'TableMap' => $path . DS . 'Map' .
                DS . 'Base' . $model_name . 'TableMap.php',
        ];
    }

//    /**
//     * @param lcEvent $event
//     * @param $models
//     * @return mixed
//     * @throws lcDatabaseException
//     */
//    public function onUseModels(lcEvent $event, $models)
//    {
//        $namespace = $event['namespace'] ?? '';
//
//        if ($models && is_array($models)) {
//            $this->useModels($namespace, $models);
//            $event->setProcessed();
//        }
//
//        return $models;
//    }

//    public function useModels(string $namespace, array $models)
//    {
//        foreach ($models as $model_name) {
//            try {
//                $this->useModel($namespace, $model_name);
//            } catch (Exception $e) {
//                throw new lcDatabaseException('Could not use model \'' .
//                    ($namespace ? $namespace . '\\' : '') . $model_name .
//                    '\': ' . $e->getMessage(), $e->getCode(), $e);
//            }
//
//            unset($model_name);
//        }
//    }
//
//    /**
//     * @param string $namespace
//     * @param string $model_name
//     * @return bool
//     * @throws lcInvalidArgumentException
//     * @throws lcNotAvailableException
//     */
//    public function useModel(string $namespace, string $model_name): bool
//    {
//        if (!$model_name) {
//            throw new lcInvalidArgumentException('Invalid params');
//        }
//
//        $namespaced_model = ($namespace ? $namespace . '\\' : '') . $model_name;
//
//        // check if already used
//        if (in_array($namespaced_model, $this->used_models)) {
//            return true;
//        }
//
//        if (!isset($this->registered_models[$namespaced_model])) {
//            throw new lcNotAvailableException('Model not available');
//        }
//
//        $this->_useModel($namespace, $model_name);
//
//        return true;
//    }
//
//    /**
//     * @param string $namespace
//     * @param string $model_name
//     * @return void
//     */
//    protected function _useModel(string $namespace, string $model_name)
//    {
//        return;
//
//        assert(!is_null($model_name));
//
//        $namespaced_model = ($namespace ? $namespace . '\\' : '') . $model_name;
//
//        $path_to_model = $this->model_paths[$this->registered_models[$namespaced_model]['index']];
//
//        $classes = [
//            $namespaced_model => $path_to_model . DS . $model_name . '.php',
//            $namespaced_model . 'Peer' => $path_to_model . DS . $model_name . 'Peer.php',
//            $namespaced_model . 'Query' => $path_to_model . DS . $model_name . 'Query.php',
//        ];
//
//        // use custom gen dir or in place with models
//        $path_to_gen_classes = $this->models_gen_dir ?: $path_to_model;
//        $classes = array_merge($classes, $this->getGenClasses($model_name, $path_to_gen_classes));
//        $class_autoloader = $this->class_autoloader;
//
//        foreach ($classes as $class_name => $filename) {
//            $class_autoloader->addClass($class_name, $filename);
//            unset($class_name, $filename);
//        }
//
//        $this->used_models[] = $model_name;
//
//        // load the table map to include related tables also
//        /*$tblmap_class = $model_inf . 'TableMap';
//
//         if (!class_exists($tblmap_class))
//         {
//         throw new lcSystemException('TableMap of model \'' . $model_name . '\'
//         cannot be found (' . $tblmap_class . ')');
//         }
//
//         // include models from related tables also
//         $related_models = $tblmap_class::getForeignKeyRelations();
//
//         if ($related_models)
//         {
//         try
//         {
//         $this->useModels($related_models);
//         }
//         catch(Exception $e)
//         {
//         throw new lcSystemException('Could not use models from relations: ' .
//         $e->getMessage(),
//         $e->getCode(),
//         $e);
//         }
//         }*/
//
//        if (DO_DEBUG) {
//            $this->debug('Used db model: ' . $model_name);
//        }
//    }

    public function getRegisteredModelNames(): array
    {
        return array_keys($this->registered_models);
    }

    public function getRegisteredModels(): array
    {
        return $this->registered_models;
    }

//    public function getUsedModels(): array
//    {
//        return $this->used_models;
//    }

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
