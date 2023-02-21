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
class lcComponentLocator
{
    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getProjectApplicationsInPath(string $path, string $namespace, array $options = null): array
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $subdirs = lcDirs::getSubDirsOfDir($path);

        $applications = [];

        if ($subdirs) {
            foreach ($subdirs as $dir) {
                $found = self::getProjectApplicationContextInfo($dir, $namespace, $path . DS . $dir);

                $applications[$dir] = $options ? array_merge($options, $found) : $found;

                unset($dir);
            }
        }

        return $applications;
    }

    /**
     * @param string $application_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getProjectApplicationContextInfo(string $application_name, string $namespace, string $path): array
    {
        return [
            'name' => $application_name,
            'namespace' => $namespace,
            'path' => $path,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getPluginsInPath(string $path, string $namespace, array $options = null): array
    {
        if (!$path) {
            throw new lcInvalidArgumentException('Invalid path');
        }

        $subdirs = lcDirs::getSubDirsOfDir($path);

        $plugins = [];

        if ($subdirs) {
            foreach ($subdirs as $dir) {
                $found = self::getPluginContextInfo($dir, $namespace, $path . DS . $dir);
                $plugins[$dir] = $options ? array_merge($options, $found) : $found;
                unset($dir);
            }
        }

        return $plugins;
    }

    /**
     * @param string $plugin_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getPluginContextInfo(string $plugin_name, string $namespace, string $path): array
    {
        return [
            'name' => $plugin_name,
            'path' => $path,
            'filename' => $plugin_name . '.php',
            'class' => $namespace . '\\' . $plugin_name . '\\' . $plugin_name,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     * @par
     * am array|null $options
     */
    public static function getControllerComponentsInPath(string $path, string $namespace, array $options = null): array
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
                $found = self::getControllerComponentContextInfo($component_name, $namespace, $fullpath);

                $components[$component_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $component_name, $fullpath);
            }
        }

        return $components;
    }

    /**
     * @param string $controller_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getControllerComponentContextInfo(string $controller_name, string $namespace, string $path): array
    {
        return [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => $namespace . '\\' . $controller_name . '\\' . $controller_name,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getActionFormsInPath(string $path, string $namespace, array $options = null): array
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
                $found = self::getActionFormContextInfo($form_name, $namespace, $fullpath);

                $forms[$form_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $form_name, $fullpath);
            }
        }

        return $forms;
    }

    /**
     * @param string $form_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getActionFormContextInfo(string $form_name, string $namespace, string $path): array
    {
        return [
            'name' => $form_name,
            'path' => $path,
            'filename' => $form_name . '.php',
            'class' => $namespace . '\\' . $form_name,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getControllerModulesInPath(string $path, string $namespace, array $options = null): array
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
                $found = self::getControllerModuleContextInfo($module_name, $namespace, $fullpath);

                $modules[$module_name] = $options ? array_merge($options, $found) : $found;

                unset($info, $module_name, $fullpath);
            }
        }

        return $modules;
    }

    /**
     * @param string $controller_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getControllerModuleContextInfo(string $controller_name, string $namespace, string $path): array
    {
        return [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => $namespace . '\\' . $controller_name . '\\' . $controller_name,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getControllerWebServicesInPath(string $path, string $namespace, array $options = null): array
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
                $found = self::getControllerWebServiceContextInfo($web_service_name, $namespace, $fullpath);

                $found = $options ? array_merge($options, $found) : $found;
                $web_services[$web_service_name] = $found;

                unset($spl, $filename, $info, $web_service_name, $fullpath);
            }
        }

        return $web_services;
    }

    /**
     * @param string $controller_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getControllerWebServiceContextInfo(string $controller_name, string $namespace, string $path): array
    {
        return [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => $namespace . '\\' . $controller_name,
        ];
    }

    /**
     * @param string $path
     * @param string $namespace
     * @param array|null $options
     * @return array
     * @throws lcInvalidArgumentException
     */
    public static function getControllerTasksInPath(string $path, string $namespace, array $options = null): array
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
                $found = self::getControllerTaskContextInfo($task_name, $namespace, $fullpath);

                $found = $options ? array_merge($options, $found) : $found;
                $tasks[$task_name] = $found;

                unset($spl, $filename, $info, $task_name, $fullpath);
            }
        }

        return $tasks;
    }

    /**
     * @param string $controller_name
     * @param string $namespace
     * @param string $path
     * @return array
     */
    public static function getControllerTaskContextInfo(string $controller_name, string $namespace, string $path): array
    {
        return [
            'name' => $controller_name,
            'path' => $path,
            'filename' => $controller_name . '.php',
            'class' => $namespace . '\\' . $controller_name,
        ];
    }
}
