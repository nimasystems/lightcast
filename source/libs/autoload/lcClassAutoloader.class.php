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
    protected $registered_classes = array();

    private $included_classes = array();

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

        $registered = spl_autoload_register(array($this, 'loadClass'));

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

        $registered = spl_autoload_unregister(array($this, 'loadClass'));

        $this->spl_registered = false;

        return $registered;
    }

    public function isSplRegistered()
    {
        return $this->spl_registered;
    }

    public function addClass($class_name, $filename)
    {
        // do not allow overwriting existing class registrations for security reasons
        if (isset($this->registered_classes[$class_name])) {
            return;
        }

        $this->registered_classes[$class_name] = $filename;
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
                $filename = ($filename{0} == '/') ? $filename : ($base_dir ? $base_dir . DS . $filename : $filename);
                $this->addClass($class_name, $filename);
                unset($class_name, $filename);
            }
        }
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
        if (in_array($class_name, $this->included_classes)) {
            return true;
        }

        // check if class is registered
        $cls_fname = isset($this->registered_classes[$class_name]) ?
            $this->registered_classes[$class_name] : null;

        if (!$cls_fname) {
            return false;
        }

        include_once $this->registered_classes[$class_name];
        $cls_exists = class_exists($class_name, false);

        if ($cls_exists) {
            $this->included_classes[] = $class_name;
        }

        return $cls_exists;
    }

    public function writeClassCache()
    {
        $cache = array(
            'registered_classes' => $this->registered_classes,
        );
        return $cache;
    }

    public function readClassCache(array $cached_data)
    {
        $this->registered_classes = isset($cached_data['registered_classes']) ? $cached_data['registered_classes'] : null;
    }
}
