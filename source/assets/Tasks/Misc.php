<?php
declare(strict_types=1);

namespace Lightcast\Assets\Tasks;

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

use lcDirs;
use lcPlugin;
use lcTaskController;

/**
 *
 */
class Misc extends lcTaskController
{
    private array $found_models = [];
    private array $found_php_files = [];

    /**
     * @return true
     */
    public function executeTask(): bool
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'find-models':
                return $this->findModels();
            default:
                return $this->displayHelp();
        }
    }

    /**
     * @return true
     */
    public function findModels(): bool
    {
        $this->consoleDisplay('Find models started');

        $this->found_models = [];
        $this->found_php_files = [];

        $this->_findModels();

        // scan them now
        $this->detectDbModelsInFile($this->found_models, $this->found_php_files);

        return true;
    }

    private function _findModels()
    {
        // plugins
        $plugins = $this->system_component_factory->getSystemPluginDetails();

        if ($plugins) {
            foreach ($plugins as $name => $info) {
                $php_files = [];
                $models = [];

                // plugin master file
                $php_files[] = $info['path'] . DS . $name . '.php';

                // plugin modules
                $modules_path = $info['path'] . DS . lcPlugin::MODULES_PATH;

                $mods = lcDirs::getSubDirsOfDir($modules_path);

                if ($mods) {
                    foreach ($mods as $mod_name) {
                        $php_files[] = $modules_path . DS . $mod_name . DS . $mod_name . '.php';
                        unset($mod_name);
                    }
                }

                unset($modules_path, $mods);

                // plugin tasks
                $tasks_path = $info['path'] . DS . lcPlugin::TASKS_PATH;

                $tasks = lcDirs::searchDir($tasks_path);

                if ($tasks) {
                    foreach ($tasks as $task_name) {
                        $php_files[] = $tasks_path . DS . $task_name['name'];
                        unset($task_name);
                    }
                }

                unset($tasks_path, $tasks);

                // web services
                $ws_path = $info['path'] . DS . lcPlugin::WEB_SERVICES_PATH;

                $web_services = lcDirs::searchDir($ws_path);

                if ($web_services) {
                    foreach ($web_services as $web_service_name) {
                        $php_files[] = $ws_path . DS . $web_service_name['name'];
                        unset($web_service_name);
                    }
                }

                unset($ws_path, $web_services);

                // models
                $models_path = $info['path'] . DS . lcPlugin::MODELS_PATH;
                $models_ = lcDirs::searchDir($models_path);

                if ($models_) {
                    foreach ($models_ as $m) {
                        $models[] = str_replace('.php', '', $m['name']);
                        unset($m);
                    }
                }

                $this->found_php_files = array_merge($this->found_php_files, $php_files);
                $this->found_models = array_merge($this->found_models, $models);

                unset($name, $info, $mods, $php_files, $models);
            }
        }

        // apps
        $apps = $this->system_component_factory->getAvailableProjectApplications();

        if ($apps) {
            foreach ($apps as $name => $details) {
                $php_files = [];

                // app modules
                $modules_path = DIR_APP . DS . 'applications' . DS . $name . DS . 'modules';

                $mods = lcDirs::getSubDirsOfDir($modules_path);

                if ($mods) {
                    foreach ($mods as $mod_name) {
                        $php_files[] = $modules_path . DS . $mod_name . DS . $mod_name . '.php';
                        unset($mod_name);
                    }
                }

                unset($modules_path, $mods);

                $this->found_php_files = array_merge($this->found_php_files, $php_files);

                unset($name, $info, $mods, $php_files, $details);
            }
        }

        // project tasks
        $tasks_path = DIR_APP . DS . 'tasks';

        $tasks = lcDirs::searchDir($tasks_path);

        if ($tasks) {
            $php_files = [];

            foreach ($tasks as $task) {
                $task_name = $task['name'];
                $php_files[] = $tasks_path . DS . $task_name;
                unset($task_name);
            }

            $this->found_php_files = array_merge($this->found_php_files, $php_files);
        }

        unset($tasks_path, $tasks);

        // project web services
        $ws_path = DIR_APP . DS . 'ws';

        $web_services = lcDirs::searchDir($ws_path);

        if ($web_services) {
            $php_files = [];

            foreach ($web_services as $web_service) {
                $web_service_name = $web_service['name'];
                $php_files[] = $ws_path . DS . $web_service_name;
                unset($web_service, $web_service_name);
            }

            $this->found_php_files = array_merge($this->found_php_files, $php_files);
        }

        unset($ws_path, $web_services);

        // project models
        $models_path = DIR_APP . DS . 'models';

        $models_ = lcDirs::searchDir($models_path);

        if ($models_) {
            $models = [];

            foreach ($models_ as $m) {
                $models[] = str_replace('.php', '', $m['name']);
                unset($m);
            }

            $this->found_models = array_merge($this->found_models, $models);
        }

        unset($tasks_path, $tasks);
    }

    private function detectDbModelsInFile(array $models, array $files)
    {
        foreach ($files as $filename) {
            $fdata = file_exists($filename) && is_readable($filename) ? file_get_contents($filename) : null;

            if (!$fdata) {
                continue;
            }

            foreach ($models as $model) {
                if (preg_match('/\b' . preg_quote($model) . '\b/', $fdata)) {
                    $this->consoleDisplay($filename . ': ' . $model, false);
                }

                unset($model);
            }

            unset($filename);
        }
    }

    /**
     * @return true
     */
    public function displayHelp(): bool
    {
        $this->consoleDisplay($this->getHelpInfo(), false);
        return true;
    }

    /**
     * @return string
     */
    public function getHelpInfo(): string
    {
        return "\n" . '--find-models - Detect all used models in the project by scanning all php plugin / module files' . "\n";
    }
}
