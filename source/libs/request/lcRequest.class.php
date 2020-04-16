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

/**
 * @method getServerPort()
 * @method getPath()
 */
abstract class lcRequest extends lcResidentObj implements iProvidesCapabilities, Serializable,
    ArrayAccess, iKeyValueProvider, iDebuggable, iArrayable
{
    /**
     * @var lcArrayCollection
     */
    protected $params;

    protected $request_data;

    /**
     * @var string
     */
    protected $call_style;

    /**
     * @var array
     */
    protected $env;

    protected $sapi;

    protected $is_running_cli;
    protected $cli_path;

    protected $is_silent;

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getCapabilities()
    {
        return [
            'request',
        ];
    }

    public function getDebugInfo()
    {
        return [
            'env' => $this->env,
            'sapi' => $this->sapi,
            'in_cli' => $this->is_running_cli,
        ];
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    public function __toString()
    {
        // TODO: Fix this
        return "lcReqest: \n\n" .
            e($this->env, true) . "\n\n";
    }

    abstract public function getRequestContext();

    /*
     * Initialization of the Request
    */
    public function initialize()
    {
        parent::initialize();

        $this->call_style = $this->configuration['controller.call_style'];
    }

    public function getAllKeys()
    {
        $keys = null;
        $p = $this->getParams();

        if ($p) {
            /** @var lcNameValuePair[] $pp */
            $pp = $p->getArrayCopy();
            $keys = [];

            foreach ($pp as $a) {
                $keys[] = $a->getName();
                unset($a);
            }

            unset($pp);
        }

        unset($p);

        return $keys;
    }

    #pragma mark - iKeyValueProvider

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(lcArrayCollection $params)
    {
        $this->params = $params;
    }

    public function getValueForKey($key)
    {
        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        return $this->getParam($key);
    }

    public function getParam($name)
    {
        return $this->params->get($name);
    }

    public function getIsSilent()
    {
        return $this->is_silent;
    }

    public function setIsSilent($is_silent = true)
    {
        $this->is_silent = $is_silent;
    }

    public function __call($method, array $params = null)
    {
        // search in environment if method has the form 'getServerSoftware = SERVER_SOFTWARE'
        $prefix = 'get';
        $prs = strlen($prefix);

        if (substr($method, 0, $prs) != $prefix) {
            parent::__call($method, $params);
        }

        $env = $this->env;

        $str = substr($method, $prs, strlen($method));
        $str = strtoupper(lcInflector::underscore($str));

        if (!isset($env[$str])) {
            return false;
            //return parent::__call($method, $params);
        }

        return $env[$str];
    }

    public function offsetExists($name)
    {
        if ($this->call_style == lcController::CALL_STYLE_REQRESP) {
            return isset($this->request_data[$name]);
        } else {
            return isset($this->env[$name]);
        }
    }

    /**
     * @param string $call_style
     */
    public function setCallStyle($call_style)
    {
        $this->call_style = $call_style;
    }

    public function offsetGet($name)
    {
        if ($this->call_style == lcController::CALL_STYLE_REQRESP) {
            return isset($this->request_data[$name]) ? $this->request_data[$name] : null;
        } else {
            return $this->env($name);
        }
    }

    public function env($name = false)
    {
        return isset($name) ? (isset($this->env[$name]) ? $this->env[$name] : null) : $this->env;
    }

    /*
     * Gets the Request parameters
    */

    public function toArray()
    {
        return (array)$this->getRequestData();
    }

    /*
     * Gets a single Request parameter by name
    */

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return $this->request_data;
    }

    /**
     * @param mixed $request_data
     * @return lcRequest
     */
    public function setRequestData($request_data)
    {
        $this->request_data = $request_data;
        return $this;
    }

    /*
     * Checks if Request has a parameter by its name
    */

    public function offsetSet($name, $value)
    {
        throw new lcUnsupportedException('Changing env variables is not supported');
    }

    /*
     * sets the default request vars - before clearing globals
    */

    public function offsetUnset($name)
    {
        throw new lcUnsupportedException('Changing env variables is not supported');
    }

    /*
     * Get the whole environment
    */

    public function hasParam($name)
    {
        return $this->params->get($name) ? true : false;
    }

    /*
     * PHP Value: SAPI
    */

    public function getEnv()
    {
        return $this->env;
    }

    public function getSapi()
    {
        return $this->sapi;
    }

    public function isInCli()
    {
        return $this->is_running_cli;
    }

    /*
     * PHP Value: PHP CLI Path
    */

    public function isRunningCli()
    {
        return $this->is_running_cli;
    }

    public function getCliPath()
    {
        return $this->cli_path;
    }

    public function getPort()
    {
        return $this->getServerPort();
    }

    /*
     * PHP Header: SystemRoot
    * Platform-Specific (Windows)
    */

    public function getPHPPath()
    {
        return $this->getPath();
    }

    /*
     * PHP Header: PATHEXT
    * Platform-Specific (Windows)
    */

    public function getSystemRoot()
    {
        return $this->env('SystemRoot');
    }

    public function getPathExt()
    {
        return $this->env('PATHEXT');
    }

    /*
     * PHP Header: WINDIR
    * Platform-Specific (Windows)
    */

    public function getHostname()
    {
        //return $this->env('SERVER_NAME');
        return $this->env('HTTP_HOST');
    }

    /*
     * PHP Header: PHP_SELF
    */

    public function getWinDir()
    {
        return $this->env('WINDIR');
    }

    public function getPHPSelf()
    {
        return $this->getPhpSelf();
    }

    public function serialize()
    {
        return serialize([$this->env]);
    }

    /*
     * Provides access to Request
    * Environment.
    */

    public function unserialize($serialized)
    {
        [$this->env] = unserialize($serialized);
    }

    /*
     * Emulation for various vars -
    * before running the clear of global vars
    *
    */

    protected function beforeAttachRegisteredEvents()
    {
        parent::beforeAttachRegisteredEvents();

        // init default vars
        $this->sapi = lcSys::get_sapi();
        $this->is_running_cli = lcSys::isRunningCLI();

        // TODO: Deprecated - workaround this!
        //$this->cli_path = lcSys::getPhpCli();

        // initialize default environment
        $this->initializeEnvironment();
    }

    private function initializeEnvironment()
    {
        $this->env = [];

        if (!isset($_SERVER)) {
            throw new lcInvalidRequestException('Invalid server environment');
        }

        // fix broken HTTP_AUTHORIZATION for phpfcgi
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            if (isset($_SERVER['Authorization']) && (strlen($_SERVER['Authorization']) > 0)) {
                [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']] = explode(':', base64_decode(substr($_SERVER['Authorization'], 6)));
                if (strlen($_SERVER['PHP_AUTH_USER']) == 0 || strlen($_SERVER['PHP_AUTH_PW']) == 0) {
                    unset($_SERVER['PHP_AUTH_USER']);
                    unset($_SERVER['PHP_AUTH_PW']);
                }
            }
        }

        // merge all
        $merged_env_vars = array_merge((array)$_SERVER, (array)$_ENV/*, (array)$_REQUEST* fixed in 1.4 - not valid to merge it here*/);

        // force the following vars which are always required
        $merged_env_vars['SCRIPT_FILENAME'] = isset($merged_env_vars['SCRIPT_FILENAME']) ? $merged_env_vars['SCRIPT_FILENAME'] : null;
        $merged_env_vars['DOCUMENT_ROOT'] = isset($merged_env_vars['DOCUMENT_ROOT']) ? $merged_env_vars['DOCUMENT_ROOT'] : null;
        $merged_env_vars['PHP_SELF'] = isset($merged_env_vars['PHP_SELF']) ? $merged_env_vars['PHP_SELF'] : null;
        $merged_env_vars['CGI_MODE'] = isset($merged_env_vars['CGI_MODE']) ? $merged_env_vars['CGI_MODE'] : null;
        $merged_env_vars['HTTP_BASE'] = isset($merged_env_vars['HTTP_BASE']) ? $merged_env_vars['HTTP_BASE'] : null;
        $merged_env_vars['PATH_INFO'] = isset($merged_env_vars['PATH_INFO']) ? $merged_env_vars['PATH_INFO'] : null;
        $merged_env_vars['SCRIPT_URL'] = isset($merged_env_vars['SCRIPT_URL']) ? $merged_env_vars['SCRIPT_URL'] : null;

        foreach ($merged_env_vars as $key => $val) {
            $this->env[$key] = $this->_env($key);
            unset($key, $val);
        }
    }

    private function _env($key)
    {
        $val = null;

        if ($key == 'HTTPS') {
            if (isset($_SERVER) && !empty($_SERVER)) {
                return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
            } else {
                return (strpos($this->_env('SCRIPT_URI'), 'https://') === 0);
            }
        } else if ($key == 'SCRIPT_NAME') {
            if ($this->_env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
                $key = 'SCRIPT_URL';
            }
        }

        if (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        } else if (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        } /* not valid to merge it here! fixed in lc 1.4 - elseif (isset($_REQUEST[$key]))
		{
			$val = $_REQUEST[$key];
		}*/
        else if (getenv($key) !== false) {
            $val = getenv($key);
        }

        if ($key == 'REMOTE_ADDR_REAL') {
            $val = $_SERVER['REMOTE_ADDR'];

            if ($addr = $this->_env('HTTP_PC_REMOTE_ADDR')) {
                $val = $addr;
            }

            unset($addr);
        } else if ($key == 'REMOTE_ADDR' /*&& $val == $this->_env('SERVER_ADDR')*/) {
            if ($forwarded_for = $this->_env('HTTP_X_FORWARDED_FOR')) {
                $val = $forwarded_for;
            } else if ($addr = $this->_env('HTTP_PC_REMOTE_ADDR')) {
                $val = $addr;
            }

            unset($forwarded_for, $addr);
        }

        if ($val) {
            return $val;
        }

        switch ($key) {
            case 'SCRIPT_FILENAME':
                if (defined('SERVER_IIS') && SERVER_IIS === true) {
                    return str_replace('\\\\', '\\', $this->_env('PATH_TRANSLATED'));
                }
                break;
            case 'DOCUMENT_ROOT':
                $offset = 0;
                if (!strpos($this->_env('SCRIPT_NAME'), '.php')) {
                    $offset = 4;
                }
                return substr($this->_env('SCRIPT_FILENAME'), 0, strlen($this->_env('SCRIPT_FILENAME')) - (strlen($this->env('SCRIPT_NAME')) + $offset));
                break;
            case 'PHP_SELF':
                return r($this->_env('DOCUMENT_ROOT'), '', $this->_env('SCRIPT_FILENAME'));
                break;
            case 'CGI_MODE':
                return (substr(php_sapi_name(), 0, 3) == 'cgi');
                break;
            case 'HTTP_BASE':
                return preg_replace('/^([^.])*/i', null, $this->_env('HTTP_HOST'));
                break;
            case 'PATH_INFO':

                $request_uri = $this->_env('REQUEST_URI');

                if ($request_uri) {
                    // separate params from url
                    if ($t = strpos($request_uri, '?')) {
                        $request_uri = substr($request_uri, 0, $t);
                    } else if ($t = strpos($request_uri, '&')) {
                        $request_uri = substr($request_uri, 0, $t);
                    } else if ($t = strpos($request_uri, '#')) {
                        $request_uri = substr($request_uri, 0, $t);
                    }

                    unset($t);
                }

                return $request_uri;
                break;
            case 'SCRIPT_URL':
                return isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : $this->_env('REQUEST_URI');
                break;
        }

        return $val;
    }
}
