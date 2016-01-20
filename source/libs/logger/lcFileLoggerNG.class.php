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
 * @changed $Id: lcFileLoggerNG.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcFileLoggerNG extends lcLogger
{
    const DEFAULT_LOG_BUFFER_SIZE = 200;
    /** @var lcSysLog */
    protected $syslog;
    protected $log_to_syslog;
    protected $syslog_priority;
    protected $syslog_severity;
    protected $syslog_facility;
    protected $syslog_prefix;
    protected $saved_request_header;
    protected $request_header_has_been_outputed;

    protected $logs;
    protected $files;
    protected $channels;
    protected $dont_log_request_header;

    protected $request;

    // flush log buffers at each 200 logs
    private $is_logging;
    private $is_in_cli;
    private $buffered_logging = true;
    private $buffered_logging_threshold = self::DEFAULT_LOG_BUFFER_SIZE;
    private $buffered_logs = array();
    private $bufferend_logs_count = 0;

    public function initialize()
    {
        parent::initialize();

        if ($this->configuration['logger.enabled']) {
            $logs = array_filter((array)$this->configuration['logger.log_files']);

            // walk log files
            if ($logs && is_array($logs)) {
                $this->setupLogFiles($logs);

                if (isset($this->logs) && is_array($this->logs) && count($this->logs)) {
                    $this->is_logging = true;
                }
            }
        }

        // disable buffering under cli
        $this->is_in_cli = lcSys::isRunningCLI();

        if ($this->is_in_cli) {
            $this->buffered_logging = false;
        }

        // syslog
        $this->initSyslog();

        $this->dont_log_request_header = (bool)$this->configuration['logger.no_request_header'];

        $this->event_dispatcher->connect('request.startup', $this, 'onRequestStart');
    }

    private function setupLogFiles(array $log_files)
    {
        $log_files = array_filter($log_files);

        assert($log_files);

        foreach ($log_files as $filename => $log_info) {
            try {
                $severity = null;
                $channel = null;

                // if generic or other channel
                if (is_array($log_info)) {
                    // walk all log files under the channel
                    foreach ($log_info as $filename2 => $log_info2) {
                        $channel = $filename;
                        $filename = $filename2;
                        $severity = $log_info2;

                        $this->setupLogFile($channel, $filename, $severity);

                        unset($filename2, $log_info2);
                    }
                } else {
                    $severity = $log_info;
                    $channel = self::DEFAULT_CHANNEL;

                    $this->setupLogFile($channel, $filename, $severity);
                }
            } catch (Exception $e) {
                throw new lcSystemException('Error while setting up logger log file: ' . $filename . ': ' . $e->getMessage(), $e->getCode(), $e);
            }

            unset($filename, $log_info, $severity, $channel);
        }
    }

    private function setupLogFile($channel, $filename, $severity)
    {
        $channel = (string)$channel;
        $filename = (string)$filename;
        $severity = trim((string)$severity);

        if (!$level = self::strToErrType($severity)) {
            throw new lcConfigException('Invalid severity specified: ' . $severity);
        }

        // disable debug modes if not debugging
        if (!DO_DEBUG && $level == self::LOG_DEBUG) {
            return;
        }

        $full_filename = $this->configuration->getLogDir() . lcMisc::appendPathPrefix($filename);

        // check if writable
        $dirname = dirname($full_filename);

        if (!is_dir($dirname)) {
            lcDirs::create($dirname, true);
        }

        $next = count($this->files);

        $this->files[$next] = array('channel' => $channel, 'filename' => $full_filename);

        $logs = array(
            self::LOG_ALERT => array(),
            self::LOG_CRIT => array(),
            self::LOG_DEBUG => array(),
            self::LOG_EMERG => array(),
            self::LOG_ERR => array(),
            self::LOG_INFO => array(),
            self::LOG_NOTICE => array(),
            self::LOG_WARNING => array(),
        );

        foreach ($logs as $key => $val) {
            // check modifier
            if ((($key == $level)) || ($key <= $level)) {
                $this->logs[$key][] = $next;
            }

            unset($key, $val);
        }

        // add to channels
        $this->channels[$channel] = $channel;

        unset($logs);
    }

    private function initSyslog()
    {
        $this->log_to_syslog = (bool)$this->configuration['logger.syslog.enabled'];
        $this->syslog_prefix = (string)$this->configuration['logger.syslog.prefix'];

        $this->syslog_severity = self::strToErrType((string)$this->configuration['logger.syslog.severity']);

        if (!$this->syslog_severity) {
            $this->syslog_severity = self::LOG_INFO;
        }

        /*
         * The following two configurations use the integrated PHP constants for logging
        */
        try {
            if ($this->configuration['logger.syslog.priority']) {
                $this->syslog_priority = constant((string)$this->configuration['logger.syslog.priority']);
            }

            $this->syslog_priority = $this->syslog_priority ? $this->syslog_priority : LOG_INFO;

            if ($this->configuration['logger.syslog.facility']) {
                $this->syslog_facility = constant((string)$this->configuration['logger.syslog.facility']);
            }

            $this->syslog_facility = $this->syslog_facility ? $this->syslog_facility : lcSysLog::DEFAULT_FACILITY;
        } catch (Exception $e) {
            throw new lcConfigException('Could not set syslog configuration - probably wrongly specified facility / priority: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!$this->syslog_priority) {
            $this->syslog_priority = LOG_INFO;
        }

        if ($this->log_to_syslog) {
            $this->syslog = new lcSysLog();
        }
    }

    public function onAppException(lcEvent $event)
    {
        // flush the logs upon an exception
        try {
            $this->flushLogs();
        } catch (Exception $e) {
            error_log('Could not flush logs on app exception: ' . $e);
        }

        parent::onAppException($event);
    }

    public function flushLogs()
    {
        // write buffered logs
        $logs = $this->buffered_logs;

        if ($logs) {
            $max_len = (int)ini_get('log_errors_max_len');

            foreach ($logs as $filename => $logs1) {
                $str = implode('', $logs1);
                $last_pos = 0;

                while ($sub = substr($str, $last_pos, $max_len)) {
                    error_log($sub, 3, $filename);
                    $last_pos += strlen($sub);
                    unset($sub);
                }

                unset($filename, $logs1, $str);
            }
        }

        // wipe them out now
        $this->bufferend_logs_count = 0;
        $this->buffered_logs = array();
    }

    public function shutdown()
    {
        // close syslog if open
        $this->syslog = null;

        try {
            $this->flushLogs();
        } catch (Exception $e) {
            error_log('Could not flush logs on shutdown: ' . $e);
        }

        parent::shutdown();
    }

    public function setEnableBufferedLogging($enabled, $flush_at = self::DEFAULT_LOG_BUFFER_SIZE)
    {
        $this->buffered_logging = $enabled;
        $this->buffered_logging_threshold = (int)$flush_at;

        if (!$enabled) {
            $this->flushLogs();
        }
    }

    public function onRequestStart(lcEvent $event)
    {
        // if logged or not from config
        if ($this->dont_log_request_header) {
            return;
        }

        $this->request = $event->getSubject();

        assert(isset($this->request));

        if ($this->request instanceof lcWebRequest) {
            /** @var lcWebRequest $request */
            $request = $this->request;

            // initialize new log buffer
            $buffer = array();
            $buffer[] = '';
            $buffer[] = '******************** REQUEST START [' . $this->getTimeTick() . ']';
            $buffer[] = '';

            // intro line
            $buffer[] =
                $this->request->getRealRemoteAddr() . ' ' .
                ($request->getXForwardedFor() ? '*-> ' . $request->getXForwardedFor() . '* ' : null) .
                '[' . date('r', $request->getRequestTime()) . '] ' .
                '[' . $this->configuration->getApplicationName() . '] ' .
                ($this->request->isXmlHttpRequest() ? '[XML-HTTP] ' : null) .
                '"' . $request->getRequestMethod() . ' ' . $request->getRequestUri() . '" ' .
                '"' . ($request->getHttpReferer() ? $request->getHttpReferer() : '-') . '" ' .
                '"' . ($request->getHttpUserAgent() ? $request->getHttpUserAgent() : '-') . '"';

            // params

            // GET
            $tmpstr = (string)$this->request->getGetParams();

            if ($tmpstr) {
                $buffer[] = 'Get: {' . $tmpstr . '}';
            }

            // POST
            $tmpstr = (string)$this->request->getPostParams();

            if ($tmpstr) {
                $buffer[] = 'Post: {' . $tmpstr . '}';
            }

            // COOKIES
            $tmpstr = (string)$this->request->getCookies();

            if ($tmpstr) {
                $buffer[] = 'Cookies: {' . $tmpstr . '}';
            }

            // FILES
            $tmpstr = (string)$this->request->getFiles();

            if ($tmpstr) {
                $buffer[] = 'Files: {' . $tmpstr . '}';
            }

            // ROUTING
            $tmpstr = (string)$this->request->getParams();

            if ($tmpstr) {
                $buffer[] = 'Route: {' . $tmpstr . '}';
            }

            // headers
            $headers = $this->request->getApacheHeaders();

            if ($headers) {
                $buffer[] = '';

                foreach ($headers as $key => $val) {
                    $buffer[] = $key . ': ' . $val;

                    unset($key, $val);
                }
            }

            $interm = implode("\r\n", $buffer) . "\r\n\r\n";

            unset($buffer);

            $this->saved_request_header = $interm;
        }
    }

    private function getTimeTick()
    {
        $m = explode(' ', microtime());
        $mili = substr($m[0], 1, strlen($m[0]) - 6);

        return date('d/m/Y H:i:s', $m[1]) . $mili;
    }

    public function log($message, $severity = null, $channel = null)
    {
        $this->logExtended($message, $severity, null, false, false, $channel);
    }

    public function logExtended($message, $severity = null, $filename = null, $ignore_severity_check = false, $cleartext = false, $channel = null)
    {
        if (!$this->is_logging) {
            return;
        }

        $message = (string)$message;
        $severity = (int)$severity;
        $channel = (string)$channel;

        if (!$message || !$severity) {
            return;
        }

        if ($ignore_severity_check) {
            $severity = null;
        }

        if (!$ignore_severity_check && !isset($this->logs[$severity])) {
            return;
        }

        try {
            $this->internalLog($message, $severity, $filename, $cleartext, $channel);
        } catch (Exception $e) {
            if (DO_DEBUG) {
                error_log('Logger error: ' . $e);
            }
        }
    }

    private function internalLog($string, $severity = null, $custom_file = null, $cleartext = false, $channel = null, $logged_channel_name = null)
    {
        if (!isset($string)) {
            return;
        }

        if (!is_string($string)) {
            $string = var_export($string, true);
        }

        $severity = isset($severity) ? $severity : self::DEFAULT_SEVERITY;

        if (!isset($this->logs[$severity])) {
            return;
        }

        $channels1 = $this->channels;

        $write_channel_name_in_log = false;
        $channel_faked = null;

        // if the channel at which the log is to be written
        // is not setup - we will log to GENERIC instead
        if (!isset($channels1[$channel])) {
            $channel_faked = self::DEFAULT_CHANNEL;
            $write_channel_name_in_log = true;
        }

        if (!$cleartext) {
            if ($channel == self::DEFAULT_CHANNEL || $write_channel_name_in_log) {
                // add the channel name in the generic channel
                $ch = isset($logged_channel_name) ? $logged_channel_name : $channel;
                $ch = $ch ? $ch : self::DEFAULT_CHANNEL;

                $string_log = sprintf('%-17s %-12s %-10s %s',
                    '[' . $this->getTimeTick() . ']',
                    '{' . $ch . '}',
                    self::errTypeToStr($severity),
                    $string
                );

                unset($ch);
            } else {
                $string_log = sprintf('%-17s %-10s %s',
                    '[' . $this->getTimeTick() . ']',
                    self::errTypeToStr($severity),
                    $string
                );
            }
        } else {
            $string_log = $string;
        }

        $string_log .= "\r\n";

        $files1 = $this->files;
        $logs1 = $this->logs;

        // set the faked name - if channel is not set
        $channel = $channel_faked ? $channel_faked : $channel;

        // first log to the GENERIC channel if channel is not it
        if ($channel != self::DEFAULT_CHANNEL && !$custom_file) {
            // it's important that we wrap this call here in try catch - otherwise a circular
            // logging may occur
            try {
                $this->internalLog($string, $severity, $custom_file, $cleartext, self::DEFAULT_CHANNEL, $channel);
            } catch (Exception $e) {
                if (DO_DEBUG) {
                    // in case of looping errors - exit right away
                    die('Internal log error: ' . $e);
                }

                assert(false);
            }
        }

        // log to all files
        if ($files1) {
            $files = null;

            if (!isset($severity)) {
                $files = $files1;
            } else {
                $files = isset($logs1[$severity]) ? $logs1[$severity] : array();
                $tofind = true;
            }

            foreach ($files as $file) {
                if (isset($tofind)) {
                    $file = isset($files1[$file]) ? $files1[$file] : null;
                }

                if (!$file) {
                    break;
                }

                $filename = $file['filename'];
                $fchannel = $file['channel'];

                if ($fchannel != $channel) {
                    continue;
                }

                // first send the request header if not sent already
                if (!isset($this->request_header_has_been_outputed[$filename]) && $this->saved_request_header) {
                    $this->request_header_has_been_outputed[$filename] = true;

                    if ($this->buffered_logging) {
                        $this->buffered_logs[$filename][] = $this->saved_request_header;
                        $this->bufferend_logs_count++;

                        // flush logs if necessary
                        if ($this->bufferend_logs_count >= $this->buffered_logging_threshold) {
                            $this->flushLogs();
                        }
                    } else {
                        if (!error_log($this->saved_request_header, 3, $filename) && DO_DEBUG) {
                            throw new lcSystemException('Could not log request header');
                        }
                    }
                }

                if ($this->buffered_logging) {
                    $this->buffered_logs[$filename][] = $string_log;
                    $this->bufferend_logs_count++;

                    // flush logs if necessary
                    if ($this->bufferend_logs_count >= $this->buffered_logging_threshold) {
                        $this->flushLogs();
                    }
                } else {
                    if (!error_log($string_log, 3, $filename) && DO_DEBUG) {
                        throw new lcSystemException('Could not log message');
                    }
                }

                unset($file, $filename);
            }

            unset($files);
        }

        // syslog logging
        if ($this->log_to_syslog && $this->syslog_severity >= $severity) {
            assert(isset($this->syslog));

            if (!$this->logToSyslog($string_log, $this->syslog_priority, $this->syslog_facility, $this->syslog_prefix)) {
                throw new lcSystemException('Could not log message to syslog');
            }
        }

        // custom file
        if (isset($custom_file)) {
            $f = lcMisc::isPathAbsolute($custom_file) ?
                $custom_file :
                $this->configuration->getLogDir() . lcMisc::appendPathPrefix($custom_file);

            error_log($string_log, 3, $f);

            unset($f);
        }
    }

    public function logToSyslog($message, $priority = null, $facility = null, $prefix = null)
    {
        if (!$this->syslog) {
            return false;
        }

        return $this->syslog->log($message, $priority, $facility, $prefix);
    }
}
