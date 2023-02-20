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

class lcErrorHandler extends lcResidentObj implements iProvidesCapabilities, iErrorHandler
{
    const AJAX_REQUEST_CONTENT_TYPE = 'application/json';

    /** @var lcController */
    protected $controller;

    /** @var lcMailer */
    protected $mailer;

    /** @var lcRequest */
    protected $request;

    /** @var lcResponse */
    protected $response;

    /** @var lcLogger */
    protected $logger;

    /** @var lcApp */
    protected $app_context;

    private $is_debugging;

    public function getListenerEvents(): array
    {
        return [
            'app.startup' => 'onAppStartup',
            'request.startup' => 'onRequestStartup',
            'response.startup' => 'onResponseStartup',
            'mailer.startup' => 'onMailerStartup',
            'controller.startup' => 'onControllerStartup',
            'error_handler.exception_notify' => 'onExceptionNotificationReported',
        ];
    }

    public function shutdown()
    {
        $this->request =
        $this->response =
        $this->controller =
        $this->mailer =
        $this->logger =
        $this->app_context =
            null;

        parent::shutdown();
    }

    public function getCapabilities(): array
    {
        return [
            'error_handler',
        ];
    }

    public function onExceptionNotificationReported(lcEvent $event)
    {
        $exception = isset($event->params['exception']) ? $event->params['exception'] : null;

        if ($exception) {
            $this->notifyOfException($exception);
        }
    }

    public function notifyOfException(Exception $exception)
    {
        if (!$exception) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $this->warning('Notifying of handled exception: ' . $exception);

        $should_send_email = !DO_DEBUG && (bool)$this->configuration['exceptions.mail.enabled'];
        $exception_min_severity = (int)$this->configuration['exceptions.mail.severity'];

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($should_send_email && (!$exception_min_severity || !($exception instanceof lcException) ||
                ($exception instanceof lcException && $exception->getSeverity() <= $exception_min_severity))) {
            try {
                $this->emailException($exception);
            } catch (exception $e) {
                if (DO_DEBUG) {
                    die('Cannot send email in exception handler: ' . $e);
                }

                return;
            }
        }

        $exception_code = $exception->getCode();
        $exception_message = $this->getExceptionOverridenMessage($exception);
        $exception_cause = ($exception instanceof lcException) ? $exception->getCause() : null;
        $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;

        $exception_trace = [];
        $this->getStackTrace($exception, $exception_trace);
        $exception_trace_str = $this->getTextTrace($exception_trace);

        $system_snapshot = $this->app_context ? $this->app_context->getDebugSnapshot() : null;

        $this->event_dispatcher->notify(new lcEvent('app.exception', $this,
            [
                'exception' => $exception,
                'message' => $exception_message,
                'domain' => $exception_domain,
                'code' => $exception_code,
                'cause' => $exception_cause,
                'trace' => $exception_trace_str,
                'system_snapshot' => $system_snapshot,
            ]));
    }

