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

abstract class lcSecurityUser extends lcUser implements iDebuggable
{
    const NS_KEY = 'user_security';
    const DEFAULT_TIMEOUT = 30; // in minutes
    /** @var array */
    protected $authentication_data;
    /** @var lcStorage */
    protected $storage;
    protected $user_id;
    protected $is_authenticated;
    protected $timeout;
    protected $last_request;
    protected $has_expired;

    // in mins
    private $diff_to_expire;

    abstract public function hasCredential($creditenal_name);

    public function initialize()
    {
        parent::initialize();

        $this->storage = $this->event_dispatcher->provide('loader.storage', $this)->getReturnValue();

        if ($this->storage) {
            $this->readFromStorage();

            // verify if the current session is still in time
            // if not - manually expire it
            $this->checkAndExpireIfNecessary();

            // mark the last request
            $last_request = time();
            $this->storage->set('last_request', $last_request, self::NS_KEY);
            $this->diff_to_expire = $this->calculateTimeoutInSeconds();

            $this->event_dispatcher->notify(new lcEvent('user.read_security', $this));
        }
    }

    protected function readFromStorage()
    {
        $this->is_authenticated = (bool)$this->storage->get('is_authenticated', self::NS_KEY);
        $this->authentication_data = (array)$this->storage->get('authentication_data', self::NS_KEY);
        $this->user_id = (string)$this->storage->get('user_id', self::NS_KEY);
        $this->last_request = (int)$this->storage->get('last_request', self::NS_KEY);
        $this->timeout = (int)$this->storage->get('timeout', self::NS_KEY);

        $this->attributes = $this->storage->get('attributes', self::NS_KEY);

        $timeout = (int)$this->configuration['user.timeout'] ? (int)$this->configuration['user.timeout'] : self::DEFAULT_TIMEOUT;

        $this->setTimeout($timeout);

        if (DO_DEBUG) {
            // basic checks
            if ($this->is_authenticated) {
                assert(isset($this->authentication_data));
                assert(isset($this->user_id));
            }

            $this->debug('user security data read');
        }

        if ($this->is_authenticated) {
            $this->info('user authenticated by session: ' . $this->user_id);
        }
    }

    protected function checkAndExpireIfNecessary()
    {
        $this->diff_to_expire = 0;

        if (!$this->is_authenticated || !$this->last_request || $this->has_expired) {
            return false;
        }

        $timeout_in_seconds = $this->timeout * 60;

        /*
         * if Time = 0 - no expiration
        */
        if (!$timeout_in_seconds) {
            return false;
        }

        $new_time = time() - ($timeout_in_seconds);
        $old_time = (int)$this->last_request;

        if ($new_time > $old_time) {
            $this->has_expired = true;

            $this->info('user session has expired. Last action: ' . date('d.m.Y H:i:s', $this->last_request));

            $last_request = $this->last_request;
            $timeout = $this->timeout;
            $diff = $new_time - $old_time;
            $user_id = $this->user_id;
            $credentials = $this->getCredentials();
            $auth_data = $this->authentication_data;

            $this->event_dispatcher->notify(new lcEvent('user.session_will_expire', $this,
                ['user_id' => $user_id,
                 'last_request' => $last_request,
                 'timeout' => $timeout,
                 'overtime' => $diff,
                ]));

            $this->setAuthenticated(false);

            $this->event_dispatcher->notify(new lcEvent('user.session_expired', $this,
                ['user_id' => $user_id,
                 'authentication_data' => $auth_data,
                 'credentials' => $credentials,
                 'last_request' => $last_request,
                 'timeout' => $timeout,
                 'overtime' => $diff,
                ]));

            /*
             * It's important to return TRUE - this is how callers know
            * that the session had expired!
            */
            return true;
        }

        return false;
    }

    abstract public function getCredentials();

    protected function setAuthenticated($authenticated, $forced_by_user = false, $no_events = false)
    {
        return $authenticated ? $this->setAuthentication($forced_by_user, $no_events) : $this->clearAuthentication($forced_by_user, $no_events);
    }

    protected function setAuthentication($forced_by_user = false, $no_events = false)
    {
        if ($this->is_authenticated) {
            return $this->refreshUserSession($forced_by_user, $no_events);
        }

        // check if should be authenticated or not
        if (!$this->shouldUserAuthenticate($forced_by_user, $no_events)) {
            return false;
        }

        $this->last_request = time();
        $this->is_authenticated = true;

        $this->storage->set('last_request', $this->last_request, self::NS_KEY);
        $this->storage->set('is_authenticated', $this->is_authenticated, self::NS_KEY);

        $this->info('User ' . $this->user_id . ' authenticated');

        // event telling everyone user is now authenticated
        $this->refreshUserSession($forced_by_user, $no_events);

        if (!$no_events) {
            $this->event_dispatcher->notify(new lcEvent('user.session_refresh', $this,
                [
                    'is_authenticated' => $this->is_authenticated,
                    'forced_by_user' => $forced_by_user,
                ]));
        }

        return true;
    }

    protected function refreshUserSession($forced_by_user = false, $no_events = false)
    {
        if (!$this->is_authenticated) {
            return false;
        }

        if (!$no_events) {
            // already authenticated - just send a 'refresh' event
            $this->event_dispatcher->notify(new lcEvent('user.session_refresh', $this,
                [
                    'is_authenticated' => $this->is_authenticated,
                    'forced_by_user' => $forced_by_user,
                ]));
        }

        return true;
    }

