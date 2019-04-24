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

class lcComponentLocator
{
    const PLUGIN_CLASS_PREFIX = 'p';
    const COMPONENT_CLASS_PREFIX = 'component';
    const MODULE_CLASS_PREFIX = 'c';
    const WEB_SERVICE_CLASS_PREFIX = 'ws';
    const TASK_CLASS_PREFIX = 't';

    public static function getProjectApplicationsInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $subdirs = lcDirs::getSubDirsOfDir($path);

        $applications = [];

        if ($subdirs) {
            foreach ($subdirs as $dir) {
                $found = self::getProjectApplicationContextInfo($dir, $path . DS . $dir);

                $applications[$dir] = $options ? array_merge($options, $found) : $found;

                unset($dir);
            }
        }

        return $applications;
    }

    public static function getProjectApplicationContextInfo($application_name, $path)
    {
        $ret = [
            'name' => $application_name,
            'path' => $path,
        ];
        return $ret;
    }

    public static function getPluginsInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $subdirs = lcDirs::getSubDirsOfDir($path);

        $plugins = [];

        if ($subdirs) {
            foreach ($subdirs as $dir) {
                $found = self::getPluginContextInfo($dir, $path . DS . $dir);

                // apply the assets webpath
                if (isset($options['web_path'])) {
                    $found['web_path'] = $options['web_path'] . $dir . '/';
                }

                $plugins[$dir] = $options ? array_merge($options, $found) : $found;

                unset($dir);
            }
        }

        return $plugins;
    }

    public static function getPluginContextInfo($plugin_name, $path)
    {
        $ret = [
            'name' => $plugin_name,
            'path' => $path,
            'filename' => $plugin_name . '.php',
            'class' => self::PLUGIN_CLASS_PREFIX . lcInflector::camelize($plugin_name, false),
        ];
        return $ret;
    }

    public static function getControllerComponentsInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        // scan the location for modules
        $subdirs = lcDirs::searchDir($path, false, true);

        $components = [];

        if ($subdirs) {
            foreach ($subdirs as $info) {
                if ($info['type'] != 'dir') {
                    continue;
                }

                $component_name = $info['name'];
                $fullpath = $path . DS . $component_name;

                // do not look for the file as the operation is way too expensive
                // even if the module is invalid - if it is invoked the operation will fail later on
                $found = self::getControllerComponentContextInfo($component_name, $fullpath);

                $components[$component_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $component_name, $fullpath);
            }
        }

        return $components;
    }

    public static function getControllerComponentContextInfo($controller_name, $path)
    {
        $ret = [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.class.php',
            'class' => self::COMPONENT_CLASS_PREFIX . lcInflector::camelize($controller_name, false),
        ];
        return $ret;
    }

    public static function getActionFormsInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        // scan the location for modules
        $subdirs = lcDirs::searchDir($path, false, true);

        $forms = [];

        if ($subdirs) {
            foreach ($subdirs as $info) {
                if ($info['type'] != 'dir') {
                    continue;
                }

                $form_name = $info['name'];
                $fullpath = $path . DS . $form_name;

                // do not look for the file as the operation is way too expensive
                // even if the module is invalid - if it is invoked the operation will fail later on
                $found = self::getActionFormContextInfo($form_name, $fullpath);

                $forms[$form_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $form_name, $fullpath);
            }
        }

        return $forms;
    }

    public static function getActionFormContextInfo($form_name, $path)
    {
        $ret = [
            'name' => $form_name,
            'path' => $path,
            'filename' => $form_name . '.php',
            'class' => lcInflector::camelize($form_name, false) . 'Form',
        ];
        return $ret;
    }

    public static function getControllerModulesInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        // scan the location for modules
        $subdirs = lcDirs::searchDir($path, false, true);

        $modules = [];

        if ($subdirs) {
            foreach ($subdirs as $info) {
                if ($info['type'] != 'dir') {
                    continue;
                }

                $module_name = $info['name'];
                $fullpath = $path . DS . $module_name;

                // do not look for the file as the operation is way too expensive
                // even if the module is invalid - if it is invoked the operation will fail later on
                $found = self::getControllerModuleContextInfo($module_name, $fullpath);

                $modules[$module_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $module_name, $fullpath);
            }
        }

        return $modules;
    }

    public static function getControllerModuleContextInfo($controller_name, $path)
    {
        $ret = [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => self::MODULE_CLASS_PREFIX . lcInflector::camelize($controller_name, false),
        ];
        return $ret;
    }

    public static function getControllerWebServicesInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $web_services = [];

        // scan the location for web services
        $subfiles = lcDirs::searchDir($path, true, true);

        if ($subfiles) {
            foreach ($subfiles as $info) {
                if ($info['type'] != 'file') {
                    continue;
                }

                $filename = $info['name'];

                $spl = lcFiles::splitFileName($filename);

                if (!$spl || $spl['ext'] != '.php') {
                    continue;
                }

                $web_service_name = $spl['name'];
                $fullpath = $path;

                // do not look for the file as the operation is way too expensive
                // even if the module is invalid - if it is invoked the operation will fail later on
                $found = self::getControllerWebServiceContextInfo($web_service_name, $fullpath);

                $found = $options ? array_merge($options, $found) : $found;
                $web_services[$web_service_name] = $found;

                unset($spl, $filename, $info, $web_service_name, $fullpath);
            }
        }

        return $web_services;
    }

    public static function getControllerWebServiceContextInfo($controller_name, $path)
    {
        $ret = [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => self::WEB_SERVICE_CLASS_PREFIX . lcInflector::camelize($controller_name, false),
        ];
        return $ret;
    }

    public static function getControllerTasksInPath($path, array $options = null)
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $tasks = [];

        // scan the location for tasks
        $subfiles = lcDirs::searchDir($path, true, true);

        if ($subfiles) {
            foreach ($subfiles as $info) {
                if ($info['type'] != 'file') {
                    continue;
                }

                $filename = $info['name'];

                $spl = lcFiles::splitFileName($filename);

                if (!$spl || $spl['ext'] != '.php') {
                    continue;
                }

                $task_name = $spl['name'];
                $fullpath = $path;

                // do not look for the file as the operation is way too expensive
                // even if the module is invalid - if it is invoked the operation will fail later on
                $found = self::getControllerTaskContextInfo($task_name, $fullpath);

                $found = $options ? array_merge($options, $found) : $found;
                $tasks[$task_name] = $found;

                unset($spl, $filename, $info, $task_name, $fullpath);
            }
        }

        return $tasks;
    }

    public static function getControllerTaskContextInfo($controller_name, $path)
    {
        $ret = [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => self::TASK_CLASS_PREFIX . lcInflector::camelize($controller_name, false),
        ];
        return $ret;
    }
}