    public function emailException(Exception $exception)
    {
        if (!$exception) {
            return;
        }

        $configuration = $this->configuration;

        if (!$configuration) {
            return;
        }

        $recipient = (string)$configuration['exceptions.mail.recipient'];
        $recipient = $recipient ? $recipient : $configuration->getAdminEmail();

        if (!$recipient) {
            return;
        }

        $exinner = ($exception instanceof lcException) ? $exception->getCause() : $exception;
        $type = $exinner ? get_class($exinner) : get_class($exception);

        $skip_exceptions = (array)$configuration['exceptions.mail.skip_exceptions'];
        $only_exceptions = (array)$configuration['exceptions.mail.only_exceptions'];

        if ($skip_exceptions && in_array($type, $skip_exceptions)) {
            return;
        }

        if ($only_exceptions && !in_array($type, $only_exceptions)) {
            return;
        }

        // OK TO SEND IT

        $ipaddr = null;
        $hostname = '-';

        if (!$this->request) {
            $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();
        }

        $in_cli = lcSys::isRunningCLI();

        if (!$in_cli) {
            $application_name = $configuration->getApplicationName();

            if ($this->request instanceof lcWebRequest) {
                $ipaddr = $this->request->getRealRemoteAddr();
                $hostname = $this->request->getFullHostname();
            }
        } else {
            $application_name = 'cli';
        }

        $subject_prefix = strtoupper($configuration->getProjectName() . '/' . $configuration->getApplicationName());

        $exception_code = $exception->getCode();
        $exception_message = $exception->getMessage();
        $exception_cause = ($exception instanceof lcException) ? $exception->getCause() : null;
        $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;

        $exception_file = $exception->getFile();
        $exception_line = $exception->getLine();

        $exception_trace = [];
        $this->getStackTrace($exception, $exception_trace);
        $exception_trace_str = $this->getTextTrace($exception_trace);

        $subject = $subject_prefix . ' - Exception (' . $type . '): ' . substr($exception_message, 0, 60);

        $debug_snapshot = null;

        try {
            $debug_snapshot = $this->app_context ? $this->app_context->getDebugSnapshot(true) : null;
        } catch (Exception $e) {
            return;
        }

        $message =
            "<strong>Application:</strong> " . $application_name . "<br />\n" .
            (
            !$in_cli ?

                "<strong>Hostname:</strong> " . $hostname . "<br />\n" .
                "<strong>Client IP:</strong> " . $ipaddr . "<br />\n" : null) .

            "<strong>Date/Time of Event:</strong> " . date('d.m.y H:i:s') . "<br />\n" .
            "<strong>Exception:</strong> <cite>" . $type . "</cite> / Code: " . $exception_code . " (" . $exception_domain . "): " . "<br />\n" .
            "<strong>Filename:</strong> " . $exception_file . ' (' . $exception_line . ')' . "<br />\n" .
            "<strong>Message:</strong> " . $exception_message . "<br />\n<br />\n" .
            ($exception_cause ? "<strong>Exception Cause:</strong>: " . gettype($exception_cause) . "<br />\n" : null) .
            ($exception_trace_str ? "<strong>Stack trace:</strong> <br />\n<br />\n" . nl2br($exception_trace_str) . "<br />\n<br />\n" : null) .
            (isset($_GET) && $_GET ? "<strong>GET:</strong> <br />\n<br />\n" . ee($_GET, true) . "<br />\n" : null) .
            (isset($_POST) && $_POST ? "<strong>POST:</strong> <br />\n<br />\n" . ee($_POST, true) . "<br />\n" : null) .
            (isset($_SERVER) && $_SERVER ? "<strong>SERVER:</strong> <br />\n<br />\n" . ee($_SERVER, true) . "<br />\n" : null) .
            (isset($_REQUEST) && $_REQUEST ? "<strong>REQUEST:</strong> <br />\n<br />\n" . ee($_REQUEST, true) . "<br />\n" : null) .
            ($debug_snapshot ? "<strong>Debug Snapshot:</string> <br />\n<br />\n" . ee($debug_snapshot, true) . "<br /><br />\n\n" : null) .
            "----------------------------------------------------------------<br />\n" .
            "This is an automatically sent e-mail. Please, do not reply.<br />\n<br />\n";

        try {
            // send it using ordinary email if mailer is not available
            if ($this->mailer) {
                $this->mailer->sendMail([$recipient], $message, $subject);
            } else {
                $headers = 'From: ' . $recipient;
                @mail($recipient, $subject, $message, $headers);
            }
        } catch (Exception $e) {
            return;
        }
    }

    /**
     * @param Exception|Error $exception
     * @param array $compiled_stack_trace
     * @return array|null
     */
    private function getStackTrace($exception, array &$compiled_stack_trace): ?array
    {
        $previous = ($exception instanceof lcException) ? $exception->getCause() : null;

        $trace = ($previous !== null) ? $this->getStackTrace($previous, $compiled_stack_trace) :
            $exception->getTrace();

        if (!$trace) {
            return null;
        }

        foreach ($trace as $key => $trace1) {
            array_push($compiled_stack_trace, $trace1);
            unset($key, $trace1);
        }

        return $trace;
    }

    private function getTextTrace(array $full_trace, $return_array = false)
    {
        if (!$full_trace) {
            return false;
        }

        $txt_trace = [];

        $i = 0;

        foreach ($full_trace as $trace) {
            $txt_trace[] = $this->showTextTrace($trace, $i);

            ++$i;
            unset($trace);
        }

        return !$return_array ? implode("\n", $txt_trace) : $txt_trace;
    }