    protected function shouldUserAuthenticate($forced_by_user = false, $no_events = false)
    {
        // listen to an event with which authentication can be stopped
        $event = $this->event_dispatcher->filter(
            new lcEvent('user.should_authenticate',
                $this,
                ['user_id' => $this->user_id,
                 'forced_by_user' => $forced_by_user,
                 'authentication_data' => $this->authentication_data]
            ), []);

        $should_authenticate = $event->isProcessed() ? $event->getReturnValue() : true;

        return $should_authenticate;
    }

    protected function clearAuthentication($forced_by_user = false, $no_events = false)
    {
        if (!$this->is_authenticated) {
            return true;
        }

        $tmp_user_id = $this->user_id;

        $this->is_authenticated = null;
        $this->last_request = time();
        $this->user_id = null;
        $this->authentication_data = null;
        $this->timeout = 0;

        $this->storage->set('authentication_data', $this->authentication_data, self::NS_KEY);
        $this->storage->set('user_id', $this->user_id, self::NS_KEY);
        $this->storage->set('is_authenticated', $this->is_authenticated, self::NS_KEY);
        $this->storage->set('last_request', time(), self::NS_KEY);
        $this->storage->set('timeout', $this->timeout, self::NS_KEY);

        $this->unsetAttributes();

        $this->info('User ' . $tmp_user_id . ' de-authentication');

        // event telling everyone user is no longer authenticated
        if (!$no_events) {
            $this->event_dispatcher->notify(new lcEvent('user.authenticate', $this,
                [
                    'is_authenticated' => false,
                    'forced_by_user' => $forced_by_user,
                ]));

            $this->event_dispatcher->notify(new lcEvent('user.session_refresh', $this,
                [
                    'is_authenticated' => false,
                    'forced_by_user' => $forced_by_user,
                ]));
        }

        return true;
    }

    protected function calculateTimeoutInSeconds()
    {
        if (!$this->is_authenticated) {
            return 0;
        }

        $timeout = (int)$this->timeout;
        $last_request = (int)$this->last_request;

        if (!$timeout || !$this->last_request) {
            return false;
        }

        $new_time = time() - ($timeout * 60);
        $old_time = $last_request;

        $diff = $old_time - $new_time;

        return $diff;
    }

    public function shutdown()
    {
        // write attributes to cache
        $attributes = $this->is_authenticated ? $this->attributes : null;

        if ($this->storage) {
            $this->storage->set('attributes', $attributes, self::NS_KEY);
        }

        //$this->info('Wrote security credentials on shutdown(): ' . print_r($attributes, true));

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug_parent = parent::getDebugInfo();

        $debug = [
            'authentication_data' => $this->authentication_data,
            'user_id' => $this->user_id,
            'is_authenticated' => $this->is_authenticated,
            'timeout' => $this->timeout,
            'last_request' => $this->last_request,
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout($session_timeout)
    {
        $session_timeout = (int)$session_timeout;

        if (!$session_timeout) {
            throw new lcInvalidArgumentException('Invalid session timeout specified: ' . $session_timeout);
        }

        // make a check if the underlying storage's timeout is
        // equal or larger than the user's timeout
        $storage_timeout = (int)$this->configuration['storage.timeout'];

        if ($storage_timeout && $storage_timeout < $session_timeout) {
            throw new lcConfigException('Storage timeout (' . $storage_timeout . ') is less than the requested user\'s timeout (' . $session_timeout . ') - user session would expire prematurely!');
        }

        $this->timeout = (int)$session_timeout;

        // set gc_maxlifetime according to our timeout
        if (ini_get('session.gc_maxlifetime') < $this->timeout * 60) {
            ini_set('session.gc_maxlifetime', $this->timeout * 60);
        }

        $this->storage->set('timeout', $this->timeout, self::NS_KEY);

        if (DO_DEBUG) {
            $this->debug('user session timeout set: ' . $this->timeout . ' minutes.');
        }
    }

    public function getLastRequest()
    {
        return $this->last_request;
    }

    public function hasExpired()
    {
        return $this->has_expired;
    }

    public function forceExpire()
    {
        $this->setAuthenticated(false);
        $this->has_expired = true;
    }

    public function isAuthenticated()
    {
        return $this->is_authenticated;
    }

    public function __toString()
    {
        $str = 'lcSecurityUser: ' .
            'Is Authenticated: ' . (int)$this->is_authenticated . "\n" .
            'User ID: ' . (string)$this->user_id . "\n" .
            'Timeout: ' . (int)$this->timeout . ' minute(s)' . "\n" .
            'Seconds to timeout: ' . $this->diff_to_expire . "\n" .
            'Authentication Data: ' . var_export($this->authentication_data, true) . "\n" .
            'Last Request: ' . $this->last_request . "\n\n" .
            parent::__toString();

        return $str;
    }

    protected function getStorage()
    {
        return $this->storage;
    }

    protected function setAuthenticationData($user_id, array $authentication_data = null)
    {
        $this->user_id = $user_id;
        $this->authentication_data = $authentication_data;

        $this->storage->set('user_id', $this->user_id, self::NS_KEY);
        $this->storage->set('authentication_data', $this->authentication_data, self::NS_KEY);
    }
}
