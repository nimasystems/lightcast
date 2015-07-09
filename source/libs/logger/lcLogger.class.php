<?php

/**
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
abstract class lcLogger extends lcResidentObj implements iLoggable, iProvidesCapabilities
{
    const LOG_EMERG = 1;

    /*
     * A constant defining 'System is unusuable' logging level
    */
    const LOG_ALERT = 2;

    /*
     * A constant defining 'Immediate action required' logging level
    */
    const LOG_CRIT = 3;

    /*
     * A constant defining 'Critical conditions' logging level
    */
    const LOG_ERR = 4;

    /*
     * A constant defining 'Error conditions' logging level
    */
    const LOG_WARNING = 5;

    /*
     * A constant defining 'Warning conditions' logging level
    */
    const LOG_NOTICE = 6;

    /*
     * A constant defining 'Normal but significant' logging level
    */
    const LOG_INFO = 7;

    /*
     * A constant defining 'Informational' logging level
    */
    const LOG_DEBUG = 8;

    /*
     * A constant defining 'Debug-level messages' logging level
    */
    const DEFAULT_SEVERITY = 7;
    const DEFAULT_CHANNEL = 'generic';
    protected $severity;

    public static function errTypeToStr($error_type)
    {
        switch ($error_type) {
            case self::LOG_EMERG: {
                return 'emerg';
            }
            case self::LOG_ALERT: {
                return 'alert';
            }
            case self::LOG_CRIT: {
                return 'crit';
            }
            case self::LOG_ERR: {
                return 'err';
            }
            case self::LOG_WARNING: {
                return 'warning';
            }
            case self::LOG_NOTICE: {
                return 'notice';
            }
            case self::LOG_INFO: {
                return 'info';
            }
            case self::LOG_DEBUG: {
                return 'debug';
            }
            default: {
                return 'info';
            }
        }
    }

    public function initialize()
    {
        parent::initialize();

        // listen for exceptions
        $this->event_dispatcher->connect('app.exception', $this, 'onAppException');

        $this->severity = self::strToErrType($this->configuration['logger.severity']);

        // connect a logger listener - so everyone can log a message
        // through an event
        $this->event_dispatcher->connect('logger.log', $this, 'listenerLog');

        $this->severity = self::strToErrType($this->configuration['logger.severity']);
    }

    public static function strToErrType($error_type_str)
    {
        switch ($error_type_str) {
            case 'emerg': {
                return self::LOG_EMERG;
            }
            case 'alert': {
                return self::LOG_ALERT;
            }
            case 'crit': {
                return self::LOG_CRIT;
            }
            case 'err': {
                return self::LOG_ERR;
            }
            case 'error': {
                return self::LOG_ERR;
            }
            case 'warning': {
                return self::LOG_WARNING;
            }
            case 'notice': {
                return self::LOG_NOTICE;
            }
            case 'info': {
                return self::LOG_INFO;
            }
            case 'debug': {
                return self::LOG_DEBUG;
            }
            default: {
                return self::LOG_INFO;
            }
        }
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getCapabilities()
    {
        return array(
            'logger'
        );
    }

    public function onAppException(lcEvent $event)
    {
        try {
            $params = $event->params;

            $exception_message = $params['message'];
            $exception_domain = $params['domain'];
            $exception_code = $params['code'];
            $exception = $params['exception'];
            $exception_type = get_class($exception);
            //$cause = $params['cause'];
            //$trace = $params['trace'];

            $exception_message_full =
                'Unhandled exception: ' . $exception_type . ': ' .
                $exception_message .
                ' (' . ($exception_domain ? 'Domain: ' . $exception_domain . ', ' : null) . 'Code: ' . $exception_code . ')' . "\n";

            $this->crit($exception_message_full);
        } catch (Exception $e) {
            if (DO_DEBUG) {
                exit('Logger could not process exception: exception rethrown: ' . $e);
            }
        }
    }

    public function crit($message, $channel = null)
    {
        $this->log($message, self::LOG_CRIT, $channel);
    }

    public function getSeverity()
    {
        return $this->severity;
    }

    public function emerg($message, $channel = null)
    {
        $this->log($message, self::LOG_EMERG, $channel);
    }

    public function alert($message, $channel = null)
    {
        $this->log($message, self::LOG_ALERT, $channel);
    }

    public function err($message, $channel = null)
    {
        $this->log($message, self::LOG_ERR, $channel);
    }

    public function warning($message, $channel = null)
    {
        $this->log($message, self::LOG_WARNING, $channel);
    }

    public function notice($message, $channel = null)
    {
        $this->log($message, self::LOG_NOTICE, $channel);
    }

    public function info($message, $channel = null)
    {
        $this->log($message, self::LOG_INFO, $channel);
    }

    public function debug($message, $channel = null)
    {
        $this->log($message, self::LOG_DEBUG, $channel);
    }

    public function listenerLog(lcEvent $event)
    {
        $params = $event->getParams();

        if (!isset($params['message'])) {
            assert(false);
            return;
        }

        // get severity
        $severity = isset($params['severity']) ? (int)$params['severity'] : self::DEFAULT_SEVERITY;

        // get channel
        $channel = isset($params['channel']) ? (string)$params['channel'] : self::DEFAULT_CHANNEL;

        // clear text log string or formatted
        if (!isset($params['cleartext'])) {
            $subject = $event->getSubject();
            $class_name = isset($subject) ? get_class($subject) : '-';

            $message =
                sprintf('%-25s %s',
                    '{' . $class_name . '}',
                    $params['message']
                );

            $cleartext = false;
        } else {
            $message = $params['message'];
            $cleartext = true;
        }

        $filename = isset($params['filename']) ? $params['filename'] : null;
        $ignore_severity_check = isset($params['ignore_severity_check']) ? true : false;

        $this->logExtended(
            $message,
            $severity,
            $filename,
            $ignore_severity_check,
            $cleartext,
            $channel
        );

        unset($severity, $event);
    }
}