    private function showTextTrace($_trace, $_i): string
    {
        $htmldoc = ' #' . $_i . ' ';

        if (array_key_exists('file', $_trace)) {
            $htmldoc .= $_trace['file'];
        }

        if (array_key_exists('line', $_trace)) {
            $htmldoc .= '(' . $_trace["line"] . '): ';
        }

        if (array_key_exists('class', $_trace) && array_key_exists('type', $_trace)) {
            $htmldoc .= $_trace['class'] . $_trace['type'];
        }

        if (array_key_exists('function', $_trace)) {
            $htmldoc .= $_trace["function"] . '(';

            if (array_key_exists('args', $_trace)) {
                if (count($_trace['args']) > 0) {
                    $prep = [];

                    foreach ($_trace['args'] as $arg) {
                        $type = gettype($arg);
                        $value = $arg;
                        $str = '';

                        if ($type == 'boolean') {
                            if ($value) {
                                $str .= 'true';
                            } else {
                                $str .= 'false';
                            }
                        } else if ($type == 'integer' || $type == 'double') {
                            if (settype($value, 'string')) {
                                $str .= $value;
                            } else {
                                if ($type == 'integer') {
                                    $str .= '? integer ?';
                                } else {
                                    $str .= '? double or float ?';
                                }
                            }
                        } else if ($type == 'string') {
                            $str .= "'" . (strlen($value) > 50 ? substr($value, 0, 50) : $value) . "'";
                        } else if ($type == 'array') {
                            $str .= 'Array';
                        } else if ($type == 'object') {
                            $str .= 'Object';
                        } else if ($type == 'resource') {
                            $str .= 'Resource';
                        } else if ($type == 'NULL') {
                            $str .= 'null';
                        } else if ($type == 'unknown type') {
                            $str .= '? unknown type ?';
                        }

                        $prep[] = $str;

                        unset($type);
                        unset($value);
                        unset($arg);
                    }

                    if ($prep) {
                        $htmldoc .= implode(', ', $prep);
                    }
                }

                /*if (count($_trace['args']) > 1)
                 {
                $htmldoc .= implode(', ', $_trace['args']);
                //$htmldoc.= ',...';
                }*/
            }

            $htmldoc .= ')';
        }

        return $htmldoc;
    }

    /**
     * @param Exception|Error $exception
     * @param $content_type
     * @return string
     */
    private function getExceptionOverridenMessage($exception, $content_type = 'text/html'): string
    {
        $is_debugging = $this->is_debugging;
        $message = $exception->getMessage();

        // if overriden - append file / code
        if ($is_debugging) {
            $file = basename($exception->getFile());
            $split_filename = lcFiles::splitFileName($file);
            $code = $exception->getCode();
            $line = $exception->getLine();

            if ($content_type == 'text/html') {
                $message .= "\n\n" . '[' . $split_filename['name'] . ' / ' . $line . ($code ? ' / ' . $code : null) . ']';
            }
        }

        return $message;
    }

    public function onAppStartup(lcEvent $event)
    {
        $this->app_context = $event->subject;
    }

    public function onRequestStartup(lcEvent $event)
    {
        $this->request = $event->subject;
    }

    public function onControllerStartup(lcEvent $event)
    {
        $this->controller = $event->subject;
    }

    public function onResponseStartup(lcEvent $event)
    {
        $this->response = $event->subject;
    }

    public function onMailerStartup(lcEvent $event)
    {
        $this->mailer = $event->subject;
    }

