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

class tPropel extends lcTaskController
{
    const SCHEMA_FILE = 'schema.xml';
    const PROPEL_REVERSE_PARSE_CLASS = 'lcPropelMysqlSchemaParser';
    const PROPEL_GENERATOR_CONFIG = 'build.properties';
    const DEFAULT_REVERSE_TARGET_NAME = 'reversed-schema';
    const DEFAULT_REVERSE_TARGET_FOLDER = 'data/db';
    const DEFAULT_BUILD_PROPERTIES_STATIC = 'shell/build_static.properties';

    const PROJECT_PREFIX = 'project_';

    private $schema_files_tmp;
    private $schemas_initialized;
    private $work_dir;
    private $phing_has_error;
    private $plugin_configs = [];

    public function initialize()
    {
        parent::initialize();

        $this->initPropelGenerator();
        $this->createWorkingDirectory();
    }

    private function initPropelGenerator()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        set_include_path(get_include_path() . PATH_SEPARATOR . $this->configuration->getThirdPartyDir() . DS . 'propel' . DS . 'generator' . DS . 'lib' .
            PATH_SEPARATOR . $this->configuration->getThirdPartyDir() . DS . 'phing' . DS . 'classes');

        $this->includePhing();

        /** @noinspection PhpIncludeInspection */
        require_once 'task/PropelOMTask.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/PHP5PeerBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/PHP5ExtensionPeerBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/PHP5ExtensionObjectBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/PHP5TableMapBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/PHP5ObjectBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/om/QueryBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'platform/MysqlPlatform.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'reverse/mysql/MysqlSchemaParser.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/sql/DataSQLBuilder.php';
        /** @noinspection PhpIncludeInspection */
        require_once 'builder/sql/mysql/MysqlDataSQLBuilder.php';

