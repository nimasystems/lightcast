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

class tLightcastUpgrader extends lcTaskController
{
    private $upgradeable_versions = array(
        '1.4' => 'upgradeFromLC14'
    );

    private $tpl_dir;

    private $project_dir;
    private $project_ver;
    private $available_apps;
    private $available_plugins;

    public function getHelpInfo()
    {
        return
            'Possible commands:' . "\n\n" .
            'Project Upgrades:' . "\n\n" .
            lcConsolePainter::formatConsoleText('upgrade-project', 'info') . ' - Upgrades the project for usage by the latest lightcast framework (' . LC_VER . ') ' . "\n" .
            "\t- project_dir - specify the directory of the project (required)\n" .
            "\t- project_ver - force a version (skip autodetection)\n" .
            "\n";
    }

    public function executeTask()
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'upgrade-project':
                return $this->upgradeProject();
            default:
                return $this->displayHelp();
        }
    }

    private function displayHelp()
    {
        $this->consoleDisplay($this->getHelpInfo(), false);
        return true;
    }

    private function upgradeProject()
    {
        $r = $this->request;
        $project_dir = $r->getParam('project_dir');
        $project_ver = $r->getParam('project_ver');

        if (!$project_dir) {
            throw new lcInvalidArgumentException('The project directory must be specified');
        }

        $project_actual_dir = null;
        $detected_version = null;
        $is_detected = $this->detectProjectVersion($project_dir, $project_ver, $project_actual_dir, $detected_version);

        if (!$is_detected) {
            $this->consoleDisplay('Could not detect an upgradable lightcast project at the specified directory');
            return false;
        }

        // found it
        $this->display('Detected a Lightcast project with version (' . $detected_version . ') at path: ' . $project_actual_dir);

        if (!isset($this->upgradeable_versions[$detected_version])) {
            $this->displayError('The project cannot be upgraded - unsupported version');
            return false;
        }

        // confirm
        /*if (!$this->confirm('Please backup the project before proceeding! Confirm the operation (LC ' . $detected_version . ' > ' . LC_VER . ') by typing UPGRADE:', 'UPGRADE'))
        {
        $this->displayWarning('Operation has been cancelled');
        return false;
        }*/

        $this->consoleDisplay('Upgrading the project from version ' . $detected_version . ' to version: ' . LC_VER);

        $this->project_dir = $project_actual_dir;
        $this->project_ver = $detected_version;

        $ret = $this->processUpgrade();

        return $ret;
    }

    private function matchInFile($regex, $filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $content = @file_get_contents($filename);

        if (!$content) {
            return false;
        }

        $matches = array();
        $matched = preg_match_all($regex, $content, $matches);

        if (!$matched) {
            return false;
        }

        return $matches;
    }

    private function replaceInFile($regex, $replacement, $filename, $test = false)
    {
        $f = file_get_contents($filename);

        if (!$f) {
            return false;
        }

        $f = str_replace("\n", ' --\n-- ', $f);
        $f = str_replace("\r", ' --\r-- ', $f);

        $f = preg_replace($regex, $replacement, $f);

        $f = str_replace(' --\n-- ', "\n", $f);
        $f = str_replace(' --\r-- ', "\r", $f);

        if ($test) {
            return $f;
        }

        $ret = file_put_contents($filename, $f);

        return $ret;
    }

    private function detectProjectVersion($project_dir, $fallback_version, &$project_actual_dir, &$detected_version)
    {
        $detected_version = null;
        $project_actual_dir = realpath($project_dir);

        if (!$project_actual_dir) {
            $project_actual_dir = $project_dir;
        }

        $project_boot_file = $project_actual_dir . DS . 'lib' . DS . 'boot.php';

        // LC 1.4 - look for lib/boot.php and see if there is a constant APP_VER within it
        $matches = $this->matchInFile("/define\(['\"]APP_VER['\"][,\s]+['\"]([\d.]+?)['\"]\)/i", $project_boot_file);

        if (!$matches) {
            // use a forced version
            if ($fallback_version && is_file($project_boot_file)) {
                $detected_version = $fallback_version;
                return true;
            }

            return false;
        }

        $detected_version = $matches[1][0];

        return true;
    }

    private function processUpgrade()
    {
        $this->tpl_dir = $this->configuration->getRootDir() . DS . 'source' . DS . 'assets' . DS . 'templates' . DS . 'default';
        $this->available_apps = $this->getAvailableApplications($this->project_dir . DS . 'applications');
        $this->available_plugins = $this->getAvailablePlugins($this->project_dir . DS . 'addons' . DS . 'plugins');

        // upgrade differences from the latest template
        $this->updateTemplateDifferences();

        // move all plugin / application / config files into a default config folder (lc 1.5)
        $this->moveConfigFiles();

        // fix yaml syntax with yaml extension
        $this->fixYmls();

        // run custom upgrades for the target only
        $method = $this->upgradeable_versions[$this->project_ver];

        $ret = $this->$method();

        $this->upgradeEnd($ret);

        return $ret;
    }

    private function rm($files)
    {
        $f = is_array($files) ? $files : array($files);

        if (!$f) {
            return false;
        }

        foreach ($f as $ff) {
            $ff = $this->project_dir . DS . $ff;

            if (file_exists($ff) && is_file($ff)) {
                $this->display('D (f) ' . $ff);
                unlink($ff);
            }

            unset($ff);
        }

        return true;
    }

    private function cpt($files)
    {
        $files = is_array($files) ? $files : array($files);

        foreach ($files as $filename => $options) {
            $filename = is_array($options) ? $filename : $options;
            $options = is_array($options) ? $options : array();

            // possible options:
            // overwrite = true/false
            // destination - pick a different filename for the destination
            // recursive_dir_copy - copy an entire dir tree

            $spath = $this->tpl_dir . DS . $filename;

            if (!file_exists($spath) || !is_readable($spath)) {
                throw new lcIOException('Source filename could not be read: ' . $spath);
            }

            $should_overwrite = isset($options['overwrite']) && (bool)$options['overwrite'] ? true : false;
            $destination_filename = isset($options['destination']) && $options['destination'] ? (string)$options['destination'] : $filename;
            $recursive_dir_copy = isset($options['recursive_dir_copy']) && $options['recursive_dir_copy'] ? true : false;
            $chmod = isset($options['chmod']) && $options['chmod'] ? $options['chmod'] : null;

            // check source dir
            $source_is_dir = is_dir($spath);

            assert(($recursive_dir_copy && $source_is_dir) || !$source_is_dir);

            $fpath = $this->project_dir . DS . $destination_filename;

            // remove the previous one
            $file_created = true;

            if (file_exists($fpath)) {
                $file_created = false;

                if ($should_overwrite) {
                    if ($source_is_dir) {
                        lcDirs::rmdirRecursive($fpath);
                    } else {
                        unlink($fpath);
                    }
                }
            }

            if (!file_exists($fpath)) {
                // try to create the file's dir
                lcDirs::mkdirRecursive(dirname($fpath));

                // copy the file
                $this->display(($file_created ? 'A' : 'M') . ' (' . ($source_is_dir ? 'd' : 'f') . ') ' . $fpath);

                lcFiles::copy($spath, $fpath);
            }

            if ($chmod) {
                chmod($fpath, $chmod);
            }

            unset($fpath, $options, $filename, $should_overwrite, $destination_filename, $recursive_dir_copy, $source_is_dir, $file_created);
        }
    }

    private function mkd($dirs)
    {
        $d = is_array($dirs) ? $dirs : array($dirs);

        if (!$d) {
            return false;
        }

        foreach ($d as $dir) {
            $dir = $this->project_dir . DS . $dir;

            if (!is_dir($dir)) {
                $this->display('A (d) ' . $dir);
                lcDirs::mkdirRecursive($dir);
            }

            unset($dir);
        }

        return true;
    }

    private function updateTemplateDifferences()
    {
        // create dirs if missing
        $this->mkd(
            array(
                'addons/extensions',
                'addons/plugins',
                'applications',
                'config/default/applications',
                'config/default/plugins',
                'data',
                'lib/custom_errors/img',
                'models',
                'sandbox',
                'shell',
                'tmp/logs',
                'tmp/temp',
                'tmp/cache'
            )
        );

        // copy / replace missing files
        $this->cpt(
            array(
                '.htaccess',
                'config/boot_config.default.php' => array(
                    'overwrite' => true,
                    'destination' => 'config/boot_config.php'
                ),
                'config/preboot_config.default.php' => array(
                    'overwrite' => true,
                    'destination' => 'config/preboot_config.php'
                ),
                'config/api_configuration.php',
                'config/project_configuration.php',
                'data/.htaccess',
                'lib/custom_errors' => array(
                    'recursive_dir_copy' => true
                ),
                'lib/.htaccess',
                'lib/boot.php' => array(
                    'overwrite' => true
                ),
                'shell/cmd' => array(
                    'overwrite' => true,
                    'chmod' => 777
                ),
                'shell/cmd_debug' => array(
                    'overwrite' => true,
                    'chmod' => 777
                ),
                'shell/console.bat' => array(
                    'overwrite' => true,
                    'chmod' => 777
                ),
                'webroot/.htaccess',
                'webroot/robots.txt',
                'LICENSE' => array(
                    'overwrite' => true
                ),
                'README' => array(
                    'overwrite' => true
                ),
            )
        );

        // remove obsolete files
        $this->rm(
            array(
                'lib/debug_include.php',
                'shell/console',
                'shell/console_debug',
            )
        );

        return true;
    }

    private function fixYmls()
    {
        $this->consoleDisplay('Fixing YAML syntax');

        // reads / rewrites ymls to fix errorous syntax caused by bad extensions (syck)
        if (!extension_loaded('yaml')) {
            $this->displayWarning('PHP extension \'yaml\' is not loaded - omitting YAML fixing');
            return;
        }

        // find yamls
        $found = lcFiles::globRecursive($this->project_dir, '*.yml');

        if (!$found) {
            return;
        }

        foreach ($found as $file) {
            $parser = new lcYamlFileParser($file);
            $data = $parser->parse();
            $parser->writeData($data);
            unset($file, $parser, $data);
        }
    }

    private function moveConfigFiles()
    {
        // move all plugin configs
        // move all app configs
        // move all project configs which are not under a default config folder

        $project_dir = $this->project_dir;
        $default_cfg_dir = $project_dir . DS . 'config/default';

        $this->consoleDisplay('Moving config files to the new default location: ' . $default_cfg_dir);

        // make the dirs
        lcDirs::mkdirRecursive($default_cfg_dir);
        lcDirs::mkdirRecursive($default_cfg_dir . DS . 'applications');
        lcDirs::mkdirRecursive($default_cfg_dir . DS . 'plugins');

        // move all orphan config files
        lcFiles::globMove($project_dir . DS . 'config/*.{yml,xml,ini}', $default_cfg_dir);

        // move all app config files
        $available_apps = $this->available_apps;

        if ($available_apps) {
            foreach ($available_apps as $app) {
                lcFiles::globMove(
                    $project_dir . DS . 'applications' . DS . $app . DS . 'config' . DS . '*.{yml,xml,ini}',
                    $default_cfg_dir . DS . 'applications' . DS . $app);

                unset($app);
            }
        }

        // move all plugin config files
        $available_plugins = $this->available_plugins;

        if ($available_plugins) {
            foreach ($available_plugins as $plugin) {
                lcFiles::globMove(
                    $project_dir . DS . 'addons' . DS . 'plugins' . DS . $plugin . DS . 'config' . DS . '*.{yml,ini}',
                    $default_cfg_dir . DS . 'plugins' . DS . $plugin);

                unset($plugin);
            }
        }
    }

    private function upgradeEnd($successful)
    {
        if ($successful) {
            $this->display(
                'Success! Please remember to manually:' . "\n\n" .
                "\t- clear all caches" . "\n" .
                "\t- set the proper project / app / web service names in config/*.php files" . "\n" .
                "\t- upgrade your web server boot files (webroot/*.php)" . "\n" .
                "\t- upgrade your plugins to their latest versions" . "\n" .
                "\t- rebuild the Propel models" .
                "\n"
            );
        } else {
            $this->displayError('Operation was not successful!');
        }
    }

    private function getAvailableApplications($project_dir)
    {
        $ret = lcDirs::getSubDirsOfDir($project_dir);
        return $ret;
    }

    private function getAvailablePlugins($plugins_dir)
    {
        $ret = lcDirs::getSubDirsOfDir($plugins_dir);
        return $ret;
    }

    private function getApplicationModules($app_dir)
    {
        $ret = lcDirs::getSubDirsOfDir($app_dir . DS . 'modules');
        return $ret;
    }

    /*private function getProjectTasks($project_dir)
    {
        $ret = glob($project_dir . DS . 'tasks' . DS . '*.php');
        return $ret;
    }

    private function getProjectWebServices($project_dir)
    {
        $ret = glob($project_dir . DS . 'ws' . DS . '*.php');
        return $ret;
    }

    private function getPluginControllers($plugin_dir)
    {
        $web_modules = (array)lcDirs::getSubDirsOfDir($plugin_dir . DS . 'modules');
        $web_services = (array)lcDirs::getSubDirsOfDir($plugin_dir . DS . 'web_services');
        $console_tasks = (array)lcDirs::getSubDirsOfDir($plugin_dir . DS . 'tasks');

        $ret = array(
            'web_modules' => $web_modules,
            'web_services' => $web_services,
            'console_tasks' => $console_tasks
        );
        return $ret;
    }*/

    private function _getFixProtectedSysObjClassVarsRegex()
    {
        static $regex;

        if ($regex) {
            return $regex;
        }

        $vars = array(
            'translation_context_type',
            'translation_context_name',
            'parent_plugin',
            'context_type',
            'context_name',
            'logger',
            'i18n',
            'class_autoloader',
            'event_dispatcher',
            'configuration'
        );

        $regex = array();
        $reps = array();

        foreach ($vars as $var) {
            $regex[] = "/(private|public)[\s]+\\$" . $var . "\s*;/i";
            $reps[] = 'protected $' . $var . ';';

            unset($var);
        }

        $regex = array(
            'regex' => $regex,
            'reps' => $reps
        );

        return $regex;
    }

    private function _getFixProtectedAppObjClassVarsRegex()
    {
        static $regex;

        if ($regex) {
            return $regex;
        }

        $vars = array(
            'request',
            'response',
            'routing',
            'database_manager',
            'storage',
            'user',
            'data_storage',
            'cache',
            'mailer',
            'dbc'
        );

        $regex = array();
        $reps = array();

        foreach ($vars as $var) {
            $regex[] = "/(private|public)[\s]+\\$" . $var . "\s*;/i";
            $reps[] = 'protected $' . $var . ';';

            unset($var);
        }

        $regex = array(
            'regex' => $regex,
            'reps' => $reps
        );

        return $regex;
    }

    private function _lc14FixMasterConfigurations($project_dir, array $available_apps)
    {
        // 1. fix configuration issues
        $this->display('Fixing master config boot files');

        // remove template configs if 'lc' prefixed versions exist
        // rename 'lc' prefixed versions to non-lc prefixed files
        $cfg_files = array(
            'config/lcApiConfiguration.class.php' => array(
                'new_class' => 'ApiConfiguration',
                'old_class' => 'lcApiConfiguration',
                'new_filename' => 'api_configuration.php'
            ),
            'config/lcProjectConfiguration.class.php' => array(
                'new_class' => 'ProjectConfiguration',
                'old_class' => 'lcProjectConfiguration',
                'new_filename' => 'project_configuration.php'
            ),
        );

        if ($available_apps) {
            foreach ($available_apps as $app) {
                $app_cam = lcInflector::camelize($app, false);
                $f = 'applications' . DS . $app . DS . 'config' . DS . 'lc' . $app_cam . 'Configuration.class.php';
                $cfg_files[$f] = array(
                    'new_class' => $app_cam . 'Configuration',
                    'old_class' => 'lc' . $app_cam . 'Configuration',
                    'new_filename' => $app . '_configuration.php',
                    'is_app' => true
                );
                unset($app, $f);
            }
        }

        foreach ($cfg_files as $source => $options) {
            $new_filename = $options['new_filename'];
            $new_class = isset($options['new_class']) ? $options['new_class'] : null;
            $old_class = isset($options['old_class']) ? $options['old_class'] : null;
            $is_app = isset($options['is_app']) ? (bool)$options['is_app'] : false;

            $nf = $project_dir . DS . dirname($source) . DS . $new_filename;

            if (file_exists($project_dir . DS . $source)) {
                // remove the template version and just leave the original
                // but rename it to the template filename

                if (file_exists($nf)) {
                    unlink($nf);
                }

                rename($project_dir . DS . $source, $nf);
            }

            if ($old_class && $new_class) {
                // fix the class parents
                // from 1.5 applications should inherit from lcWebConfiguration (previously from: lcApplicationConfiguration)
                if ($is_app) {
                    $this->replaceInFile(
                        "/class[\s]+" . $old_class . "[\s]+extends[\s]+lcApplicationConfiguration/i",
                        'class ' . $new_class . ' extends lcWebConfiguration',
                        $nf);
                } else {
                    // rename class
                    $this->replaceInFile(
                        "/class[\s]+[\w]+[\s]+extends[\s]+([\w]+?)Configuration/i",
                        'class ' . $new_class . ' extends \\1Configuration',
                        $nf);
                }
            }

            unset($source, $options, $new_filename, $new_class, $old_class, $is_app, $nf);
        }

        unset($cfg_files);
    }

    private function getModulesForTree($app_dir)
    {
        if (!$app_dir) {
            return null;
        }

        $ret = array();

        $app_modules = $this->getApplicationModules($app_dir);

        if ($app_modules) {
            foreach ($app_modules as $module) {
                $ret[] = array(
                    'name' => $module,
                    'parent' => 'lcAppObj',
                    'class' => 'c' . lcInflector::camelize($module, false),
                    'filename' => $app_dir . DS . 'modules' . DS . $module . DS . $module . '.php'
                );
                unset($module);
            }
        }

        return $ret;
    }

    private function getWebServicesInDir($dir)
    {
        $tasks = glob($dir . DS . '*.php');

        if (!$tasks) {
            return null;
        }

        $ret = array();

        foreach ($tasks as $task_filename) {
            $b = basename($task_filename);
            $b = $b ? lcFiles::splitFileName($b) : null;
            $b = $b ? $b['name'] : null;

            if (!$b) {
                continue;
            }

            $ret[] = array(
                'name' => $b,
                'parent' => 'lcAppObj',
                'class' => 'ws' . lcInflector::camelize($b, false),
                'filename' => $task_filename
            );

            unset($task_filename, $b);
        }

        return $ret;
    }

    private function getConsoleTasksInDir($dir)
    {
        $tasks = glob($dir . DS . '*.php');

        if (!$tasks) {
            return null;
        }

        $ret = array();

        foreach ($tasks as $task_filename) {
            $b = basename($task_filename);
            $b = $b ? lcFiles::splitFileName($b) : null;
            $b = $b ? $b['name'] : null;

            if (!$b) {
                continue;
            }

            $ret[] = array(
                'name' => $b,
                'parent' => 'lcAppObj',
                'class' => 't' . lcInflector::camelize($b, false),
                'filename' => $task_filename
            );

            unset($task_filename, $b);
        }

        return $ret;
    }

    private function getProjectTree($walk_callback = null)
    {
        $this->display('Fixing wrong visibility scope of system object vars (lcSysObj / lcAppObj)');

        $project_dir = $this->project_dir;
        $available_apps = $this->available_apps;
        $available_plugins = $this->available_plugins;

        $tree = array(
            'config' => array(),
            'applications' => array(),
            'tasks' => array(),
            'web_services' => array(),
            'plugins' => array()
        );

        // get applications web module protected vars
        if ($available_apps) {
            foreach ($available_apps as $app) {
                $app_dir = $project_dir . DS . 'applications' . DS . $app;

                // app config
                $el = array(
                    'class' => lcInflector::camelize($app . '_configuration', false),
                    'parent' => 'lcSysObj',
                    'filename' => $app_dir . DS . 'config' . DS . $app . '_configuration.php'
                );
                $tree[$app]['config'][] = $el;

                if ($walk_callback) {
                    $this->$walk_callback('config', $el);
                }

                $tree[$app]['modules'] = (array)$this->getModulesForTree($app_dir);

                if ($walk_callback) {
                    foreach ($tree[$app]['modules'] as $module) {
                        $this->$walk_callback('module', $module);
                        unset($module);
                    }
                }

                unset($app, $app_dir);
            }
        }

        // get project tasks protected vars
        $tree['tasks'] = (array)$this->getConsoleTasksInDir($project_dir . DS . 'tasks');

        if ($walk_callback) {
            foreach ($tree['tasks'] as $task) {
                $this->$walk_callback('task', $task);
                unset($task);
            }
        }

        // get project web service protected vars
        $tree['web_services'] = (array)$this->getWebServicesInDir($project_dir . DS . 'ws');

        if ($walk_callback) {
            foreach ($tree['web_services'] as $web_service) {
                $this->$walk_callback('web_service', $web_service);
                unset($web_service);
            }
        }

        // get project configurations protected vars
        $el = array(
            'class' => 'ProjectConfiguration',
            'parent' => 'lcSysObj',
            'filename' => $project_dir . DS . 'config' . DS . 'project_configuration.php'
        );
        $tree['config'][] = $el;

        if ($walk_callback) {
            $this->$walk_callback('config', $el);
        }

        $el = array(
            'class' => 'ApiConfiguration',
            'parent' => 'lcSysObj',
            'filename' => $project_dir . DS . 'config' . DS . 'api_configuration.php'
        );
        $tree['config'][] = $el;

        if ($walk_callback) {
            $this->$walk_callback('config', $el);
        }

        // get plugins protected vars
        if ($available_plugins) {
            foreach ($available_plugins as $plugin) {
                $plugin_dir = $project_dir . DS . 'addons' . DS . 'plugins' . DS . $plugin;

                $pl = array(
                    'class' => 'p' . lcInflector::camelize($plugin, false),
                    'parent' => 'lcAppObj',
                    'filename' => $plugin_dir . DS . $plugin . '.php'
                );

                if ($walk_callback) {
                    $this->$walk_callback('plugin', $pl);
                }

                // plugin modules
                $pl['modules'] = (array)$this->getModulesForTree($plugin_dir);

                if ($walk_callback) {
                    foreach ($pl['modules'] as $module) {
                        $this->$walk_callback('module', $module);
                        unset($module);
                    }
                }

                // plugin console tasks
                $pl['tasks'] = (array)$this->getConsoleTasksInDir($plugin_dir . DS . 'tasks');

                if ($walk_callback) {
                    foreach ($pl['tasks'] as $task) {
                        $this->$walk_callback('task', $task);
                        unset($task);
                    }
                }

                // plugin web services
                $pl['web_services'] = (array)$this->getWebServicesInDir($plugin_dir . DS . 'ws');

                if ($walk_callback) {
                    foreach ($pl['web_services'] as $web_service) {
                        $this->$walk_callback('web_service', $web_service);
                        unset($web_service);
                    }
                }

                $tree['plugins'][] = $pl;

                unset($plugin);
            }
        }

        return $tree;
    }

    private function fixAddRequiredComponents($item_type, array $details)
    {
        $filename = $details['filename'];
        $class = $details['class'];
        $c = file_get_contents($filename);

        if (!$c) {
            return false;
        }

        // parse and find out the previous plugins
        //
        $used_components = array();

        /*
         $matches = array();
        $matched = preg_match("/" . 'protected.*\$use_components[^=]+=[^a]+array\((.+?)\)' . "/smi", $c, $matches);
        $matches_str = $matched ? $matches[1] : null;
        $matches_str = $matches_str ? lcStrings::toAlphaNum($matches_str, array(',')) : null;
        $used_components = $matches_str ? array_unique(array_filter(explode(',', $matches_str))) : array();
        */

        // parse the file to find other used plugins and add them
        $matches = $this->matchInFile("/->initComponent\(['\"]([\w\d]+?)['\"]\)/i", $filename);

        if ($matches) {
            $used_components = array_unique(array_merge(
                $used_components,
                (array)$matches[1]
            ));
        }

        // remove the previous declaration
        $c = preg_replace("/protected.*" . '\$use_components[^;]+;' . "/si", '', $c, 1);

        if ($used_components) {
            // insert in the class
            $matches = array();
            $matched = preg_match("/^class\s*" . $class . "\s*extends.*\{$/smi", $c, $matches, PREG_OFFSET_CAPTURE);

            if (!$matched) {
                assert(false);
                return null;
            }

            $pos = (int)$matches[0][1];
            $pos = strpos($c, '{', $pos) + 1;

            $c = substr($c, 0, $pos) .
                "\n" . ' protected $use_components = array(' . "\n" . '\'' . implode('\',' . "\n" . '\'', $used_components) . '\');' .
                substr($c, $pos, strlen($c));

            file_put_contents($filename, $c);
        }

        return null;
    }

    private function fixAddRequiredPlugins($item_type, array $details)
    {
        $filename = $details['filename'];
        $class = $details['class'];
        $c = file_get_contents($filename);

        if (!$c) {
            return;
        }

        // parse and find out the previous plugins
        //
        $used_plugins = array();

        /*
         * $matches = array();
        $matched = preg_match("/" . 'protected.*\$use_plugins[^=]+=[^a]+array\((.+?)\)' . "/smi", $c, $matches);
        $matches_str = $matched ? $matches[1] : null;
        $matches_str = $matches_str ? lcStrings::toAlphaNum($matches_str, array(',')) : null;
        $used_plugins = $matches_str ? array_unique(array_filter(explode(',', $matches_str))) : array();
        */

        // parse the file to find other used plugins and add them
        $matches = $this->matchInFile("/->getPlugin\(['\"]([\w\d]+?)['\"]\)/i", $filename);

        if ($matches) {
            $used_plugins = array_unique(array_merge(
                $used_plugins,
                (array)$matches[1]
            ));
        }

        // remove the previous declaration
        $c = preg_replace("/protected.*" . '\$use_plugins[^;]+;' . "/si", '', $c, 1);

        if ($used_plugins) {
            // insert in the class
            $matches = array();
            $matched = preg_match("/^class\s*" . $class . "\s*extends.*\{$/smi", $c, $matches, PREG_OFFSET_CAPTURE);

            if (!$matched) {
                assert(false);
                return;
            }

            $pos = (int)$matches[0][1];
            $pos = strpos($c, '{', $pos) + 1;

            $c = substr($c, 0, $pos) .
                "\n" . ' protected $use_plugins = array(' . "\n" . '\'' . implode('\',' . "\n" . '\'', $used_plugins) . '\');' .
                substr($c, $pos, strlen($c));

            file_put_contents($filename, $c);
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function _lc14ApplyProjectTreeFixes($item_type, array $details)
    {
        $filename = $details['filename'];
        $parent = $details['parent'];

        $this->consoleDisplay('> ' . $item_type . ' : ' . $filename);

        // fix protected vars
        if ($parent == 'lcSysObj') {
            $this->consoleDisplay(' - applying lcSysObj protected vars fix');

            $regex = $this->_getFixProtectedSysObjClassVarsRegex();
            $this->replaceInFile($regex['regex'], $regex['reps'], $filename);
        } elseif ($parent == 'lcAppObj') {
            $this->consoleDisplay(' - applying lcAppObj protected vars fix');

            $regex = $this->_getFixProtectedAppObjClassVarsRegex();
            $this->replaceInFile($regex['regex'], $regex['reps'], $filename);
        }

        // add required plugins
        if ($parent == 'lcSysObj' || $parent == 'lcAppObj') {
            $this->fixAddRequiredPlugins($item_type, $details);
            $this->fixAddRequiredComponents($item_type, $details);

            // replace $request['request'] occurrences (previous way of passing forwarded params)
            $this->replaceInFile("/" . '\$' . "request\[['\"]request['\"]]/i", '$request', $filename);
        }
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function upgradeFromLC14()
    {
        $project_dir = $this->project_dir;
        $available_apps = $this->available_apps;

        // fix master config files
        $this->_lc14FixMasterConfigurations($project_dir, $available_apps);

        // walk the project tree and apply various fixes
        $this->consoleDisplay('Applying project tree fixes');
        $this->getProjectTree('_lc14ApplyProjectTreeFixes');

        return true;
    }
}