    /**
     * @param Exception|Error $exception
     * @throws Exception
     */
    public function handleException($exception)
    {
        // PHP7 compat
//        if (!($exception instanceof Exception)) {
//            $exception = new ErrorException(
//                $exception->getMessage(),
//                $exception->getCode(),
//                E_ERROR,
//                $exception->getFile(),
//                $exception->getLine(),
//                $exception->getPrevious()
//            );
//        }

        $this->event_dispatcher->notify(new lcEvent('error_handler.exception', $this, [
            'exception' => $exception,
        ]));

        $should_send_email = !DO_DEBUG && (bool)$this->configuration['exceptions.mail.enabled'];
        $exception_min_severity = (int)$this->configuration['exceptions.mail.severity'];

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($should_send_email && (!$exception_min_severity || !($exception instanceof lcException) ||
                ($exception instanceof lcException && $exception->getSeverity() <= $exception_min_severity))) {
            try {
                $this->emailException($exception);
            } catch (exception $e) {
                if (DO_DEBUG) {
                    die('Cannot send email in exception handler: ' . $e);
                }
            }
        }

        $configuration = $this->configuration;
        $is_debugging = $this->is_debugging;
        $in_cli = lcSys::isRunningCLI();

        /** @var lcWebRequest $request */
        $request = $this->request;

        // check request Accept to determine if response should output html or something else
        $request_content_type = ($request && !$in_cli && $request instanceof lcWebRequest) ? $request->getAcceptMimetype()->getPreferred() : null;
        $request_content_type = $request_content_type && is_array($request_content_type) && count($request_content_type) ? $request_content_type[0] : null;

        /** @var lcWebResponse $response */
        $response = $this->response;

        $content_type = null;

        if ($request_content_type) {
            $content_type = $request_content_type;
        } else {
            $content_type = ($response && !$in_cli && $response instanceof lcWebResponse) ? $response->getContentType() :
                ($in_cli ? 'text/plain' : 'text/html');
        }

        // override content_type for XMLHTTP requests!
        if ($request && $request instanceof lcWebRequest && $request->isXmlHttpRequest()) {
            $content_type = isset($configuration['exceptions.ajax_content_type']) ?
                (string)$configuration['exceptions.ajax_content_type'] :
                self::AJAX_REQUEST_CONTENT_TYPE;
        }

        $exception_code = $exception->getCode();
        $exception_message = $this->getExceptionOverridenMessage($exception, $content_type);
        $exception_cause = ($exception instanceof lcException) ? $exception->getCause() : null;

        $exception_trace = [];
        $this->getStackTrace($exception, $exception_trace);
        $exception_trace_str = $this->getTextTrace($exception_trace);

        // exception domain
        $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;
        $exception_http_status_code = ($exception instanceof iHTTPException) ? $exception->getStatusCode() : 500;

        /*
         * In some cases we might not have a live configuration object
        * (early exception) - then we just output the error!
        */
        if (!isset($configuration)) {
            $exception_message_full = $is_debugging ?
                'Early exception (before configuration was initialized):' . "\n\n" .
                'Message: ' . $exception->getMessage() . "\n" .
                'Code: ' . $exception_code . "\n" .
                'Domain: ' . $exception_domain . "\n" .
                'Exception class: ' . get_class($exception) . "\n" .
                'Trace: ' . "\n\n" .
                $exception_trace_str :
                'System Error (e1)';

            $exception_message_full = !$in_cli ? nl2br($exception_message_full) : $exception_message_full;

            $this->sendResponse($exception_message_full, 4, 1, $exception_http_status_code);
        }

        try {
            $error_output = $this->prepareErrorOutput($exception, $content_type);

            $request = $this->request;

            /**
             * In case it's an early exception (before response / request have been started
             * then show the default output and exit manually
             */
            if (!isset($request)) {
                $this->sendResponse($error_output, $content_type, 3, $exception_http_status_code);
            }

            // custom application handler
            $exceptions_custom_module = (string)$configuration['exceptions.module'];
            $exceptions_custom_action = (string)$configuration['exceptions.action'];

            if (!DO_DEBUG && $request && !$in_cli && $request instanceof lcWebRequest &&
                $request->isGet() && (!$response || !$response->getIsResponseSent()) &&
                $exceptions_custom_module && $exceptions_custom_action
            ) {
                $front_controller = $this->controller;

                if ($front_controller) {
                    try {
                        $params = [
                            'exception' => get_class($exception),
                            'message' => $exception_message,
                            'domain' => $exception_domain,
                            'code' => $exception_code,
                            'cause' => $exception_cause,
                            'exception_object' => $exception,
                            'trace' => $exception_trace_str,
                        ];

                        $front_controller->forward($exceptions_custom_module, $exceptions_custom_action, ['request' => $params]);

                        // just in case
                        exit($exception_code);
                    } catch (Exception $e) {
                        $this->err('Could not forward to exception handler action (' .
                            $exceptions_custom_module . '/' . $exceptions_custom_action .
                            '): ' . $e->getMessage());
                    }
                }
            }

            // show the error
            // hide errors of outputing the header
            // as output may have already began (hide a potential error of sending headers)
            if (!$in_cli) {
                $exception_http_header = $configuration['settings.exception_http_header'];

                if ($exception_http_header && is_array($exception_http_header)) {
                    $is_enabled = $exception_http_header && isset($exception_http_header['enabled']) ? (bool)$exception_http_header['enabled'] : false;
                    $exception_http_header_header = isset($exception_http_header['header']) ? (string)$exception_http_header['header'] : null;

                    if ($is_enabled && $exception_http_header_header) {
                        @header($exception_http_header_header);
                    }
                }

                //@header('X-Powered-By: Lightcast PHP Framework - lightcast.nimasystems.com');
            }

            $this->sendResponse($error_output, $content_type, 1, $exception_http_status_code);
        } catch (Exception $e) {
            /*
             * If we are not debugging - hide the mechanics behind the message
            */
            $message = $this->is_debugging ?
                'An exception was raised in the exception handler: ' . $e->getMessage() . ', Code: ' . $e->getCode() :
                'System Error (' . $e->getCode() . ')';

            $this->sendResponse($message, $content_type, 2, $exception_http_status_code);
        }
    }

