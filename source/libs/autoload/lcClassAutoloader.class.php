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

class lcClassAutoloader extends lcSysObj implements iCacheable
{
    /**
     * @var array
     */
    protected $registered_classes = [];

    protected $spl_registered;

    public function initialize()
    {
        parent::initialize();

        // register into system
        $this->splRegister();
    }

    public function splRegister()
    {
        if ($this->spl_registered) {
            return true;
        }

        $registered = spl_autoload_register([$this, 'loadClass']);

        $this->spl_registered = $registered;

        return $registered;
    }

    public function shutdown()
    {
        // unregister from system
        $this->splUnregister();

        parent::shutdown();
    }

    public function splUnregister()
    {
        if (!$this->spl_registered) {
            return false;
        }

        $registered = spl_autoload_unregister([$this, 'loadClass']);

        $this->spl_registered = false;

        return $registered;
    }

    public function isSplRegistered()
    {
        return $this->spl_registered;
    }

    public function addClasses(array $classes)
    {
        $current_classes = (array)$this->registered_classes;
        $new_classes = array_merge($current_classes, $classes);
        $this->registered_classes = $new_classes;
    }

    public function addFromObject(iSupportsAutoload $obj, $base_dir = null)
    {
        $autoload_classes = $obj->getAutoloadClasses();

        if ($autoload_classes && is_array($autoload_classes)) {
            foreach ($autoload_classes as $class_name => $filename) {
                $filename = ($filename[0] == '/') ? $filename : ($base_dir ? $base_dir . DS . $filename : $filename);
                $this->addClass($class_name, $filename);
                unset($class_name, $filename);
            }
        }
    }

    public function addClass($class_name, $filename)
    {
        // do not allow overwriting existing class registrations for security reasons
        if (isset($this->registered_classes[$class_name])) {
            return;
        }

        $this->registered_classes[$class_name] = $filename;
    }

    public function hasClass($class_name)
    {
        return isset($this->registered_classes[$class_name]);
    }

    public function getRegisteredClasses()
    {
        return $this->registered_classes;
    }

    public function setRegisteredClasses(array $registered_classes = null)
    {
        $this->registered_classes = $registered_classes;
    }

    public function loadClass($class_name)
    {
        $included = false;

        // check if class is registered
        $class_registered = isset($this->registered_classes[$class_name]);

        // we check if there are more than 2 autoloaders (this one and propel's one which is used for nothing)
        // if there are - we do not throw an exception here but allow the other autoloaders to also try to load the class

        if ($class_registered) {
            // try to include it
            /** @noinspection PhpIncludeInspection */
            $included = (bool)include($this->registered_classes[$class_name]);
        }

        if (!$included) {
            $error_message = null;
            $error_code = 0;

            // try other autoloaders if available
            $autoloaders = spl_autoload_functions();

            if ($autoloaders) {
                // try with each loader
                // store the first detected error
                foreach ($autoloaders as $autoloader) {

                    if (!is_array($autoloader) || $autoloader instanceof Closure) {
                        continue;
                    }

                    $obj = $autoloader[0];
                    $func = $autoloader[1];

                    // skip the current one
                    if ($obj === $this && $func == 'loadClass') {
                        continue;
                    }

                    try {
                        call_user_func_array([$obj, $func], [$class_name]);

                        $included = class_exists($class_name, false);

                        if ($included) {
                            break;
                        }
                    } catch (Exception $e) {
                        if (!$error_message) {
                            $error_message = $e->getMessage();
                        }

                        if (!$error_code) {
                            $error_code = $e->getCode();
                        }

                        continue;
                    }

                    unset($autoloader);
                }
            }

            // if we have a success
            if ($included) {
                return true;
            }

            // unfortunately - at this moment there is no way to distinguish between a class_exists() call or
            // trying to instantiate a missing class
            // so if we throw an exception here - it might be an exception thrown to a class_exists() check
            // that should not happen...
            // until PHP resolves this issue (because it IS an issue) we cannot apply this
            // and we silently exit and do nothing further.... pitty..
            return false;

            /*
            $this->event_dispatcher->notify(new lcEvent('class_autoloader.class_not_found', $this, array(
                    'class_name' => $class_name,
                    'error_message' => $error_message,
                    'error_code' => $error_code
            )));

            throw new Exception('Could not load class: ' . $class_name);*/
        }

        return true;
    }

    public function writeClassCache()
    {
        $cache = [
            'registered_classes' => $this->registered_classes,
        ];
        return $cache;
    }

    public function readClassCache(array $cached_data)
    {
        $this->registered_classes = isset($cached_data['registered_classes']) ? $cached_data['registered_classes'] : null;
    }
}