        //require_once($propel_dir . DS .
        // 'PropelLightcastCacheBehavior.class.php');
    }

    // TODO: Make this script NEVER change original XML files
    // but only change the ones in the TEMP dir!!!!

    public function includePhing()
    {
        /** @noinspection PhpIncludeInspection */
        require_once($this->configuration->getThirdPartyDir() . DS . 'phing' . DS . 'vendor' . DS . 'autoload.php');

        /** @noinspection PhpIncludeInspection */
        require_once 'phing/listener/AnsiColorLogger.php';
    }

    private function createWorkingDirectory()
    {
        if ($this->work_dir) {
            return true;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $this->work_dir = $this->configuration->getGenDir() . DS . 'propel';

        lcDirs::mkdirRecursive($this->work_dir);

        return true;
    }

    public function shutdown()
    {
        // cleanup in case the script failed
        $this->phingCleanUp();

        spl_autoload_unregister([
            $this,
            'phingAutoloadClass',
        ]);

        parent::shutdown();
    }

    private function phingCleanUp()
    {
        $this->schema_files_tmp = null;
        $this->work_dir = null;
    }

    public function executeTask()
    {
        // check necessary requirements
        $this->precheck();

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $ret = false;

        switch ($this->getRequest()->getParam('action')) {
            case 'models' :
            {
                $ret = $this->propelOm();
                break;
            }
            case 'sql' :
            {
                $ret = $this->propelSql();
                break;
            }
            case 'graphviz' :
            {
                $ret = $this->propelGraphviz();
                break;
            }
            case 'create-db' :
            {
                $ret = $this->propelCreateDb();
                break;
            }
            case 'insert-sql' :
            {
                $ret = $this->propelInsertSql();
                break;
            }
            case 'build-sql' :
            {
                $ret = $this->propelBuildSql();
                break;
            }
            case 'reverse' :
            {
                $ret = $this->propelReverse();
                break;
            }
            case 'flush' :
            {
                $ret = $this->propelFlush();
                break;
            }
            case 'rebuild' :
            {
                $ret = $this->propelRebuild();
                break;
            }
            case 'migrate' :
            {
                $this->propelMigrate();
                break;
            }
            default :
            {
                $ret = $this->displayHelp();
                break;
            }
        }

        if (!$ret) {
            return false;
        }

        return true;
    }

    private function precheck()
    {
        // check if we have PDO support for propel
        if (!class_exists('PDO', false)) {
            throw new lcSystemException('Propel needs PDO support enabled in PHP.');
        }
    }

    private function propelOm()
    {
        $this->propelFlush();
        $this->propelInitSchemas();
        $ret = $this->phingExecute('om');
        return $ret;
    }

    /* Remove propel temporary stuff, oms, maps, sql files */
    private function propelFlush()
    {
        if (!$this->request->getIsSilent()) {
            $this->display('Flushing runtime data...');
        }

        /** @var lcProjectConfiguration $cfg */
        $cfg = $this->configuration;
        $dir = $cfg->getAppRootDir();
        $gen_dir = $cfg->getGenDir();

        // remove generated model om/map files
        lcDirs::rmdirRecursive($gen_dir . DS . 'propel', true);

        lcDirs::rmdirRecursive($dir . DS . 'data' . DS . 'graphviz');
        lcDirs::rmdirRecursive($dir . DS . 'data' . DS . 'sql');
        lcFiles::rm($dir . DS . 'data' . DS . 'reversed-schema.xml');

        // @compatibility - remove map/om files from models/ plugin/models/ dirs
        // which were used by previous LC versions
        // remove map/om files
        lcDirs::rmdirRecursive($dir . DS . 'models' . DS . 'map');
        lcDirs::rmdirRecursive($dir . DS . 'models' . DS . 'om');

        // remove from plugins
        $plugins = $this->system_component_factory->getSystemPluginDetails();

        if ($plugins) {
            foreach ($plugins as $plugin_name => $plugin_data) {
                lcDirs::rmdirRecursive($plugin_data['path'] . DS . 'models' . DS . 'map');
                lcDirs::rmdirRecursive($plugin_data['path'] . DS . 'models' . DS . 'om');

                unset($plugin_data, $plugin_name);
            }

            unset($plugins);
        }

        unset($dir);

        return true;
    }

    private function propelInitSchemas($ent = null)
    {
        if ($this->schemas_initialized) {
            return false;
        }

        $this->schema_files_tmp = null;

        // only specific entities
        if (!isset($ent)) {
            if ($ent = $this->getRequest()->getParam('only-entities')) {
                if (!$ent = array_filter(explode(',', $ent))) {
                    $ent = null;
                }
            }
        }

        // obtain datasource name
        $db_conf = $this->getPrimaryDatabaseConfig();

        if (!isset($db_conf['propel.project'])) {
            throw new lcInvalidArgumentException('Missing propel.project in configuration!');
        }

        $datasource_name = (string)$db_conf['propel.project'];

        //$propel_config = $this->configuration['db.propel'];
        //$default_translator = isset($propel_config['propel.defaultTranslator'])
        // ? (string)$propel_config['propel.defaultTranslator'] : null;

        $schemas = $this->getSchemaDetails($ent);

        // walk schemas
        foreach ($schemas as $type => $schema_item_details) {
            $type_name = lcController::getContextTypeAsString($type);
            assert(!is_null($type_name));

            foreach ($schema_item_details as $name => $schema_details) {
                $path = $schema_details['path'];

                $source_filename = $path . DS . self::SCHEMA_FILE;
                $rf = $type_name . '_' . $name . '-' . self::SCHEMA_FILE;
                $target_filename = $this->work_dir . DS . $rf;

                if (file_exists($source_filename)) {
                    lcFiles::copy($source_filename, $target_filename);
                } else {
                    // create it
                    lcFiles::putFile($target_filename, $this->getDefaultSchemaContent());
                }

                $this->schema_files_tmp[$path][] = [
                    $source_filename,
                    $rf,
                ];

                // fix CASCADE/RESTRICT/SET NULL problems on windows
                // (uppercase)
                $this->fixWindowsUppercaseRestrictions($target_filename);

                $options = ['datasource' => $datasource_name];

                $options['context_type'] = $type_name;

                // project name workaround
                $options['context_name'] = (is_numeric($name) ? '' : $name);

                // if a plugin - set package, otherwise 'models'
                $options['package'] = ($type_name == 'plugin') ? 'addons.plugins.' . htmlspecialchars($name) . '.models' : 'models';

                $this->fixSchema($target_filename, $options, (array)$schema_details['models']);

                unset($name, $schema_details, $path, $source_filename, $target_filename, $rf, $options);
            }

            unset($type, $type_name, $schema_paths);
        }

        unset($schemas, $ent);

        $this->schemas_initialized = true;

        return true;
    }

    private function getPrimaryDatabaseConfig()
    {
        $config = [];

        if ($d = $this->configuration['db.databases']) {
            if (isset($d['primary'])) {
                $d = $d['primary'];

                $config = [
                    'propel.project' => @$d['datasource'],
                    'propel.database.buildUrl' => @$d['url'],
                    'propel.database.user' => @$d['user'],
                    'propel.database.password' => @$d['password'],
                    'propel.database.encoding' => @$d['charset'],
                ];
            }

            unset($d);
        }

        return $config;
    }

    private function getSchemaDetails(array $only_entities = null)
    {
        // holder for all folders containing schemas
        // all schemas will be looked up - *-schema.xml, schema.xml
        $schemas = [];

        // general project schemas
        $schemas[lcSysObj::CONTEXT_PROJECT] = [];

        if ((isset($only_entities)) && (in_array('project', $only_entities)) || (!isset($only_entities))) {
            $schemas[lcSysObj::CONTEXT_PROJECT]['main']['path'] = $this->configuration->getConfigDir();
            $schemas[lcSysObj::CONTEXT_PROJECT]['main']['models'] = $this->getProjectModels();
        }

        // plugin schemas
        $schemas[lcSysObj::CONTEXT_PLUGIN] = [];

        $plugins = $this->system_component_factory->getSystemPluginDetails();

        if ($plugins) {
            foreach ($plugins as $plugin_name => $plugin_data) {
                if (isset($only_entities) && !in_array($plugin_data['name'], $only_entities)) {
                    continue;
                }

                $models = $this->getPluginModels($plugin_name, $plugin_data['path']);

                if (!$models) {
                    continue;
                }

                $schemas[lcSysObj::CONTEXT_PLUGIN][$plugin_data['name']]['path'] = $plugin_data['path'] . DS . 'config';
                $schemas[lcSysObj::CONTEXT_PLUGIN][$plugin_data['name']]['models'] = $models;

                unset($plugin_data, $plugin_name);
            }

            unset($plugins);
        }

        return $schemas;
    }

    private function getProjectModels()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return ($this->configuration->getProjectConfiguration() instanceof iSupportsDbModels ? $this->configuration->getProjectConfiguration()->getDbModels() : null);
    }

    private function getPluginModels($plugin_name, $plugin_path)
    {
        $plcfg = $this->getPluginConfig($plugin_name, $plugin_path);

        return ($plcfg && $plcfg instanceof iSupportsDbModels ? $plcfg->getDbModels() : null);
    }

    private function getPluginConfig($plugin_name, $plugin_path)
    {
        if (!isset($this->plugin_configs[$plugin_name])) {
            $pl_config = $this->getPluginManager()->getInstanceOfPluginConfiguration($plugin_path, $plugin_name, null);

            if (!$pl_config) {
                return null;
            }

            $this->plugin_configs[$plugin_name] = $pl_config;
        }

        return $this->plugin_configs[$plugin_name];
    }

    private function getDefaultSchemaContent()
    {
        return '<?xml version="1.0" encoding="utf-8"?><database package="models" defaultIdMethod="native" baseClass="lcBasePropelObject" defaultTranslateMethod="$this-&gt;translate"></database>';
    }

    private function fixWindowsUppercaseRestrictions($filename)
    {
        $rep = [
            '"CASCADE"',
            '"SET NULL"',
            '"RESTRICT"',
        ];

        $f = lcFiles::getFile($filename);

        foreach ($rep as $k => $v) {
            $f = str_replace($v, strtolower($v), $f);
            unset($k, $v);
        }

        lcFiles::putFile($filename, $f);
        unset($f);
    }

    private function fixSchema($filename, array $options, array $supported_models)
    {
        $fdata = @file_get_contents($filename);

        if (!$fdata) {
            return;
        }

        $pXml = new DOMDocument;
        $pXml->loadXML($fdata);

        $element = $pXml->documentElement;

        // set propel to use a custom baseClass, basePeer classes
        $element->setAttribute('baseClass', lcPropel::BASE_CLASS);

        // unfortunately - to override the peer a lot more things have to be
        // overriden in the peer builder - so we leave it as it is
        // not that many things that will be used there anyway for the moment
        //$element->setAttribute('basePeer', lcPropel::BASE_PEER_CLASS);

        // set the context_type, context_name to TABLE object
        // preferrably this would be set to DATABASE but when propel merges the
        // multiply
        // xml schema files
        // add missing tables
        // remove obsolete tables
        $tables_xpath_search = new DOMXPath($pXml);
        $tables_dom = $tables_xpath_search->query('/database/table');

        $db_q = $tables_xpath_search->query('/database');

        if ($tables_dom->length) {
            foreach ($tables_dom as $table) {
                /** @var DomElement $table */

                $table_name = $table->getAttribute('name');

                // remove if not in supported models
                if (!in_array($table_name, $supported_models)) {
                    $table->parentNode->removeChild($table);
                }

                if (isset($options['context_type']) && $options['context_type']) {
                    $table->setAttribute(lcPropel::CONTEXT_TYPE_ATTR, $options['context_type']);
                }

                if (isset($options['context_name']) && $options['context_name']) {
                    $table->setAttribute(lcPropel::CONTEXT_NAME_ATTR, $options['context_name']);
                }

                unset($table);
            }
        }

        // add the missing ones
        foreach ($supported_models as $model_name) {

            $found = false;

            if ($tables_dom->length) {
                foreach ($tables_dom as $table) {

                    $table_name = $table->getAttribute('name');

                    if ($table_name == $model_name) {
                        $found = true;
                        break;
                    }

                    unset($table);
                }
            }

            if (!$found) {
                $tmp = $pXml->createElement("table");
                $tmp->setAttribute('name', $model_name);
                $tmp->setAttribute('phpName', lcInflector::camelize($model_name));
                $tmp->setAttribute('idMethod', 'native');
                $db_q->item(0)->appendChild($tmp);
            }

            unset($model_name);
        }

        unset($tables_dom, $tables_xpath_search);

        // fix database_name - add it if missing / change it to the default
        // datasource name if there but different
        $database_name = isset($options['datasource']) && (string)$options['datasource'] ? $options['datasource'] : null;

        if ($database_name) {
            $element->setAttribute('name', $database_name);
        }

        // fix defaultTranslateMethod - according to the one specified in propel
        // YML config
        $translate_method = isset($options['translate_method']) && (string)$options['translate_method'] ? $options['translate_method'] : null;

        if ($translate_method) {
            $element->setAttribute('defaultTranslateMethod', $translate_method);
        } else {
            // fix the translation method of LC 1.3 which is no longer used
            if ($element->hasAttribute('defaultTranslateMethod')) {
                $attr = $element->getAttribute('defaultTranslateMethod');

                $str = 'I18nModelHelper';

                if (strstr($attr, $str)) {
                    $element->removeAttribute('defaultTranslateMethod');
                }
            }

            // propel validators translation
            // if no default translation method has been specified by the schema
            // add the default one - which will use our i18n internal objects
            if (!$element->hasAttribute('defaultTranslateMethod')) {
                $element->setAttribute('defaultTranslateMethod', '$this->translate');
            }
        }

        // fix package
        $package = isset($options['package']) && (string)$options['package'] ? $options['package'] : null;

        if ($package) {
            $element->setAttribute('package', $package);
        }

        $pXml->save($filename);
    }

    private function phingExecute($cmd, array $custom_properties = null, array $custom_args = null, $exit = false, $work_dir = null)
    {
        $request = $this->getRequest();

        /** @noinspection PhpUndefinedMethodInspection */
        $projectPath = $this->configuration->getRootDir() . DS . 'source' . DS . 'libs' . DS . 'database' . DS . 'propel';

        $wd = $work_dir ? $work_dir : (realpath($this->work_dir) . DS);

        /** @noinspection PhpUndefinedMethodInspection */
        $properties = [
            'propel.output.dir' => $this->configuration->getAppRootDir(),
            'propel.schema.xsd.file' => $projectPath . DS . 'resources/xsd/database.xsd',
            'propel.schema.xsl.file' => $projectPath . DS . 'resources/xsl/database.xsl',
            'propel.dbd2propel.xsl.file' => $projectPath . DS . 'resources/xsd/dbd2propel.xsl',
        ];

        // propel generator configuration
        $config_properties = (array)$this->configuration['db.propel'];

        // custom LC + propel generator config
        $custom_properties_config = (array)$this->configuration['db.propel_custom'];

        // merge all
        $properties = array_merge($properties, $custom_properties_config, $config_properties, (array)$custom_properties);

        // get database config
        $db_conf = $this->getPrimaryDatabaseConfig();
        $properties = array_merge($properties, $db_conf);

        lcDirs::mkdirRecursive($wd);

        // write the build properties file to temp location
        $build_properties_contents = [];

        foreach ($properties as $key => $value) {
            $build_properties_contents[] = $key . ' = ' . $value;

            unset($key, $value);
        }

        $build_properties_str = implode("\n", $build_properties_contents);

        $build_properties_filename = $wd . 'build.properties';

        /*if (DO_DEBUG) {
            echo print_r($build_properties_contents, true);
        }*/

        if (!@file_put_contents($build_properties_filename, $build_properties_str)) {
            throw new lcIOException('Could not copy the generated build.properties file: ' . $build_properties_filename);
        }

        unset($build_properties_contents, $build_properties_str, $build_properties_filename);

        unset($properties);

        $args = [];

        // add project arg
        $args[] = "-Dproject.dir=" . $wd;

        // custom arguments
        if (isset($custom_args)) {
            foreach ($custom_args as $key => $val) {
                $args[] = "$key=$val";

                unset($key, $val);
            }
        }

        /** @noinspection PhpUndefinedMethodInspection */
        if ($this->configuration->isDebugging()) {
            $args[] = '-debug';
            //$args[] = '-verbose';
        }

        //$args[] = '-listener';
        //$args[] = 'propel';

        //$args[] = '-logger';
        //$args[] = 'AnsiColorLogger';

        if ($request->getIsSilent()) {
            $args[] = '-logfile';
            /** @noinspection PhpUndefinedMethodInspection */
            $args[] = $this->configuration->getLogDir() . DS . 'propel-phing.log';
        }

        $args[] = '-f';
        /** @noinspection PhpUndefinedMethodInspection */
        $args[] = $this->configuration->getThirdPartyDir() . DS . 'propel' . DS . 'generator' . DS . 'build.xml';

        $args[] = $cmd;

        if ($this->configuration->isDebugging()) {
            echo print_r($args, true);
        }

        // set the database name manually
        if (!$db_conf['propel.project']) {
            throw new lcSystemException('You must set the datasource name in databases.yml (propel.project)');
        }

        try {
            $descr_ok = fopen('/dev/null', 'w');
            $descr_err = fopen('php://output', 'w');

            $stream_ok = new OutputStream($descr_ok);
            $stream_err = new OutputStream($descr_err);

            Phing::setOutputStream($stream_ok);
            PropelPhing::setOutputStream($stream_ok);

            Phing::setErrorStream($stream_err);
            PropelPhing::setErrorStream($stream_err);

            Phing::startup();
            PropelPhing::startup();

            Phing::setProperty('phing.home', getenv('PHING_HOME'));
            PropelPhing::setProperty('phing.home', getenv('PHING_HOME'));

            // catch and store errors internally
            Phing::startPhpErrorCapture();
            PropelPhing::startPhpErrorCapture();

            if (!PropelPhing::fire($args)) {
                throw new lcIOException($this->t('Phing command could not be executed'));
            }

            Phing::stopPhpErrorCapture();
            PropelPhing::stopPhpErrorCapture();

            Phing::shutdown();
            PropelPhing::shutdown();

            $stream_ok->close();
            $stream_err->close();

            @fclose($descr_ok);
            @fclose($descr_err);

            $captured_errors = PropelPhing::getCapturedPhpErrors();

            // return success
            $success = true;
            $exit_code = 0;
        } catch (Exception $e) {

            $this->phing_has_error = true;

            throw new lcSystemException('Phing command failed: ' . $e->getMessage() . ' (' . implode(' ', $args) . ')', $e->getCode(), $e);
        }

        if ($captured_errors) {
            $success = false;
            $exit_code = 2;

            $this->consoleDisplay(lcConsolePainter::formatConsoleText('Propel phing command has internal errors:', 'error') . "\n\n");

            foreach ($captured_errors as $error) {
                $message = isset($error['message']) ? $error['message'] : 'unknown error';
                //$level = isset($error['level']) ? $error['level'] : '-';
                $line = isset($error['line']) ? $error['line'] : '-';
                $file = isset($error['file']) ? $error['file'] : '-';

                $errmsg = '> ' . lcConsolePainter::formatColoredConsoleText('[PHP Error] ' . $message . '[line ' . $line . ' of ' . $file . ']', 'magenta');

                $this->consoleDisplay($errmsg, false);
                unset($error, $errmsg);
            }

            $this->consoleDisplay("\n\n", false);
        }

        unset($args, $db_conf);

        if ($exit) {
            exit($exit_code);
        } else {
            return $success;
        }
    }

    private function propelSql()
    {
        $this->propelInitSchemas();
        return $this->phingExecute('sql');
    }

    private function propelGraphviz()
    {
        $this->propelInitSchemas();
        return $this->phingExecute('graphviz');
    }

    private function propelCreateDb()
    {
        $this->propelInitSchemas();
        return $this->phingExecute('create-db');
    }

    private function propelInsertSql()
    {
        $this->propelInitSchemas();
        return $this->phingExecute('insert-sql');
    }

    private function propelBuildSql()
    {
        $this->propelInitSchemas();
        return $this->phingExecute('build-sql');
    }

    private function propelReverse($output_filename = null)
    {
        $this->propelInitSchemas();

        // default storage dir / filename
        $dir = $this->getRequest()->getParam('store-in') ? $this->getRequest()->getParam('store-in') : self::DEFAULT_REVERSE_TARGET_FOLDER;
        $filename = self::DEFAULT_REVERSE_TARGET_NAME . '-' . date('Y_m_d_H_i_s');

        /** @noinspection PhpUndefinedMethodInspection */
        $dir = ($dir[0] == '/') ? $dir : $this->configuration->getAppRootDir() . DS . $dir;

        if (isset($output_filename)) {
            $dir = dirname($output_filename);
            $filename = basename($output_filename);
        }

        assert((bool)$dir);
        assert((bool)$filename);

        // try to create the dir
        lcDirs::mkdirRecursive($dir);

        $properties = [
            'propel.reverse.parser.class' => self::PROPEL_REVERSE_PARSE_CLASS,
            'propel.default.schema.basename' => $filename,
            'propel.schema.dir' => $dir,
        ];

        return $this->phingExecute('reverse', $properties, null, null);
    }

    private function propelRebuild()
    {
        $fix_plugin_schemas = $this->getRequest()->getParam('with-plugins');

        if ($fix_plugin_schemas) {
            $fix_plugin_schemas = strlen($fix_plugin_schemas) > 1 ? array_filter(explode(',', $fix_plugin_schemas)) : true;
        } else {
            $fix_plugin_schemas = true;
        }

        $this->propelFlush();

        $wd = realpath($this->work_dir) . DS;

        // don't add .xml here - propel will!
        $reversedSchemaFile = $wd . 'reversed-schema';

        $this->propelReverse($reversedSchemaFile);

        $reversedSchemaFile .= '.xml';

        // find tables in each detected plugin
        $plugin_schemas = $this->getPluginSchemas();
        $plugin_all_tables = [];

        //$plugin_manager = $this->getPluginManager();

        if ($plugin_schemas) {
            $this->consoleDisplay('Working with plugin schemas...');

            foreach ($plugin_schemas as $plugin_name => $data) {
                $plugin_data = $data['plugin_data'];
                $cPath = $data['schema_filename'];

                try {
                    $pl_config = $this->getPluginConfig($plugin_name, $plugin_data['path']);

                    if (!$pl_config) {
                        throw new lcSystemException('Could not obtain plugin configuration');
                    }

                    $plugin_propel_schema = $this->getPluginPropelSchemaConfig($plugin_data['path']);

                    $plugin_tables = ($pl_config instanceof iSupportsDbModels) ? $pl_config->getDbModels() : null;
                    $plugin_tables = is_array($plugin_tables) && $plugin_tables ? $plugin_tables : [];

                    $plugin_views = ($pl_config instanceof iSupportsDbViews) ? $pl_config->getDbViews() : null;
                    $plugin_views = is_array($plugin_views) && $plugin_views ? $plugin_views : [];

                    if ($plugin_views) {
                        $plugin_propel_schema['views'] = $plugin_views;
                    }

                    $plugin_all_tables[$plugin_name] = $plugin_tables;

                    // fix plugin also
                    if ($fix_plugin_schemas && ((is_array($fix_plugin_schemas) && in_array($plugin_name, $fix_plugin_schemas)) || is_bool($fix_plugin_schemas))) {
                        $this->consoleDisplay('Fixing plugin schema (' . $plugin_name . '), tables: ' . implode(', ', $plugin_tables));

                        $temp_rebuilt_schema_filename = $this->work_dir . DS . 'plugin_' . $plugin_name . '-schema.xml';
                        $this->pluginSchemaCleanup($reversedSchemaFile, $plugin_name, $cPath, $temp_rebuilt_schema_filename, $plugin_tables, $plugin_propel_schema);
                    }
                } catch (Exception $e) {
                    $this->consoleDisplay('Plugin schema rebuild failed: ' . $e->getMessage());
                    assert(false);
                    continue;
                }

                unset($filename, $pl_config, $data, $cPath, $fdata, $cleanupXpath, $result, $length, $plugin_propel_schema);
            }
        }

        $main_plugin_schema = $this->getMainPropelSchemaConfig();

        $this->mainSchemaCleanup($reversedSchemaFile, $plugin_all_tables, $main_plugin_schema);

        // need to reinit the tmp folder with current schema files again
        $this->phingCleanUp();
        $this->createWorkingDirectory();

        $this->schemas_initialized = false;

        //$this->propelFlush();

        $this->propelOm();

        return true;
    }

    private function getPluginSchemas()
    {
        $schemas = [];

        $plugins = $this->system_component_factory->getSystemPluginDetails();

        if ($plugins) {
            foreach ($plugins as $plugin_name => $plugin_data) {

                $plconf = $this->getPluginConfig($plugin_name, $plugin_data['path']);

                if ($plconf && $plconf instanceof iSupportsDbModels) {
                    $schemas[$plugin_data['name']] = [
                        'plugin_data' => $plugin_data,
                        'schema_filename' => $plugin_data['path'] . DS . 'config' . DS . self::SCHEMA_FILE,
                    ];
                }

                unset($plugin_data, $plugin_name);
            }

            unset($plugins);
        }

        return $schemas;
    }

    private function getPluginPropelSchemaConfig($plugin_path)
    {
        $cfg_filename = $plugin_path . DS . 'config' . DS . 'propel.yml';

        if (!file_exists($cfg_filename)) {
            return null;
        }

        $parser = new lcYamlFileParser($cfg_filename);
        $data = $parser->parse();

        // for the moment - fetch the first available schema only
        if (!$data || !is_array($data) || !isset($data['schemas']) || !is_array($data['schemas']) || !$data['schemas']) {
            return null;
        }

        $data = $data['schemas'];
        $ak = array_keys($data);
        $data = $data[$ak[0]];

        /*if ($schema_name) {
            if (!isset($data['schemas'][$schema_name])) {
                return;
            }

            $data = $data['schemas'][$schema_name];
        }*/

        return $data;
    }

    private function pluginSchemaCleanup($reversed_schema_filename, $plugin_name, $plugin_schema_filename, $temp_plugin_schema_filename, array $plugin_tables, array $propel_config_schema = null)
    {
        $this->consoleDisplay('Rebuilding plugin schema: ' . $plugin_name);

        // remove all previous tables
        $pXml = new DOMDocument;
        $pXml->loadXML(file_get_contents($temp_plugin_schema_filename));
        $element = $pXml->documentElement;

        $xquery = new Domxpath($pXml);

        $result = $xquery->query('/database/table');
        $len = $result->length;

        for ($i = 0; $i < $len; $i++) {
            $element->removeChild($result->item($i));
        }

        $pXmlDatabaseNode = $pXml->getElementsByTagName('database')->item(0);
        /** @noinspection PhpUndefinedMethodInspection */
        $pXmlDatabaseNode->removeAttribute('name');

        // add plugin tables from main reversed schema
        if ($plugin_tables) {
            $pXmlMain = new DOMDocument;
            $pXmlMain->loadXML(file_get_contents($reversed_schema_filename));
            $mainSchemaXpath = new Domxpath($pXmlMain);

            $result2 = $mainSchemaXpath->query('/database/table');
            $len = $result2->length;

            for ($i = 0; $i < $len; $i++) {
                /** @var DomElement $rel */
                $rel = $result2->item($i);
                if (in_array($rel->getAttribute('name'), $plugin_tables)) {
                    // copy into plugin schema
                    $node = $pXml->importNode($result2->item($i), true);
                    $pXmlDatabaseNode->appendChild($node);
                }
            }
        }

        // save the plugin schema
        $pXml->formatOutput = true;
        $pXml->save($temp_plugin_schema_filename);

        $this->fixViews($temp_plugin_schema_filename, $propel_config_schema);
        $this->fixCopyValidatorsFromOriginalToReversedSchema($plugin_schema_filename, $temp_plugin_schema_filename);
        $this->fixCopyCustomAttributes($plugin_schema_filename, $temp_plugin_schema_filename);

        // copy back to the project schema path
        lcFiles::copy($temp_plugin_schema_filename, $plugin_schema_filename);
    }

    private function fixViews($schemaPath, array $propel_config_schema = null)
    {
        $this->consoleDisplay('Fixing VIEWs of schema: ' . $schemaPath);

        $mainSchema = new DOMDocument();

        if (!$mainSchema->load($schemaPath)) {
            throw new lcSystemException('Can`t load schema "' . $schemaPath . '"');
        }

        $database = $mainSchema->documentElement;
        $mainSchemaXpath = new Domxpath($mainSchema);

        //add package attribute to the database
        $database->setAttribute('package', 'models');

        //add primary key attribute to every view
        $result = $mainSchemaXpath->query('/database/table');
        $len = $result->length;

        $overriden_view_config = $propel_config_schema && isset($propel_config_schema['views']) ? $propel_config_schema['views'] : null;
        //$overriden_table_config = $propel_config_schema && isset($propel_config_schema['tables']) ? $propel_config_schema['tables'] : null;

        for ($i = 0; $i < $len; $i++) {
            /** @var DomElement $itm */
            $itm = $result->item($i);

            $tbl_name = $itm->getAttribute('name');
            $sub = substr($tbl_name, 0, 5);

            $is_view = ($overriden_view_config && is_array($overriden_view_config) && isset($overriden_view_config[$tbl_name]));

            //fix view's buggy primary keys - requirement by propel
            if ($is_view) {
                foreach ($itm->getElementsByTagName('column') as $col) {
                    /** @var DomElement $col */

                    $tbl_config = (isset($overriden_view_config[$tbl_name]['primary_keys']) ? $overriden_view_config[$tbl_name]['primary_keys'] : null);

                    if (!$tbl_config || !is_array($tbl_config)) {
                        continue;
                    }

                    if (in_array($col->getAttribute('name'), $tbl_config)) {
                        $col->setAttribute('primaryKey', 'true');
                    }

                    unset($col, $tbl_config);
                }
            }

            //begin fix propel foreign key bug
            $aForeignKeys = [];
            $oForeignKeys = $itm->getElementsByTagName('foreign-key');
            // all foreign keys as dom elements selection
            $sForeignKeysLen = $oForeignKeys->length;

            for ($j = 0; $j < $sForeignKeysLen; $j++) {
                /** @var DomElement $itmj */
                $itmj = $oForeignKeys->item($j);
                /** @var DomElement $oForeignKeyReference */
                $oForeignKeyReference = $itmj->getElementsByTagName('reference')->item(0);
                /** @var DomElement $foritem */
                $foritem = $oForeignKeys->item($j);
                $aForeignKeys[$oForeignKeyReference->getAttribute('local')][] = $foritem->getAttribute('name');
            }

            if (!empty($aForeignKeys)) {
                //this table has foreign keys, we should check if there isn't a
                // but anywhere
                foreach ($aForeignKeys as $reference_local_field => $foreign_key_names) {
                    if (count($foreign_key_names) > 1) {
                        //we have a propel bug, should fix it
                        $foreign_key_name_to_remove = $foreign_key_names[0];
                        for ($j = 0; $j < $sForeignKeysLen; $j++) {
                            /** @var DomElement $ofork */
                            $ofork = $oForeignKeys->item($j);
                            if ($ofork->getAttribute('name') == $foreign_key_name_to_remove) {
                                //remove
                                $result->item($i)->removeChild($oForeignKeys->item($j));
                                break;
                            }
                        }
                        unset($foreign_key_name_to_remove);
                    }

                    unset($reference_local_field, $foreign_key_names);
                }
            }

            unset($tbl_name, $sub, $aForeignKeys, $sForeignKeysLen);
            //end fix propel foreign key bug
        }

        $mainSchema->save($schemaPath);
    }

    private function fixCopyValidatorsFromOriginalToReversedSchema($original_schema_path, $reversed_schema_path)
    {
        $this->consoleDisplay('Copying back custom validators from original schema: ' . $original_schema_path);

        // original schema missing
        if (!file_exists($original_schema_path)) {
            return;
        }

        // load the original
        $original_schema = new DOMDocument();

        if (!$original_schema->load($original_schema_path)) {
            throw new lcSystemException('Can`t load original schema "' . $original_schema_path . '"');
        }

        // load the reversed
        $reversed_schema = new DOMDocument();
        $reversed_schema->formatOutput = true;

        if (!$reversed_schema->load($reversed_schema_path)) {
            throw new lcSystemException('Can`t load reversed schema "' . $reversed_schema_path . '"');
        }

        $original_schema_xpath = new DOMXPath($original_schema);
        $result = $original_schema_xpath->query('/database/table/validator');

        $total = $result->length;

        if (!$total) {
            return;
        }

        $this->consoleDisplay('Found ' . $total . ' validators which need to be copied to the reversed schema');

        foreach ($result as $dom_elem) {
            /** @var DomElement $dom_elem */
            $table_name = $dom_elem->parentNode->getAttribute('name');
            $validator_column = $dom_elem->getAttribute('column');

            $this->consoleDisplay('Copying validator: ' . $table_name . ' :: ' . $validator_column);

            // copy the validator to the reversed schema
            //

            // find the table in the reversed schema
            $res_rev = new DOMXPath($reversed_schema);
            $result2 = $res_rev->query('/database/table[@name=\'' . $table_name . '\']');

            if (!$result2->length) {
                throw new lcSystemException('Could not find the table \'' . $table_name . '\' in the reversed schema to apply missing validator for column: ' . $validator_column);
            }

            $new_node = $result2->item(0);

            $node_in = $reversed_schema->importNode($dom_elem, true);
            $new_node->appendChild($node_in);

            unset($dom_elem, $table_name, $node_in, $validator_column, $res_rev, $result2, $new_node);
        }

        $reversed_schema->save($reversed_schema_path);
    }

    private function fixCopyCustomAttributes($original_schema_path, $reversed_schema_path)
    {
        $this->consoleDisplay('Copying back custom attributes from original schema: ' . $original_schema_path);

        // original schema missing
        if (!file_exists($original_schema_path)) {
            return;
        }

        // load the original
        $original_schema = new DOMDocument();

        if (!$original_schema->load($original_schema_path)) {
            throw new lcSystemException('Can`t load original schema "' . $original_schema_path . '"');
        }

        // load the reversed
        $reversed_schema = new DOMDocument();
        $reversed_schema->formatOutput = true;

        if (!$reversed_schema->load($reversed_schema_path)) {
            throw new lcSystemException('Can`t load reversed schema "' . $reversed_schema_path . '"');
        }

        $original_schema_xpath = new DOMXPath($original_schema);
        $result = $original_schema_xpath->query('/database/table');

        $total = $result->length;

        if (!$total) {
            return;
        }

        $this->consoleDisplay('Found ' . $total . ' tables');

        foreach ($result as $dom_elem) {
            /** @var DomElement $dom_elem */
            $table_name = $dom_elem->getAttribute('name');
            $lc_table_title = $dom_elem->getAttribute(lcBasePeer::ATTR_TABLE_LC_TITLE);

            if (!$table_name || !$lc_table_title) {
                continue;
            }

            $this->consoleDisplay('Table: ' . $table_name);

            // copy the table lcBasePeer::ATTR_TABLE_LC_TITLE attribute:

            $this->consoleDisplay('Copying table custom attribute: ' . lcBasePeer::ATTR_TABLE_LC_TITLE . ':' . $lc_table_title);

            // copy to reversed schema
            //

            // find the table in the reversed schema
            $res_rev = new DOMXPath($reversed_schema);
            $result2 = $res_rev->query('/database/table[@name=\'' . $table_name . '\']');

            if (!$result2->length) {
                $this->consoleDisplay('Table not found in reverse schema - skipping...');
                continue;
            }

            /** @var DomElement $new_node */
            $new_node = $result2->item(0);
            $new_node->setAttribute(lcBasePeer::ATTR_TABLE_LC_TITLE, $lc_table_title);

            unset($result2, $res_rev, $new_node);

            // copy the columns lcBasePeer::ATTR_TABLE_LC_TITLE attribute:
            $col_result = $original_schema_xpath->query('/database/table[@name=\'' . $table_name . '\']/column');

            if ($col_result->length) {
                foreach ($col_result as $col_dom_elem) {
                    /** @var DomElement $col_dom_elem */
                    $column_name = $col_dom_elem->getAttribute('name');
                    $lc_col_title = $col_dom_elem->getAttribute(lcBasePeer::ATTR_TABLE_LC_TITLE);

                    if ($column_name && $lc_table_title) {
                        $res_rev = new DOMXPath($reversed_schema);
                        $result2 = $res_rev->query('/database/table[@name=\'' . $table_name . '\']/column[@name=\'' . $column_name . '\']');

                        if ($result2->length) {
                            $this->consoleDisplay('Copying table column (' . $column_name . ') custom attribute: ' . lcBasePeer::ATTR_TABLE_LC_TITLE . ':' . $lc_col_title);

                            $new_node = $result2->item(0);
                            $new_node->setAttribute(lcBasePeer::ATTR_TABLE_LC_TITLE, $lc_col_title);
                        }

                        unset($result2, $res_rev, $new_node);
                    }

                    unset($col_dom_elem, $column_name);
                }
            }

            unset($col_result);

            unset($dom_elem, $table_name);
        }

        $reversed_schema->save($reversed_schema_path);
    }

    private function getMainPropelSchemaConfig()
    {
        $cfg_filename = $this->configuration->getBaseConfigDir() . DS . 'propel.yml';

        $data = [];

        if (file_exists($cfg_filename)) {
            $parser = new lcYamlFileParser($cfg_filename);
            $data = $parser->parse();

            // for the moment - fetch the first available schema only
            if (!$data || !is_array($data) || !isset($data['schemas']) || !is_array($data['schemas']) || !$data['schemas']) {
                return null;
            }

            $data = $data['schemas'];
            $ak = array_keys($data);
            $data = $data[$ak[0]];
        }


        /** @noinspection PhpUndefinedMethodInspection */
        $cfg = $this->configuration->getProjectConfiguration();
        $schema_views = ($cfg instanceof iSupportsDbViews) ? $cfg->getDbViews() : null;
        $schema_views = is_array($schema_views) && $schema_views ? $schema_views : [];

        if ($schema_views) {
            $data['views'] = $schema_views;
        }

        /*if ($schema_name) {
            if (!isset($data['schemas'][$schema_name])) {
                return;
            }

            $data = $data['schemas'][$schema_name];
        }*/

        return $data;
    }

    private function mainSchemaCleanup($reversed_schema_filename, array $plugin_tables, array $propel_config_schema = null)
    {
        $this->consoleDisplay('Fixing main schema...');

        // remove plugin tables from main schema
        if ($plugin_tables) {
            $pXml = new DOMDocument;
            $pXml->loadXML(file_get_contents($reversed_schema_filename));
            $element = $pXml->documentElement;

            $mainSchemaXpath = new Domxpath($pXml);

            $result = $mainSchemaXpath->query('/database/table');
            $len = $result->length;

            for ($i = 0; $i < $len; $i++) {
                foreach ($plugin_tables as $plugin_name => $tables) {
                    /** @var DomElement $ritm */
                    $ritm = $result->item($i);
                    if (in_array($ritm->getAttribute('name'), $tables)) {
                        $element->removeChild($result->item($i));
                    }

                    unset($plugin_name, $tables);
                }

                unset($plugin);
            }

            $pXml->formatOutput = true;
            $pXml->save($reversed_schema_filename);
        }

        $original_schema_path = $this->getProjectSchema();

        $this->fixViews($reversed_schema_filename, $propel_config_schema);
        $this->fixCopyValidatorsFromOriginalToReversedSchema($original_schema_path, $reversed_schema_filename);
        $this->fixCopyCustomAttributes($original_schema_path, $reversed_schema_filename);

        // copy back to the project schema path
        lcFiles::copy($reversed_schema_filename, $original_schema_path);
    }

    private function getProjectSchema()
    {
        return $this->configuration->getConfigDir() . DS . self::SCHEMA_FILE;
    }

    private function propelMigrate()
    {
        throw new lcSystemException('Unimplemented');

        /*$this->propelInitSchemas();
        return $this->phingExecute('migrate');*/
    }

    private function displayHelp()
    {
        $this->consoleDisplay($this->getHelpInfo(), false);

        return true;
    }

    public function getHelpInfo()
    {
        $help_info = <<<EOD
Possible commands:

- models - builds/rebuilds all models schemas into objects

	OPTIONS:
	--only-entities=[plugin_names, \'project\'] - process only specific plugins or \'project\' - for the project models

- sql - generates the sql code of the project. Generated files will be stored in data/sql

- graphviz - generates a Graphviz image of the database project. Generated files will be stored in data/graphviz

- create-db - creates the actual database

- insert-sql - builds and inserts the project\'s sql data into the database

- build-sql - builds the project\'s sql data 

- reverse - creates a SCHEMA file from a live database

	OPTIONS:
	--store-in=[folder_name] - the folder where to store the reversed schema. Default: data/reversed-schema.xml

- flush - removes all files generated by propel (except user-classes)

if any of the options is skipped - the current website\'s ones gets used.
EOD;
        return $help_info;
    }

}