    private function sendResponse($content, $content_type, $exit_code = 1, $status_code = 200)
    {
        $response = $this->response;

        assert($exit_code > 0);

        http_response_code($status_code);

        if ($response) {
            // this might be an exception raised in the response send final moments
            // at this stage we can no longer 're-send' the response
            // so we directly flush the error output here
            if ($response->getIsResponseSent()) {
                // still show the last error
                echo $content;
                exit($exit_code);
            }

            try {
                if ($response instanceof lcWebResponse) {
                    $response->setStatusCode($status_code);
                }

                $response->removeObservers();
                $response->clear();
                $response->setExitCode($exit_code);
                $response->setContent($content);
                $response->setContentType($content_type);
                $response->sendResponse();
            } catch (Exception $e) {
                if (DO_DEBUG) {
                    echo 'Exception loop protection - error handler could not send the response properly: ' . "\n\n" . $e;
                    exit(1);
                }

                // still show the last error
                echo $content;
                exit($exit_code);
            }
        } else {
            echo $content;
        }

        exit($exit_code);
    }

    /**
     * @param Exception|Error $exception
     * @param $content_type
     * @return array|false|int|string|string[]|void
     */
    private function prepareErrorOutput($exception, $content_type)
    {
        $in_cli = lcSys::isRunningCLI();

        /*
         * We handle CLI without any template
        * for the sake of a clear terminal output
        */
        if ($in_cli) {
            $exception_message = $exception->getMessage();
            $exception_code = $exception->getCode();
            $exception_file = $exception->getFile();
            $exception_line = $exception->getLine();
            $exception_trace_str = $exception->getTraceAsString();
            $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;

            $output = null;

            if ($this->is_debugging) {
                $output =
                    '> ERR: ' . $exception_message . ' (' . ($exception_domain ? 'Domain: ' . $exception_domain . ', ' : null) . 'Code: ' . $exception_code . ')' . "\n" .
                    'File: ' . $exception_file . "\n" .
                    'Line: ' . $exception_line .
                    ($exception_trace_str ? ("\n\n" . $exception_trace_str) : null) . "\n";
            } else {
                $output = $exception_message . ' (' . $exception_code . ')' . "\n";
            }

            return $output;
        }

        // obtain the error template filename
        $fname = null;
        $template = null;
        $error_output = null;

        switch ($content_type) {
            case 'text/html':
            {
                $fname = 'html.err';
                break;
            }
            case 'text/xml':
            {
                $fname = 'xml.err';
                break;
            }
            case 'text/javascript':
            {
                $fname = 'js.err';
                break;
            }
            case 'text/css':
            {
                $fname = 'css.err';
                break;
            }
            case 'application/json':
            {
                $fname = null;
                $error_output = @json_encode(['error' => $this->getExceptionDetails($exception)]);

                if ($error_output && DO_DEBUG) {
                    $error_output = lcVars::indentJson($error_output);
                }

                break;
            }
            default:
            {
                $fname = 'txt.err';
                break;
            }
        }

        $libs_dir = $this->configuration->getLibsDir();

        assert(isset($libs_dir));

        if ($fname) {
            try {
                // if we are under a web server
                if ($this->is_debugging) {
                    $template = lcFiles::getFile($this->configuration->getLibsDir() . DS . 'exceptions' . DS . 'templates' . DS . 'debug' . DS . $fname);
                } else {
                    if (lcFiles::exists($this->configuration->getAppRootDir() . DS . 'lib' . DS . 'custom_errors' . DS . $fname)) {
                        $template = lcFiles::getFile($this->configuration->getAppRootDir() . DS . 'lib' . DS . 'custom_errors' . DS . $fname);
                    } else {
                        $template = lcFiles::getFile($this->configuration->getLibsDir() . DS . 'exceptions' . DS . 'templates' . DS . 'user' . DS . $fname);
                    }
                }
            } catch (Exception $e) {
                $this->err('Could not load error template (' . $template . '): ' . $e);
            }

            $error_output = $this->processTemplate($exception, $template, $content_type);

            $exception_full_str = $this->getExceptionOverridenMessage($exception) . ' (' . $exception->getCode() . ')';
            $error_output = $error_output ? $error_output : $exception_full_str;
        }

        assert(!is_null($error_output));

        return $error_output;
    }

