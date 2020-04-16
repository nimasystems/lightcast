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

class lcSessionStorage extends lcStorage implements iDebuggable
{
    const DEFAULT_NAMESPACE = 'global';
    const DEFAULT_TIMEOUT = 30;
    protected $storage;
    protected $session_id;
    protected $timeout;
    private $last_request;
    private $diff_to_expire; // in minutes

    public function initialize()
    {
        $configuration = $this->configuration;

        // auto start = no
        ini_set('session.auto_start', '0');

        // session name
        $session_name = (string)$configuration['storage.session_name'] ? (string)$configuration['storage.session_name'] : null;

        if ($session_name) {
            ini_set('session.name', $session_name);
        }

        // serialize_handler
        ini_set('session.serialize_handler', 'php');

        // path
        $session_dir = $this->configuration->getSessionDir();
        assert(isset($session_dir));

        // create session dir if missing
        if (!is_dir($session_dir)) {
            lcDirs::mkdirRecursive($session_dir);
        }

        ini_set('session.save_handler', 'files');
        ini_set('session.save_path', $session_dir);

        // garbage collection
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');

        // cookie path
        $cookie_path = (string)$configuration['storage.cookie_path'] ? (string)$configuration['storage.cookie_path'] : null;

        if ($cookie_path) {
            ini_set('session.cookie_path', $cookie_path);
        }

        // cookie domain
        $cookie_domain = (string)$configuration['storage.cookie_domain'] ? (string)$configuration['storage.cookie_domain'] : null;

        if ($cookie_domain) {
            ini_set('session.cookie_domain', $cookie_domain);
        }

        // usage of cookies
        $use_cookies = isset($configuration['storage.use_cookies']) ? (bool)$configuration['storage.use_cookies'] : true;
        ini_set('session.use_cookies', $use_cookies);

        // use only cookies
        $use_only_cookies = isset($configuration['storage.use_only_cookies']) ? (bool)$configuration['storage.use_only_cookies'] : true;
        ini_set('session.use_only_cookies', $use_only_cookies);

        // if these are ever needed some day - we'll add them
        //ini_set('session.cookie_secure', 'off');
        //ini_set('session.cookie_httponly', '1');

        // use_trans_sid
        $use_trans_sid = isset($configuration['storage.use_trans_sid']) ? (bool)$configuration['storage.use_trans_sid'] : false;
        ini_set('session.use_trans_sid', $use_trans_sid);

        // cache_limiter
        $cache_limiter = (string)$configuration['storage.cache_limiter'] ? (string)$configuration['storage.cache_limiter'] : 'nocache';
        ini_set('session.cache_limiter', $cache_limiter);

        // cache_expire
        $cache_expire = (string)$configuration['storage.cache_expire'] ? (string)$configuration['storage.cache_expire'] : null;

        if ($cache_expire) {
            ini_set('session.cache_expire', $cache_expire);
        }

        // session timeout
        $timeout = (int)$configuration['storage.timeout'] ? (int)$configuration['storage.timeout'] : self::DEFAULT_TIMEOUT;

        assert($timeout >= 1);

        $this->timeout = $timeout;

        $timeout = $timeout * 60; // gc_maxlifetime requires this in seconds
        ini_set('session.gc_maxlifetime', $timeout);
        ini_set('session.cookie_lifetime', 0);

        parent::initialize();
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $parent_debug = parent::getDebugInfo();

        $debug = [
            'session_id' => $this->session_id,
            'timeout' => $this->timeout,
            'last_request' => $this->last_request,
            'default_namespace' => self::DEFAULT_NAMESPACE,
            'default_timeout' => self::DEFAULT_TIMEOUT,
        ];

        $debug = array_merge($parent_debug, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getSessionId()
    {
        return $this->session_id;
    }

    public function has($key, $namespace = null)
    {
        return $this->get($key, $namespace) ? true : false;
    }

    /**
     * @param string $key
     * @param string $namespace
     * @return mixed
     */
    public function get($key, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return null;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        return isset($this->storage[$n][$key]) ? $this->storage[$n][$key] : null;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $namespace
     * @return mixed
     */
    public function set($key, $value = null, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return null;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        $this->storage[$n][$key] = $value;

        if (DO_DEBUG) {
            $str = is_string($value) ? $value : null;
            $this->debug('set: ' . $key . '=' . $str . (isset($n) ? ' in ns: ' . $n : null));
        }
    }

    /**
     * @param string $key
     * @param string $namespace
     */
    public function remove($key, $namespace = null)
    {
        if (!$key) {
            assert(false);
            return;
        }

        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return;
        }

        unset($this->storage[$n][$key]);

        if (DO_DEBUG) {
            $this->debug('remove: ' . $key . (isset($n) ? ' from ns: ' . $n : null));
        }
    }

    public function clear($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return;
        }

        unset($this->storage[$n]);

        if (DO_DEBUG) {
            $this->debug('internal storage namespace cleared: ' . $n);
        }
    }

    public function clearAll()
    {
        $this->storage = null;

        if (DO_DEBUG) {
            $this->debug('entire storage cleared');
        }
    }

    public function hasValues($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        return count($this->storage) ? true : false;
    }

    public function count($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        return count($this->storage[$n]);
    }

    public function getAll($namespace = null)
    {
        $n = isset($namespace) ? (string)$namespace : self::DEFAULT_NAMESPACE;

        if (!isset($this->storage[$n])) {
            return false;
        }

        return $this->storage[$n];
    }

    public function getBackendData()
    {
        return $this->storage;
    }

    public function getNamespaces()
    {
        return array_keys($this->storage);
    }

    public function __toString()
    {
        $p = (string)parent::__toString();

        return 'Session ID: ' . $this->session_id . "\n" .
            'Timeout: ' . $this->timeout . " min(s)\n\n" .
            'Seconds to timeout: ' . $this->diff_to_expire . "\n" .
            'Data: ' . "\n\n" .
            $p;
    }

    protected function trackTime()
    {
        // update the last request time at shutdown so it can be overriden
        $this->storage['_internal'] = ['last_request' => time()];
    }

    protected function readFromStorage()
    {
        parent::readFromStorage();

        try {
            session_start();
        } catch (Exception $e) {
            throw new lcStorageException('Cannot start session: ' . $e->getMessage());
        }

        $this->session_id = session_id();

        // move all variables from global var to local var
        // in shutdown() we will do exactly the opposite so session is not lost
        $this->storage = (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : [];
        $_SESSION = [];

        /*
         * Because the integrated PHP methods are not 100% reliable:
        * (http://stackoverflow.com/questions/520237/how-do-i-expire-a-php-session-after-30-minutes)
        * we implement our own checking in addition to the integrated ones
        */

        // load time of last request
        $this->last_request = isset($this->storage['_internal']['last_request']) ? (int)$this->storage['_internal']['last_request'] : 0;

        // check for expiration
        $this->checkAndExpireIfNecessary();

        // mark the last request
        $this->diff_to_expire = $this->calculateTimeoutInSeconds();
    }

    private function checkAndExpireIfNecessary()
    {
        $this->diff_to_expire = 0;

        $last_request = (int)$this->last_request;

        if (!$last_request) {
            return;
        }

        $new_time = time() - ($this->timeout * 60);
        $old_time = (int)$this->last_request;

        if ($new_time > $old_time) {
            $this->info('storage session has expired. Last action: ' . date('d.m.Y H:i:s', $this->last_request));

            $_SESSION = [];
            session_destroy();

            // restart
            session_start();

            $this->session_id = session_id();
            $this->storage = [];

            // send an event
            $this->event_dispatcher->notify(new lcEvent('storage.gc', $this,
                ['max_lifetime' => $last_request, 'session_id' => $this->session_id]
            ));

            $gctime = date('Y-m-d H:i:s', $last_request);

            $this->info('garbage collector invalidated sessions older than ' .
                $gctime . ' (' . lcVm::date_default_timezone_get() . ')', 'severity');
        }
    }

    private function calculateTimeoutInSeconds()
    {
        $timeout = (int)$this->timeout;
        $last_request = (int)$this->last_request;

        if (!$timeout || !$this->last_request) {
            return false;
        }

        $new_time = time() - ($timeout * 60);
        $old_time = $last_request;

        return $old_time - $new_time;
    }

    protected function writeToStorage()
    {
        parent::writeToStorage();

        $_SESSION = $this->storage;

        session_write_close();

        $this->storage = null;
    }
}
