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

class lcException extends Exception implements iDomainException
{
    const DEFAULT_DOMAIN = 'com.lightcast.generic';

    const SEVERITY_LEVEL_CRIT = 0;
    const SEVERITY_LEVEL_ERROR = 1;
    const SEVERITY_LEVEL_WARNING = 2;

    protected $cause;
    protected $content_type;

    protected $domain;
    protected $extra_data;

    /**
     * @var int
     */
    protected $severity = self::SEVERITY_LEVEL_CRIT;

    /**
     * @var array
     */
    protected $options;

    public function __construct($message = null, $code = null, Exception $cause = null, $extra_data = null, $domain = null)
    {
        $message = $message ? $message : '';
        $code = $code ? $code : 0;

        // workaround some custom handlers
        if (is_string($code)) {
            $message .= ' (' . $code . ')';
            $code = 0;
        }

        if (isset($extra_data)) {
            $this->extra_data = $extra_data;
        }

        $this->domain = isset($domain) ? $domain : self::DEFAULT_DOMAIN;

        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, $code, $cause);
        } else {
            parent::__construct($message, $code);
        }
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return lcException
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    public function getExtraData()
    {
        return $this->extra_data;
    }

    public function setExtraData($data)
    {
        $this->extra_data = $data;
    }

    /**
     * Get the previous Exception
     * We can't override getPrevious() since it's final
     *
     * @return Exception The previous exception
     */
    public function getCause()
    {
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            return $this->getPrevious();
        } else {
            return $this->cause;
        }
    }

    /**
     * @param int $severity
     * @return lcException
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * @return int
     */
    public function getSeverity()
    {
        return $this->severity;
    }
}