    public function getExceptionDetails(Exception $exception): array
    {
        $configuration = $this->configuration;

        $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;

        $outmsg = $this->getExceptionOverridenMessage($exception);

        $details = [];
        $details['error_code'] = $exception->getCode();
        $details['error_subject'] = nl2br($outmsg);
        $details['domain'] = $exception_domain;
        $details['admin_email'] = $configuration->getAdminEmail();
        $details['lightcast_version'] = LIGHTCAST_VER;
        $details['php_version'] = lcSys::getPhpVer();
        $details['exception_name'] = get_class($exception);

        if (DO_DEBUG) {
            $details['file'] = $exception->getFile();
            $details['line'] = $exception->getLine();
            //$details['file_excerpt'] = $this->fileExcerpt($exception->getFile(), $exception->getLine());

            $fulltrace = [];
            $this->getStackTrace($exception, $fulltrace);

            $details['trace'] = $this->getTextTrace($fulltrace, true);
        }

        return $details;
    }

    /**
     * @param Exception|Error $exception
     * @param $template_data
     * @param $content_type
     * @return array|string|string[]|null
     */
    public function processTemplate($exception, $template_data, $content_type = 'text/html')
    {
        $configuration = $this->configuration;
        $data = $template_data;

        $exception_domain = ($exception instanceof iDomainException) ? $exception->getDomain() : lcException::DEFAULT_DOMAIN;

        $outmsg = $this->getExceptionOverridenMessage($exception);

        $data = str_replace('{$error_code}', $exception->getCode(), $data);
        $data = str_replace('{$output_encoding}', 'UTF-8', $data);
        $data = str_replace('{$error_subject}', nl2br($outmsg), $data);
        $data = str_replace('{$domain}', $exception_domain, $data);
        $data = str_replace('{$admin_email}', $configuration->getAdminEmail(), $data);
        $data = str_replace('{$lightcast_version}', LIGHTCAST_VER, $data);
        $data = str_replace('{$project_version}', $configuration->getVersion(), $data);
        $data = str_replace('{$php_version}', lcSys::getPhpVer(), $data);
        $data = str_replace('{$exception_name}', get_class($exception), $data);
        $data = str_replace('{$file}', $exception->getFile(), $data);
        $data = str_replace('{$line}', $exception->getLine(), $data);
        $data = str_replace('{$file_excerpt}', $this->fileExcerpt($exception->getFile(), $exception->getLine()), $data);

        // make our stack trace
        $matches = [];
        $tmpd = preg_replace("/\n/", '<---n--->', $data);
        preg_match("/<!-- BEGIN stackline -->(.*)<!-- END stackline -->/", $tmpd, $matches);

        if ($matches) {
            $tracetemp = preg_replace("/<---n--->/", "", $matches[1]);

            $i = 0;
            $tracestr = '';

            $fulltrace = [];
            $this->getStackTrace($exception, $fulltrace);

            foreach ($fulltrace as $trace) {
                $tmp = $tracetemp;
                $tmp = str_replace('{$stack_line}', ($content_type != 'text/html') ?
                    $this->showTextTrace($trace, $i) : $this->showTrace($trace, $i), $tmp);
                $tracestr .= $tmp;
                ++$i;
                unset($trace);
            }

            unset($fulltrace);

            $data = preg_replace("/\n/", '<---n--->', $data);
            $data = preg_replace("/<!-- BEGIN stackline -->.*<!-- END stackline -->/", $tracestr, $data);
            $data = preg_replace("/<---n--->/", "\n", $data);
        }

        return $data;
    }

