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
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcWebRequest.class.php 1541 2014-06-20 14:08:58Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1541 $
 */
class lcWebRequest extends lcRequest implements Serializable, iDebuggable, iKeyValueProvider
{
    /**
     * @var lcHttpFilesCollection
     */
    private $files;

    /**
     * @var lcArrayCollection
     */
    private $post_params;

    /**
     * @var lcArrayCollection
     */
    private $get_params;

    private $request_method; // ANY, GET, PUT, POST, HEAD - httpMethods

    private $accept_mimetype;
    private $accept_language;
    private $accept_encoding;
    private $accept_charset;

    /**
     * @var lcCookiesCollection
     */
    private $cookies;

    private $app_url;
    private $app_server;
    private $uri_as_path;

    private $request_fext;

    private $prefix;
    private $context;

    private $protocol = self::HTTP_PROTO_HTTP;
    private $protocol_ver = '1.1';

    const HTTP_PROTO_HTTP = 1;
    const HTTP_PROTO_HTTPS = 2;

    /*
     * Stores the real client IP addres /proxies, etc.
    * Filled only after calling the getRealRemoteAddr() function
    */
    private $real_remote_addr;

    /*
     * Initialization of the Request
    */
    public function initializeBeforeApp(lcEventDispatcher $event_dispatcher, lcConfiguration $configuration)
    {
        parent::initializeBeforeApp($event_dispatcher, $configuration);

        $this->resetPHPRequestGlobals();

        // fix REQUEST_URI - it is not url escaped
        // but the rest of the env vars are (PATH_INFO, etc)
        if (isset($this->env['REQUEST_URI'])) {
            $uri = $this->env['REQUEST_URI'];
            $uri = trim(urldecode($uri));
            $this->env['REQUEST_URI'] = $uri;
        }

        // fix path info - add it if missing
        if (!isset($this->env['PATH_INFO'])) {
            $this->env['PATH_INFO'] = '';
        } else {
            $uri = $this->env['PATH_INFO'];
            $uri = trim(urldecode($uri));
            $this->env['PATH_INFO'] = $uri;
        }

        // some sanity checks
        $this->verifyEnv();

        $this->_set_request_uri();

        // set / fix prefix
        $prefix = $this->configuration->getPathInfoPrefix();

        if (!$prefix) {
            // try to autodetect the prefix - find diff between REQUEST_URI / PATH_INFO
            $prefix = @parse_url(str_replace($this->env['PATH_INFO'], '', $this->env['REQUEST_URI']), PHP_URL_PATH);
        }

        $len = strlen($prefix);
        $this->prefix = substr($prefix, $len - 1, $len) == '/' ? substr($prefix, 0, $len - 1) : $prefix;

        // init http method type
        $this->initHttpMethod();

        // TODO: This is temporary until we figure out how to handle the rest
        if ($this->request_method != lcHttpMethod::METHOD_GET &&
            $this->request_method != lcHttpMethod::METHOD_POST
        ) {
            $this->warning('Unsupported request method: ' . $this->env('REQUEST_METHOD') . ' - exiting');
            exit(0);
        }

        // init protocol type
        $in_https = isset($this->env['HTTPS']) || isset($this->env['REDIRECT_HTTPS']) ||
            (isset($this->env['HTTP_X_FORWARDED_PROTO']) && $this->env['HTTP_X_FORWARDED_PROTO'] == 'https');

        $proto = $this->env['SERVER_PROTOCOL'];
        $proto_expl = array_filter(explode('/', $proto));

        // check for protocol support
        if (!$proto_expl || !is_array($proto_expl) || count($proto_expl) != 2 || $proto_expl[0] != 'HTTP') {
            throw new lcUnsupportedException('Unsupported HTTP protocol');
        }

        $this->protocol = $in_https ? self::HTTP_PROTO_HTTPS : self::HTTP_PROTO_HTTP;
        $this->protocol_ver = (string)$proto_expl[1];

        unset($proto_expl);

        // disable magic quotes and set input vars
        $get = (array)$_GET;
        $post = (array)$_POST;

        $get = get_magic_quotes_gpc() ? lcStrings::slashStrip($get) : $get;
        $post = get_magic_quotes_gpc() ? lcStrings::slashStrip($post) : $post;

        // reset globals $_GET / $_POST
        $_GET = $get;
        $_POST = $post;

        $this->post_params = new lcArrayCollection((array)$post);
        $this->get_params = new lcArrayCollection((array)$get);

        unset($get, $post);

        // init context
        $this->initContext();

        // init request params
        $this->initParams();

        // init cookies
        $this->initCookies();

        // init uploaded files
        $this->initHttpFiles();

        // reset all global vars
        $this->resetAllGlobals();
    }

