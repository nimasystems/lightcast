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

class lcCurl extends lcObj
{
    const COOKIE_NAME = 'cookie.txt';
    const DEFAULT_TIMEOUT = 30;
    const DEFAULT_MAX_REDIRECTS = 4;
    protected $configuration;
    private $user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
    private $url;
    private $follow_location;
    private $timeout;
    private $max_redirects;
    private $cookie_file_location = self::COOKIE_NAME;
    private $post;
    private $post_fields;
    private $referer;
    //private $session;
    private $response;
    private $headers = array('Except:');
    private $include_header;
    private $nobody;
    private $http_status;
    private $binary_transfer;

    private $authentication;
    private $auth_name;
    private $auth_pass;

    private $has_requested;

    public function __construct($url)
    {
        assert(isset($url));

        $this->url = $url;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setConfiguration(lcConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getFollowLocation()
    {
        return $this->follow_location;
    }

    public function setFollowLocation($do_follow = true)
    {
        $this->follow_location = $do_follow;
    }

    public function getMaxRedirects()
    {
        return $this->max_redirects;
    }

    public function setMaxRedirects($max_redirects = self::DEFAULT_MAX_REDIRECTS)
    {
        $this->max_redirects = $max_redirects;
    }

    public function getBinaryTransfer()
    {
        return $this->binary_transfer;
    }

    public function setBinaryTransfer($binary_transfer = true)
    {
        $this->binary_transfer = $binary_transfer;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout($timeout = self::DEFAULT_TIMEOUT)
    {
        $this->timeout = $timeout;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setAuthentication($auth_name, $auth_pass)
    {
        $this->authentication = true;
        $this->auth_name = $auth_name;
        $this->auth_pass = $auth_pass;
    }

    public function unsetAuthentication()
    {
        $this->authentication = false;
    }

    public function hasAuthentication()
    {
        return $this->authentication;
    }

    public function getAuthName()
    {
        return $this->auth_name;
    }

    public function getAuthPassword()
    {
        return $this->auth_pass;
    }

    public function getReferer()
    {
        return $this->referer;
    }

    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    public function setPost(array $post_fields = null)
    {
        $this->post = true;
        $this->post_fields = isset($post_fields) ? $post_fields : array();
    }

    public function isPost()
    {
        return $this->post;
    }

    public function getPostFields()
    {
        return (array)$this->post_fields;
    }

    public function hasPostField($name)
    {
        return (is_array($this->post_fields) && isset($this->post_fields[$name]));
    }

    public function getUserAgent()
    {
        return $this->user_agent;
    }

    public function setUserAgent($user_agent)
    {
        $this->user_agent = $user_agent;
    }

    public function getHttpStatus()
    {
        return $this->http_status;
    }

    public function __toString()
    {
        return (string)$this->getResponse();
    }

    public function getResponse()
    {
        if (!$this->has_requested) {
            $this->makeRequest();
        }

        return $this->response;
    }

    public function makeRequest($url = null)
    {
        if ($url) {
            $this->url = $url;
        }

        assert(isset($this->configuration));

        // TODO: needs workaround!
        $this->cookie_file_location = $this->configuration->getTempDir() . DS . self::COOKIE_NAME;

        try {
            $s = curl_init();

            curl_setopt($s, CURLOPT_URL, $this->url);
            curl_setopt($s, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($s, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($s, CURLOPT_MAXREDIRS, $this->max_redirects);
            curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($s, CURLOPT_FOLLOWLOCATION, $this->follow_location);
            curl_setopt($s, CURLOPT_COOKIEJAR, $this->cookie_file_location);
            curl_setopt($s, CURLOPT_COOKIEFILE, $this->cookie_file_location);

            if ($this->authentication && $this->auth_name) {
                curl_setopt($s, CURLOPT_USERPWD, $this->auth_name . ':' . $this->auth_pass);
            }

            if ($this->post) {
                curl_setopt($s, CURLOPT_POST, true);
                curl_setopt($s, CURLOPT_POSTFIELDS, $this->getHttpPostFields());
            }

            if ($this->include_header) {
                curl_setopt($s, CURLOPT_HEADER, true);
            }

            if ($this->nobody) {
                curl_setopt($s, CURLOPT_NOBODY, true);
            }

            if ($this->binary_transfer) {
                curl_setopt($s, CURLOPT_BINARYTRANSFER, true);
            }

            if ($this->user_agent) {
                curl_setopt($s, CURLOPT_USERAGENT, $this->user_agent);
            }

            if ($this->referer) {
                curl_setopt($s, CURLOPT_REFERER, $this->referer);
            }

            $this->response = curl_exec($s);
            $this->http_status = curl_getinfo($s, CURLINFO_HTTP_CODE);

            curl_close($s);

            $this->has_requested = true;
        } catch (Exception $e) {
            throw new lcIOException('Communication error: ' . $e->getMessage(), null, $e);
        }

        return $this->response;
    }

    private function getHttpPostFields()
    {
        if (!$this->post_fields) {
            return null;
        }

        $ret = array();

        foreach ($this->post_fields as $name => $value) {
            $ret[] = $name . '=' . $value;

            unset($name, $value);
        }

        return implode('&', $ret);
    }

    public function setHeader($header)
    {
        $this->headers[] = $header;
    }

}