    protected function fileExcerpt($file, $line)
    {
        if (!is_readable($file)) {
            return false;
        }

        $content = preg_split('#<br />#', highlight_file($file, true));

        $lines = [];

        for ($i = max($line - 6, 1), $max = min($line + 6, count($content)); $i <= $max; $i++) {
            $lines[] = '<li' . ($i == $line ? ' class="selected"' : '') . '>' . $content[$i - 1] . '</li>';
        }

        return '<ol start="' . max($line - 6, 1) . '">' . implode("\n", $lines) . '</ol>';
    }

    private function showTrace($_trace, $_i): string
    {
        $htmldoc = '<div><span style="font-size: 12px; font-weight: bold">#' . $_i . '</span> ';

        if (array_key_exists('file', $_trace)) {
            $htmldoc .= $_trace['file'];
        }

        if (array_key_exists('line', $_trace)) {
            $htmldoc .= '(<span style="color: red; font-weight: bold">' . $_trace["line"] . '</span>): ';
        }

        if (array_key_exists('class', $_trace) && array_key_exists('type', $_trace)) {
            $htmldoc .= $_trace['class'] . $_trace['type'];
        }

        if (array_key_exists('function', $_trace)) {
            $htmldoc .= $_trace["function"] . '(';

            if (array_key_exists('args', $_trace)) {
                if (count($_trace['args']) > 0) {
                    $args = $_trace['args'];
                    $type = gettype($args[0]);
                    $value = $args[0];
                    unset($args);

                    if ($type == 'boolean') {
                        if ($value) {
                            $htmldoc .= 'true';
                        } else {
                            $htmldoc .= 'false';
                        }
                    } else if ($type == 'integer' || $type == 'double') {
                        if (settype($value, 'string')) {
                            $htmldoc .= $value;
                        } else {
                            if ($type == 'integer') {
                                $htmldoc .= '? integer ?';
                            } else {
                                $htmldoc .= '? double or float ?';
                            }
                        }
                    } else if ($type == 'string') {
                        $htmldoc .= "'$value'";
                    } else if ($type == 'array') {
                        $htmldoc .= 'Array';
                    } else if ($type == 'object') {
                        $htmldoc .= 'Object';
                    } else if ($type == 'resource') {
                        $htmldoc .= 'Resource';
                    } else if ($type == 'NULL') {
                        $htmldoc .= 'null';
                    } else if ($type == 'unknown type') {
                        $htmldoc .= '? unknown type ?';
                    }

                    unset($type);
                    unset($value);
                }

                if (count($_trace['args']) > 1) {
                    $htmldoc .= ',...';
                }
            }

            $htmldoc .= ')</div>';
        }

        if (isset($_trace['file']) && isset($_trace['line'])) {
            $htmldoc .=
                '<div style="font-size:10px; line-height: 12px;">' .
                self::fileExcerpt($_trace['file'], $_trace['line']) .
                '</div>';
        }

        return $htmldoc;
    }

    public function supportsAssertions(): bool
    {
        return true;
    }

    public function handleAssertion($file, $line, $code)
    {
        throw new lcAssertException($file, $line, $code);
    }

    public function handlePHPError($errno, $errmsg, $filename, $linenum, $vars = null): bool
    {
        if (error_reporting() == 0) {
            return true;
        }

        throw new lcPHPException($errmsg, $errno, $filename, $linenum, $vars);
    }

    protected function beforeAttachRegisteredEvents()
    {
        parent::beforeAttachRegisteredEvents();

        $this->is_debugging = $this->configuration->isDebugging();
    }
}