    public function initialize()
    {
        parent::initialize();

        // when router loads the detected params from request
        // it will notify us with this event
        // and request will set its local params to the ones detected by router
        $this->event_dispatcher->connect('router.detect_parameters', $this, 'onRouterDetectParameters');
    }

    public function shutdown()
    {
        $this->post_params =
        $this->get_params =
        $this->params =
        $this->cookies =
        $this->files =
        $this->accept_mimetype =
        $this->accept_language =
        $this->accept_encoding =
        $this->accept_charset =
            null;

        parent::shutdown();
    }

    public function getCustomRequestClone(array $get = null, array $post = null, array $user_params = null, array $cookies = null, array $files = null)
    {
        $reqc = clone $this;
        $reqc->setGetVars($get);
        $reqc->setPostVars($post);
        $reqc->setParamVars($user_params);

        // TODO: For a future implementation
        fnothing($cookies, $files);

        return $reqc;
    }

    public function getDebugInfo()
    {
        $debug_parent = parent::getDebugInfo();

        // compile cookies
        $c = $this->cookies;
        $ca = array();

        if ($c) {
            $c = $c->getArrayCopy();

            if ($c) {
                foreach ($c as $cc) {
                    $ca[$cc->getName()] = $cc->getValue();

                    unset($cc);
                }
            }

            unset($c);
        }

        $debug = array(
            'method' => $this->request_method,
            'accept_mimetype' => $this->accept_mimetype,
            'accept_language' => $this->accept_language,
            'accept_encoding' => $this->accept_encoding,
            'accept_charset' => $this->accept_charset,
            'protocol' => $this->protocol,
            'protocol_ver' => $this->protocol_ver,
            'cookies' => ($ca ? $ca : null),
            'uploaded_files_count' => (is_array($this->files) ? count($this->files) : null),
            'params' => ($this->params ? $this->params->getKeyValueArray() : null),
            'post_params' => ($this->post_params ? $this->post_params->getKeyValueArray() : null),
            'get_params' => ($this->get_params ? $this->get_params->getKeyValueArray() : null)
        );

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    #pragma mark - iKeyValueProvider

    public function getAllKeys()
    {
        $keys = (array)parent::getAllKeys();
        $nk = array(
            'url_prefix' => $this->prefix,
            'full_hostname' => $this->getFullHostname(),
            'base_url' => $this->getBaseUrl(),
            'remote_addr' => $this->getRealRemoteAddr()
        );
        $ret = array_filter(array_merge($keys, $nk));
        return $ret;
    }

    public function getValueForKey($key)
    {
        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        if ($key == 'url_prefix') {
            return $this->prefix;
        } elseif ($key == 'full_hostname') {
            return $this->getFullHostname();
        } elseif ($key == 'base_url') {
            return $this->getBaseUrl();
        } elseif ($key == 'remote_addr') {
            return $this->getRealRemoteAddr();
        } else {
            $ret = parent::getValueForKey($key);
            return $ret;
        }
    }

    protected function verifyEnv()
    {
        if (!isset($this->env['HTTP_HOST']) ||
            !isset($this->env['SERVER_NAME']) ||
            !isset($this->env['SERVER_ADDR']) ||
            !isset($this->env['SERVER_PORT']) ||
            !isset($this->env['REMOTE_ADDR']) ||
            !isset($this->env['DOCUMENT_ROOT']) ||
            !isset($this->env['SCRIPT_FILENAME']) ||
            !isset($this->env['REMOTE_PORT']) ||
            !isset($this->env['SERVER_PROTOCOL']) ||
            !isset($this->env['REQUEST_METHOD']) ||
            !isset($this->env['QUERY_STRING']) ||
            !isset($this->env['REQUEST_URI']) ||
            !isset($this->env['SCRIPT_NAME']) ||
            !isset($this->env['PATH_INFO']) ||
            !isset($this->env['PHP_SELF'])
        ) {
            throw new lcSystemException('Invalid request environment');
        }
    }

    public function serialize()
    {
        return serialize(array(
            $this->files,
            $this->post_params,
            $this->get_params,
            $this->env,
            $this->request_method,
            $this->accept_mimetype,
            $this->accept_language,
            $this->accept_encoding,
            $this->accept_charset,
            $this->cookies,
            $this->context
        ));
    }

    public function unserialize($serialized)
    {
        list(
            $this->files,
            $this->post_params,
            $this->get_params,
            $this->env,
            $this->request_method,
            $this->accept_mimetype,
            $this->accept_language,
            $this->accept_encoding,
            $this->accept_charset,
            $this->cookies,
            $this->context
            ) = unserialize($serialized);
    }

    public function onRouterDetectParameters(lcEvent $event)
    {
        $params = $event->getParams();

        assert(isset($params) && is_array($params));

        $request_params = isset($params['params']) ? $params['params'] : array();

        $processed_event = $this->event_dispatcher->filter(
            new lcEvent('request.filter_parameters', $this,
                array('context' => $this->context, 'parameters' => $params)
            ), array());

        if ($processed_event->isProcessed()) {
            $request_params = (array)$processed_event->getReturnValue();
        }

        $this->params = new lcArrayCollection($request_params);

        $this->event_dispatcher->notify(new lcEvent('request.load_parameters', $this, $request_params));

        unset($params);
    }

    private function setRequestFromContext(array $context)
    {
        $context_post_params = isset($context['post_params']) && ($context['post_params'] instanceof lcArrayCollection) ? $context['post_params'] :
            new lcArrayCollection();

        $context_get_params = isset($context['get_params']) && ($context['post_params'] instanceof lcArrayCollection) ? $context['get_params'] :
            new lcArrayCollection();

        $this->post_params = $context_post_params;
        $this->get_params = $context_get_params;

        // reset the context
        $this->setDefaultContext();
    }

    protected function setDefaultContext()
    {
        $this->context = array(
            'path_info' => parent::getPathInfo(),
            'post_params' => $this->post_params,
            'get_params' => $this->get_params,
            'prefix' => $this->prefix,
            'method' => $this->getMethod(),
            'format' => $this->isSecure() ? 'https' : 'http',
            'host' => $this->getHostname(),
            'is_secure' => $this->isSecure(),
            'is_xml_http_request' => $this->isXmlHttpRequest(),
            'request_uri' => parent::getRequestUri()
        );
    }

    public function setContext(array $context)
    {
        $this->context = $context;
    }

    public function getRequestContext()
    {
        return $this->context;
    }

    public function getPathInfo()
    {
        return $this->context['path_info'];
    }

    public function getRequestUri()
    {
        return $this->context['request_uri'];
    }

    /*
     * Override POST vars
    */
    public function setPostVars(array $vars = null)
    {
        $this->post_params = new lcArrayCollection($vars);
    }

    /*
     * Override GET vars
    */
    public function setGetVars(array $vars = null)
    {
        $this->get_params = new lcArrayCollection($vars);
    }

    /*
     * Override user based param vars
    */
    public function setParamVars(array $vars = null)
    {
        $this->params = new lcArrayCollection($vars);
    }

    public function setRequestMethod($request_method)
    {
        $this->request_method = (int)$request_method;
    }

    /*
     * Gets the uri prefix based on the current protocol type
    * Example: http://, https://
    */
    public function getProtoPrefix()
    {
        if ($this->protocol == self::HTTP_PROTO_HTTP) {
            return 'http://';
        } elseif ($this->protocol == self::HTTP_PROTO_HTTPS) {
            return 'https://';
        } else {
            return null;
        }
    }

    public function getForwardedFor()
    {
        return $this->env('HTTP_X_FORWARDED_FOR');
    }

    // TODO: Deprecated - remove in 1.5
    public function getXForwardedFor()
    {
        return $this->getForwardedFor();
    }

    // TODO: Deprecated - remove in 1.5
    public function getHttpXForwardedFor()
    {
        return $this->getForwardedFor();
    }

    // TODO: Deprecated - remove in 1.5
    public function getHttpReferer()
    {
        return $this->env('HTTP_REFERER');
    }

    /*
     * Gets the url protocol plus the hostname together
    */
    public function getBaseUrl()
    {
        return
            $this->getProtoPrefix() .
            $this->getHostname() .
            $this->prefix;
    }

    public function getUrlPrefix()
    {
        return $this->prefix;
    }

    public function getRequestPrefix()
    {
        return $this->getUrlPrefix();
    }

    /*
     * Gets the actual client remote address /skipping proxies/
    */
    public function getRealRemoteAddr($first = true)
    {
        if ($this->real_remote_addr) {
            $addr = ($first && is_array($this->real_remote_addr) && count($this->real_remote_addr) ?
                $this->real_remote_addr[0] : $this->real_remote_addr);
            return $addr;
        }

        if ($this->env('HTTP_CLIENT_IP')) {
            $ip = $this->env('HTTP_CLIENT_IP');
        } elseif ($this->env('HTTP_X_FORWARDED_FOR')) {
            $ip = $this->env('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->env('REMOTE_ADDR');
        }

        $this->real_remote_addr = strstr($ip, ',') ? array_filter(explode(',', $ip)) : $ip;

        $addr = ($first && is_array($this->real_remote_addr) && count($this->real_remote_addr) ?
            $this->real_remote_addr[0] : $this->real_remote_addr);
        return $addr;

        return $addr;
    }

    /*
     * Returns the a combined string of proto +
    * hostname
    */
    public function getFullHostname()
    {
        $res =
            $this->getProtoPrefix() .
            $this->getHostname() .
            $this->prefix;

        return $res;
    }

    /*
     * Get a custom header
    */
    public function header($prop_name)
    {
        if (!$prop_name) {
            return null;
        }

        $cv = strtoupper($prop_name);
        $cv = str_replace('-', '_', $cv);
        $cv = 'HTTP_' . $cv;
        return $this->env($cv);
    }

    /*
     * HTTP Header: IF_MODIFIED_SINCE
    */
    public function getIfModifiedSince()
    {
        return $this->env('HTTP_IF_MODIFIED_SINCE');
    }

    /*
     * HTTP Header: HTTP_CACHE_CONTROL
    */
    public function getCacheControl()
    {
        return $this->env('HTTP_CACHE_CONTROL');
    }

    /*
     * HTTP Header: HTTP_CONNECTION
    */
    public function getHttpConnection()
    {
        return $this->env('HTTP_CONNECTION');
    }

    /*
     * HTTP Header: HTTP_TRANSFER_ENCODING
    */
    public function getTransferEncoding()
    {
        return $this->env('HTTP_TRANSFER_ENCODING');
    }

    /*
     * HTTP Header: HTTP_VIA
    */
    public function getVia()
    {
        return $this->env('HTTP_VIA');
    }

    /*
     * HTTP Header: HTTP_CONTENT_LENGTH
    */
    public function getContentLength()
    {
        return $this->env('HTTP_CONTENT_LENGTH');
    }

    /*
     * HTTP Header: HTTP_CONTENT_TYPE
    */
    public function getContentType()
    {
        return $this->env('HTTP_CONTENT_TYPE');
    }

    /*
     * HTTP Header: HTTP_CONTENT_ENCODING
    */
    public function getContentEncoding()
    {
        return $this->env('HTTP_CONTENT_ENCODING');
    }

    /*
     * HTTP Header: HTTP_CONTENT_LANGUAGE
    */
    public function getContentLanguage()
    {
        return $this->env('HTTP_CONTENT_LANGUAGE');
    }

    /*
     * HTTP Header: HTTP_EXPIRES
    */
    public function getExpires()
    {
        return $this->env('HTTP_EXPIRES');
    }

    public function getUserAgent()
    {
        return $this->env('HTTP_USER_AGENT');
    }

    /*
     * HTTP Header: HTTP_LAST_MODIFIED
    */
    public function getLastModified()
    {
        return $this->env('HTTP_LAST_MODIFIED');
    }

    /*
     * Gets the REQUEST_METHOD after proper
    * Request initialization
    */
    public function getMethod()
    {
        return $this->request_method;
    }

    /*
     * Gets the Server Protocol after proper
    * Request initialization
    */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /*
     * Gets the Server Protocol Version after
    * proper Request initialization
    */
    public function getProtocolVer()
    {
        return $this->protocol_ver;
    }

    /*
     * Gets a parser object for
    * ACCEPT_MIMETYPE
    */
    public function getAcceptMimetype()
    {
        if (!$this->accept_mimetype) {
            $this->accept_mimetype = new lcHttpAcceptParser(
                lcHttpAcceptType::ACCEPT_MIMETYPES, $this->env('HTTP_ACCEPT'));
        }

        return $this->accept_mimetype;
    }

    /*
     * Gets a parser object for
    * ACCEPT_LANGUAGE
    */
    public function getAcceptLanguage()
    {
        if (!$this->accept_language) {
            $this->accept_language = new lcHttpAcceptParser(
                lcHttpAcceptType::ACCEPT_LANGUAGE, $this->env('HTTP_ACCEPT_LANGUAGE'));
        }

        return $this->accept_language;
    }

    /*
     * Gets a parser object for
    * ACCEPT_ENCODING
    */
    public function getAcceptEncoding()
    {
        if (!$this->accept_encoding) {
            $this->accept_encoding = new lcHttpAcceptParser(
                lcHttpAcceptType::ACCEPT_ENCODING, $this->env('HTTP_ACCEPT_ENCODING'));
        }

        return $this->accept_encoding;
    }

    /*
     * Gets a parser object for
    * ACCEPT_CHARSET
    */
    public function getAcceptCharset()
    {
        if (!$this->accept_charset) {
            $this->accept_charset = new lcHttpAcceptParser(
                lcHttpAcceptType::ACCEPT_CHARSET, $this->env('HTTP_ACCEPT_CHARSET'));
        }

        return $this->accept_charset;
    }

    /*
     * Raw apache request headers
    * Platform-Specific
    */
    public function getApacheHeaders()
    {
        static $cached_headers;

        if ($cached_headers) {
            return $cached_headers;
        } elseif (!function_exists('apache_request_headers')) {
            $srv = $_SERVER;

            if ($srv) {
                $ar = array();

                foreach ($srv as $k => $v) {
                    if (substr($k, 0, 5) != 'HTTP_') {
                        continue;
                    }

                    $k = str_replace('HTTP_', '', $k);
                    $k = strtolower($k);
                    $k = explode('_', $k);

                    $o = array();

                    foreach ($k as $f) {
                        $o[] = ucfirst($f);

                        unset($f);
                    }

                    $k = implode('-', $o);

                    $ar[$k] = $v;

                    unset($k, $v, $o);
                }

                $cached_headers = $ar;

                $ret = $cached_headers;
            }
        } else {
            $ret = apache_request_headers();
        }

        return $ret;
    }

    /*
     * Checks if the request method is POST
    */
    public function isPost()
    {
        return ($this->request_method == lcHttpMethod::METHOD_POST);
    }

    /*
     * Checks if the request method is GET
    */
    public function isGet()
    {
        return ($this->request_method == lcHttpMethod::METHOD_GET);
    }

    /*
     * Checks if the request is a XMLHttpRequest
    */
    public function isXmlHttpRequest()
    {
        return ($this->env('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    public function isAjaxRequest()
    {
        return $this->isXmlHttpRequest();
    }

    public function isAjax()
    {
        return $this->isXmlHttpRequest();
    }

    /*
     * Checks if the current connection is running
    * under SSL - HTTPS
    */
    public function isSecure()
    {
        return ($this->protocol == self::HTTP_PROTO_HTTPS);
    }

    /*
     * Gets a cookies object after
    * Request initialization
    */
    public function getCookies()
    {
        return $this->cookies;
    }

    /*
     * Gets a single cookie object
    */
    public function getCookie($name)
    {
        return $this->cookies->get($name);
    }

    /*
     * Gets the value of a cookie
    */
    public function getCookieValue($name)
    {
        $cookie = $this->cookies->get($name);
        $value = $cookie ? $cookie->getValue() : null;
        return $value;
    }

    /*
     * Checks if the Request has files
    * uploaded
    */
    public function hasFiles()
    {
        if (!$this->files) {
            return false;
        }

        return $this->files->count() ? true : false;
    }

    /*
     * Gets the files object after
    * Request initialization
    */
    public function getFiles()
    {
        return $this->files;
    }

    /*
     * Gets the POST params object after
    * Request initialization
    */
    public function getPostParams()
    {
        return $this->post_params;
    }

    /*
     * Gets the GET params object after
    * Request initialization
    */
    public function getGetParams()
    {
        return $this->get_params;
    }

    protected function initContext()
    {
        // set context and send a context filter event
        // to allow others to rewrite the context / get / post params

        // first - initialize the context with the current vars
        $this->setDefaultContext();

        // send a filter event to allow others to change the context
        $evn = $this->event_dispatcher->filter(new lcEvent('request.set_context', $this), $this->context);
        $context = $evn->getReturnValue();

        // set returned vars into the request
        if ($evn->isProcessed()) {
            $this->setRequestFromContext($context);

            // reverify the environment
            $this->verifyEnv();
        }

        assert(isset($this->context));
    }

    protected function initParams()
    {
        // init request parameters
        // send a filter event to allow others to change them
        $this->params = new lcArrayCollection();
    }

    /*
     * Initialization of REQUEST FILES
    */
    protected function initHttpFiles()
    {
        $files = new lcHttpFilesCollection();

        if (isset($_FILES)) {
            foreach ($_FILES as $key => $file) {
                # in case of many files in one form
                # posted as array - file[3]
                if (is_array($file['name'])) {
                    $file['key'] = $key;
                    $file['name'] = (array)$file['name'];
                    $file['type'] = (array)$file['type'];
                    $file['tmp_name'] = (array)$file['tmp_name'];
                    $file['error'] = (array)$file['error'];
                    $file['size'] = (array)$file['size'];

                    foreach ($file['size'] as $key => $filesize) {
                        if ($filesize < 1) {
                            continue;
                        }

                        $files->append(
                            new lcHttpFile(
                                $file['key'],
                                $file['name'][$key],
                                $file['tmp_name'][$key],
                                $file['error'][$key],
                                $file['size'][$key],
                                $file['type'][$key],
                                $key
                            )
                        );
                    }
                } else {
                    if ($file['size'] < 1) {
                        continue;
                    }

                    $files->append(
                        new lcHttpFile(
                            $key,
                            $file['name'],
                            $file['tmp_name'],
                            $file['error'],
                            $file['size'],
                            $file['type']
                        )
                    );
                }
            }
        }

        $this->files = $files;
    }

    /*
     * Sets the correct HTTP_METHOD
    */
    private function initHttpMethod()
    {
        if ($this->env('REQUEST_METHOD') == 'POST') {
            $this->request_method = lcHttpMethod::METHOD_POST;
        } elseif ($this->env('REQUEST_METHOD') == 'GET') {
            $this->request_method = lcHttpMethod::METHOD_GET;
        } elseif ($this->env('REQUEST_METHOD') == 'PUT') {
            $this->request_method = lcHttpMethod::METHOD_PUT;
        } elseif ($this->env('REQUEST_METHOD') == 'HEAD') {
            $this->request_method = lcHttpMethod::METHOD_HEAD;
        } else {
            $this->request_method = null;
        }
    }

    /*
     * Cookies Initialization
    */
    protected function initCookies()
    {
        $this->cookies = new lcCookiesCollection();

        if ($_COOKIE) {
            foreach ((array)$_COOKIE as $key => $value) {
                $this->cookies->append(new lcCookie($key, $value));
            }
        }
    }

    /*
     * clears all input vars after saving in the request
    */
    protected function resetAllGlobals()
    {
        $HTTP_POST_VARS = $HTTP_POST_FILES = $HTTP_GET_VARS = $HTTP_COOKIE_VARS = $HTTP_ENV_VARS = $HTTP_SERVER_VARS = null;
        $_POST = $_FILES = $_GET = null;
        //$_SERVER = $_ENV = $_REQUEST = null; we leave these for compatibility with 3rd party software

        fnothing($HTTP_POST_VARS, $HTTP_POST_FILES, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_ENV_VARS, $HTTP_SERVER_VARS);
    }

    /*
     * resets old style php global vars
    */
    protected function resetPHPRequestGlobals()
    {
        $HTTP_POST_VARS = $_POST;
        $HTTP_POST_FILES = $_FILES;
        $HTTP_GET_VARS = $_GET;
        $HTTP_COOKIE_VARS = $_COOKIE;
        $HTTP_ENV_VARS = $_ENV;
        $HTTP_SERVER_VARS = $_SERVER;

        fnothing($HTTP_POST_VARS, $HTTP_POST_FILES, $HTTP_GET_VARS, $HTTP_COOKIE_VARS, $HTTP_ENV_VARS, $HTTP_SERVER_VARS);
    }

    public function getUriPath()
    {
        return $this->uri_as_path;
    }

    /*
     * Sets a clear path version of the url - internal
    */
    private function _set_request_uri($in_url = null, $app_url = null)
    {
        if (!isset($app_url)) {
            $app_url = $this->app_url;
        }

        if (!isset($in_url)) {
            $checkin = array('HTTP_X_REWRITE_URL', 'REQUEST_URI', 'argv');

            foreach ($checkin as $var) {
                if ($uri = $this->env($var)) {
                    if ($var == 'argv') {
                        $uri = $uri[0];
                    }
                    break;
                }

                unset($var);
            }

            unset($checkin);
        } else {
            $uri = $in_url;
        }

        $base = preg_replace('/^\//', '', '' . $in_url);

        if ($base) {
            $uri = preg_replace('/^(?:\/)?(?:' . preg_quote($base, '/') . ')?(?:url=)?/', '', $uri);
        }

        unset($base);

        $uri = preg_replace('/^(?:\/)?(?:index\.php)?(?:\/)?(?:\?)?(?:url=)?/', '', $uri);

        if ($this->app_server == 'IIS' && !empty($uri)) {
            if (key($_GET) && strpos(key($_GET), '?') !== false) {
                unset($_GET[key($_GET)]);
            }

            $uri = preg_split('/\?/', $uri, 2);

            if (isset($uri[1])) {
                parse_str($uri[1], $_GET);
            }

            $uri = $uri[0];
        } elseif (empty($uri) && is_string($this->env('QUERY_STRING'))) {
            $uri = $this->env('QUERY_STRING');
        }

        if (strpos($uri, 'index.php') !== false) {
            list(, $uri) = explode('index.php', $uri, 2);
        }

        if (empty($uri) || $uri == '/' || $uri == '//') {
            return '';
        }

        $this->uri_as_path = str_replace('//', '/', '/' . $uri);

        unset($uri);

        if (substr($this->uri_as_path, strlen($this->uri_as_path) - 1, strlen($this->uri_as_path)) == '/') {
            $this->uri_as_path = substr($this->uri_as_path, 0, strlen($this->uri_as_path) - 1);
        }
    }
}

?>