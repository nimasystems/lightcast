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

class lcWebResponse extends lcResponse implements iKeyValueProvider, iDebuggable
{
    const TR_MULTILINE_DETECT_REP = '-----rtn----';

    /**
     * @var lcWebRequest
     */
    protected $request;

    protected $status_code = lcHttpStatusCode::OK;
    protected $http_version = lcHttpVersion::HTTPVER_1_1;
    protected $reason_string = lcHttpStatusCode::OK_MESSAGE;

    protected $exit_upon_send;

    /**
     * @var lcArrayCollection
     */
    protected $custom_headers;

    protected $server_charset = 'utf-8';
    protected $content_type = 'text/html';

    protected $output_open_graph_data = true;

    protected $no_http_errors_processing;

    /**
     * @var bool
     */
    protected $add_ref_canonical = true;

    protected $content;
    protected $output_content;
    /**
     * @var array
     */
    protected $javascripts;
    protected $js_at_end_forced;
    protected $css_at_end_forced;
    protected $javascripts_async;
    protected $no_scripts;
    /**
     * @var array
     */
    protected $javascripts_end;
    protected $javascript_code;
    protected $javascript_code_before;
    protected $javascript_code_after;

    protected $view_javascripts_enabled = true;
    protected $view_stylesheets_enabled = true;

    /**
     * @var array
     */
    protected $stylesheets;
    /**
     * @var array
     */
    protected $metatags;
    /**
     * @var array
     */
    protected $rssfeeds;
    protected $icon;
    protected $html_base;
    protected $title;
    protected $title_suffix;
    protected $lang_dir;
    /**
     * @var array
     */
    protected $body_tags;
    /**
     * @var array
     */
    protected $html_head_custom;
    /**
     * @var array
     */
    protected $html_body_custom;
    protected $allow_javascripts;
    protected $allow_rss_feeds;
    protected $allow_stylesheets;
    protected $allow_metatags;
    protected $content_lang;
    protected $htmlver;
    protected $clientside_js;

    protected $content_url;
    protected $content_hreflangs;

    /**
     * @var lcCookiesCollection
     */
    private $cookies;
    private $content_should_be_processed;

    /*
     * Response initialization
    */
    public function initialize()
    {
        parent::initialize();

        $this->exit_upon_send = true;
        $this->content_should_be_processed = true;

        $this->custom_headers = new lcArrayCollection();
        $this->cookies = new lcCookiesCollection();

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

        $this->server_charset = isset($this->configuration['view.charset']) ? (string)$this->configuration['view.charset'] : 'utf-8';
        $this->content_type = isset($this->configuration['view.content_type']) ? (string)$this->configuration['view.content_type'] : 'text/html';

        // base
        $this->html_base = (string)$this->configuration['view.base'];

        // HTML5 or 4
        $this->htmlver = $this->configuration['view.htmlver'];

        $this->js_at_end_forced = (bool)$this->configuration['view.javascripts_at_end'];
        $this->css_at_end_forced = (bool)$this->configuration['view.stylesheets_at_end'];
        $this->javascripts_async = (bool)$this->configuration['view.javascripts_async'];
        $this->no_scripts = (bool)$this->configuration['view.no_scripts'];
        $this->javascript_code_before = $this->configuration['view.javascript_code_before'];
        $this->javascript_code_after = $this->configuration['view.javascript_code_after'];

        // dir
        $this->lang_dir = (string)$this->configuration['view.dir'];

        // allowances
        $this->allow_javascripts = (bool)$this->configuration['view.allow_javascripts'];
        $this->allow_stylesheets = (bool)$this->configuration['view.allow_stylesheets'];
        $this->allow_rss_feeds = (bool)$this->configuration['view.allow_rss_feeds'];
        $this->allow_metatags = (bool)$this->configuration['view.allow_metatags'];

        $this->clientside_js = (bool)$this->configuration['view.clientside_js'];

        unset($js_path);
    }

    public function shutdown()
    {
        $this->clear();

        $this->request =
        $this->custom_headers =
        $this->cookies =
            null;

        parent::shutdown();
    }

    public function clear()
    {
        if ($this->response_sent) {
            return;
        }

        $this->javascripts = null;
        $this->javascripts_end = null;
        $this->stylesheets = null;
        $this->metatags = null;
        $this->rssfeeds = null;
        $this->icon = null;
        $this->html_base = null;
        $this->title = null;
        $this->body_tags = null;
        $this->html_head_custom = null;
        $this->html_body_custom = null;
        $this->content = null;
        $this->content_type = 'text/html';
        $this->server_charset = 'utf-8';

        if ($this->custom_headers) {
            $this->custom_headers->clear();
        }
    }

    public function getDebugInfo()
    {
        // compile cookies
        $cc1 = $this->cookies;
        $ca = [];

        if ($cc1) {
            /** @var lcNameValuePair[] $c */
            $c = $cc1->getArrayCopy();

            if ($c) {
                foreach ($c as $cc) {
                    $ca[$cc->getName()] = $cc->getValue();

                    unset($cc);
                }
            }

            unset($c);
        }

        return [
            'status_code' => $this->status_code,
            'http_response_message' => $this->reason_string,
            'http_version' => $this->http_version,
            'custom_headers' => ($this->custom_headers ? $this->custom_headers->getKeyValueArray() : null),
            'charset' => $this->server_charset,
            'content_type' => $this->content_type,
            'cookies' => ($ca ? $ca : null),
        ];
    }

    #pragma mark - iKeyValueProvider

    public function getShortDebugInfo()
    {
        return false;
    }

    public function getAllKeys()
    {
        return [
            'page_title',
            'page_description',
        ];
    }

    public function getValueForKey($key)
    {
        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        $ret = null;

        if ($key == 'page_title') {
            $ret = $this->getTitle();
        }

        return $ret;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        if (DO_DEBUG) {
            $this->debug('set title: ' . $title);
        }
    }

    /**
     * @param bool $add_ref_canonical
     */
    public function setAddRefCanonical($add_ref_canonical)
    {
        $this->add_ref_canonical = $add_ref_canonical;
    }

    public function sendChunkedStream($uri, $mimetype = 'application/binary', array $options = null)
    {
        $cnt = 0;
        $resource = (isset($options['stream_context']) ? $options['stream_context'] : null);
        $handle = $resource ? fopen($uri, 'r', null, $resource) : fopen($uri, 'r');

        if ($handle === false) {
            return false;
        }

        $chunk_size = isset($options['chunk_size']) && (int)$options['chunk_size'] ? $options['chunk_size'] : 128;

        if ($mimetype) {
            header('Content-Type: ' . $mimetype);
        }

        if (isset($options['attachment']) && isset($options['attachment']['filename'])) {
            header('Content-Disposition: attachment; filename=' . $options['attachment']['filename']);
        }

        while (!feof($handle)) {
            $buffer = fread($handle, $chunk_size);
            echo $buffer;
            ob_flush();

            $cnt += strlen($buffer);
        }

        $this->response_sent = true;

        fclose($handle);
        exit(0);
    }

    public function fileStream($data, $filename, $mimetype = 'application/binary')
    {
        $content_type = (($mimetype !== null) ? $mimetype : 'application/binary');

        $this->setContentType($content_type);
        $this->setContentDisposition('attachment; filename="' . $filename . '"');
        $this->setContent($data);

        if (DO_DEBUG) {
            $this->debug('outputing file for downloading: ' . $filename . ' / ' . $mimetype);
        }

        $this->sendResponse();
    }

    public function setContentDisposition($content_disposition)
    {
        $this->custom_headers->set('Content-Disposition', $content_disposition);
    }

    public function sendResponse()
    {
        if ($this->headersSent()) {
            return;
        }

        if ($this->response_sent) {
            return;
        }

        // check if response sending is allowed
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.should_send_response', $this, []), []);

        if ($event->isProcessed()) {
            $should_send_response = (bool)$event->getReturnValue();

            if (!$should_send_response) {
                $this->info('Response sending stopped by event - caller: ' . get_class($event->subject));
                return;
            }
        }

        $this->response_sent = true;

        // prepare the content
        $this->_internalSend();

        // output the content
        $should_be_silent = (bool)$this->request->getIsSilent();

        if (!$should_be_silent) {
            $this->_outputContent();
        }

        // exit the script
        if ($this->exit_upon_send) {
            exit(0);
        }
    }

    public function headersSent()
    {
        return headers_sent();
    }

    protected function _internalSend()
    {
        $content = $this->content;

        /*if (!is_resource($content)) {
            $content = str_replace("\n", self::TR_MULTILINE_DETECT_REP, $content);
        }*/

        $this->content = null;

        // if exit code is error - do not process content
        if ($this->exit_code != 0 || !is_string($content)) {
            $this->content_should_be_processed = false;
        }

        // notify listeners if this is an errorous response
        if (!$this->no_http_errors_processing && $this->status_code != lcHttpStatusCode::OK) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.send_http_error', $this, [
                    'status_code' => $this->status_code,
                ]), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }
        }

        // notify with an event
        $this->event_dispatcher->notify(
            new lcEvent('response.will_send_response', $this, []));

        // notify listeners
        if ($this->content_should_be_processed) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.send_response', $this, []), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }

            unset($event);
        }

        if ($this->content_should_be_processed && !$this->request->isAjax()) {
            // set html customizations
            if ($this->content_type == 'text/html') {
                $content = str_replace("\n", self::TR_MULTILINE_DETECT_REP, $content);
                $content = $this->processHtmlContent($content);
                $content = str_replace(self::TR_MULTILINE_DETECT_REP, "\n", $content);
            }
        }

        // notify listeners
        if ($this->content_should_be_processed) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.output_content', $this, []), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }

            unset($event);
        }

        if (!is_resource($content)) {
            // disable all scripts
            if ($this->content_type == 'text/html') {
                if ($this->no_scripts) {
                    $content = preg_replace("#<script(.*?)>(.*?)</script>#is", '', $content);
                }
            }

            //$content = str_replace(self::TR_MULTILINE_DETECT_REP, "\n", $content);
        }

        $this->output_content = $content;

        // IMPORTANT: COOKIES / HEADERS MUST BE SENT LAST!

        // send the cookies
        $sent_cookies = $this->sendCookies();

        // send the headers
        $sent_headers = $this->sendHeaders();

        // notify with an event
        $this->event_dispatcher->notify(
            new lcEvent('response.did_send_response', $this, [
                'cookies' => $sent_cookies,
                'headers' => $sent_headers,
                'content' => $content,
            ]));
    }

    private function processHtmlContent($content)
    {
        if (!$content) {
            return null;
        }

        $head = [];

        // flush based on allowances
        if (!$this->allow_metatags) {
            $this->metatags = [];
        }

        if (!$this->allow_javascripts) {
            $this->javascripts = [];
            $this->javascripts_end = [];
        }

        if (!$this->allow_stylesheets) {
            $this->stylesheets = [];
        }

        if (!$this->allow_rss_feeds) {
            $this->rssfeeds = [];
        }

        // process view based config vars
        $this->processViewConfiguration();

        // meta equiv
        // meta equiv
        if ($this->htmlver == 5) {
            // html5 compat
            //$head[] = '<!-- From HTML 5 Boilerplate: Use .htaccess instead. See: h5bp.com/i/378 -->';
            $head[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';

            $head[] = '<meta charset="' . $this->server_charset . '" />';
        } else {
            $head[] = '<meta http-equiv="Content-Type" content="' . $this->content_type . ' charset=' . $this->server_charset . '" />';
        }

        // html_base
        if ($this->html_base) {
            $head[] = '<base href="' . $this->html_base . '" />';
        }

        // metatags
        $metatags = $this->metatags;

        // title
        $title = $this->title ? $this->title : (isset($metatags['title']) ? $metatags['title'] : null);

        if ($title) {
            $head[] = '<title>' . htmlspecialchars($title . $this->title_suffix) . '</title>';
        }

        // auto add canonical if there is a difference between the current uri and the content url
        $request_uri = $this->request->getFullHostname() . $this->request->getRequestUri();
        $has_canonical = false;

        if ($this->content_url && $request_uri != $this->content_url) {
            $has_canonical = true;
            $head[] = '<link rel="canonical" href="' . htmlspecialchars($this->content_url) . '" />';
        }

        // hreflang(s) - do not show if there is a canonical present
        if ($this->add_ref_canonical || !$has_canonical) {
            $content_hreflangs = $this->content_hreflangs;

            if ($content_hreflangs) {
                $head_hreflangs = [];
                $current_url = null;

                foreach ($content_hreflangs as $data) {

                    $head_hreflangs[] = lcTagLink::create()
                        ->setRel('alternate')
                        ->setHref($data['url'])
                        ->setAttribute('hreflang', (isset($data['default']) && $data['default'] ? 'x-default' : $data['locale']))
                        ->toString();

                    if (isset($data['current']) && $data['url'] == $request_uri) {
                        $current_url = $data['url'];
                    }

                    unset($data);
                }

                // if we have both canonical + hreflangs the canonical must be the 'self' of the current hreflang url
                if (($has_canonical && $current_url) || !$has_canonical) {
                    $head = array_merge($head, $head_hreflangs);
                }
            }
        }

        // metatags
        $meta_description = null;

        if ($metatags) {
            foreach ($metatags as $name => $value) {

                // this is deprecated now
                if ($name == 'title') {
                    continue;
                }

                if ($name == 'description') {
                    $meta_description = $value;
                }

                if (!$value) {
                    continue;
                }

                $head[] =
                    '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($value) . '" />';

                unset($name, $value);
            }
        }

        unset($metatags);

        // open graph
        if ($this->output_open_graph_data) {
            if ($title) {
                $head[] = '<meta property="og:title" content="' . htmlspecialchars($title) . '" />';
            }

            if ($meta_description) {
                $head[] = '<meta property="og:description" content="' . htmlspecialchars($meta_description) . '" />';
            }
        }

        // icon
        $icon = $this->icon;

        if ($icon) {
            $head[] = '<link rel="icon" href="' . htmlspecialchars($icon['href']) . '" type="' . htmlspecialchars($icon['type']) . '" />';
        }

        unset($icon);

        // content language
        if ($this->content_lang && $this->htmlver == 5) {
            $content = preg_replace("/<html/i", '<html ' . 'lang="' . $this->content_lang . '"', $content);
        }

        // stylesheets
        $stylesheets = $this->stylesheets;

        // filter javascripts start
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.filter.stylesheets', $this, []), $stylesheets);

        if ($event->isProcessed()) {
            $stylesheets = $event->getReturnValue();
        }

        if ($stylesheets) {
            foreach ($stylesheets as $href => $data) {
                $head[] =
                    '<link rel="stylesheet" type="' . $data['type'] . '" ' .
                    'href="' . $data['href'] . '" media="' . $data['media'] . '" />';

                unset($href, $data);
            }
        }

        unset($stylesheets);

        // javascripts start
        $javascripts = $this->javascripts;

        // filter javascripts start
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.filter.javascripts_start', $this, []), $javascripts);

        if ($event->isProcessed()) {
            $javascripts = $event->getReturnValue();
        }

        unset($event);

        if ($javascripts) {
            foreach ($javascripts as $src => $data) {
                $oattr = [];
                if (isset($data['other_attribs']) && $data['other_attribs']) {
                    foreach ($data['other_attribs'] as $key => $v) {
                        $oattr[] = $key . '="' . htmlspecialchars($v) . '"';
                        unset($key, $v);
                    }
                }

                if ($this->javascripts_async || (isset($data['async']) && $data['async'])) {
                    $oattr[] = 'async';
                }

                $scr = '<script type="' . $data['type'] . '" src="' . $data['src'] . '"' . ($oattr ? ' ' . implode(' ', $oattr) : null) . '></script>';
                $head[] = $scr;

                unset($src, $data, $oattr);
            }
        }

        // javascripts end
        $javascripts = $this->javascripts_end;

        // filter javascripts start
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.filter.javascripts_end', $this, []), $javascripts);

        if ($event->isProcessed()) {
            $javascripts = $event->getReturnValue();
        }

        if ($javascripts) {
            foreach ($javascripts as $src => $data) {
                $oattr = [];
                if (isset($data['other_attribs']) && $data['other_attribs']) {
                    foreach ($data['other_attribs'] as $key => $v) {
                        $oattr[] = $key . '="' . htmlspecialchars($v) . '"';
                        unset($key, $v);
                    }
                }

                if ($this->javascripts_async || (isset($data['async']) && $data['async'])) {
                    $oattr[] = 'async';
                }

                $scr = '<script type="' . $data['type'] . '" src="' . $data['src'] . '"' . ($oattr ? ' ' . implode(' ', $oattr) : null) . '></script>';
                $this->html_body_custom['end'][] = $scr;

                unset($src, $data);
            }
        }

        unset($javascripts);

        if (!$this->no_scripts) {
            // javascript code
            $jscode = $this->javascript_code;

            $event = $this->event_dispatcher->filter(
                new lcEvent('response.send_response_javascript_code', $this, []), $jscode);

            if ($event->isProcessed()) {
                $jscode = $event->getReturnValue();
            }

            unset($event);

            if ($jscode) {

                if (DO_DEBUG) {
                    if (is_array($jscode)) {
                        foreach ($jscode as $key => $code) {

                            $jscode[$key] = '/** ' . $key . ' */' . "\n" .
                                (is_array($code) ? implode("\n", $code) : $code);

                            unset($key, $code);
                        }
                    }
                }

                $jscode = is_array($jscode) ? implode("\n", array_filter(array_values($jscode))) : $jscode;
                $jscode = $jscode ? trim(preg_replace('/^\h*\v+/m', '', $jscode)) : null;

                if ($jscode) {
                    $this->html_body_custom['end'][] = lcTagScript::create()
                        ->setContent($this->javascript_code_before .
                            $jscode .
                            $this->javascript_code_after)
                        ->toString();
                }
            }
        }

        // rss feeds
        $rssfeeds = $this->rssfeeds;

        if ($this->rssfeeds) {
            foreach ($rssfeeds as $href => $data) {
                $head[] =
                    '<link media="' . $data['media'] . '" rel="alternate" type="application/rss+xml" ' .
                    (isset($data['title']) ? 'title="' . $data['title'] . '" ' : null) .
                    'href="' . $data['href'] . '"  />';

                unset($href, $data);
            }
        }

        unset($rssfeeds);

        if (!$this->no_scripts) {
            // google analytics
            $google_analytics = (string)$this->configuration['view.google_analytics'];

            if ($google_analytics) {
                $this->html_body_custom['end'][] = lcTagScript::create()
                    ->setContent('var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
					document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
					try {
					var pageTracker = _gat._getTracker("' . $google_analytics . '");
							pageTracker._trackPageview();
		            } catch(err) {}')
                    ->toString();
            }
        }

        // custom body end
        $html_body_custom = $this->html_body_custom;

        // TODO: the only reason that the javascripts do not get added to ajax calls is
        // that there is no <body> tag usually in there.. this must be fixed!

        if (isset($html_body_custom['end'])) {
            $content = preg_replace("/<\/body>/i", "\n" . implode("\n", $html_body_custom['end']) . "\n" . '</body>', $content);
        }

        // head parts
        $imploded = implode("\n", $head);

        if ($imploded) {
            $content = preg_replace("/<head>/i", '<head>' . $imploded, $content);
        }

        unset($head);

        // custom body tags
        $body_tags1 = $this->body_tags;

        if ($body_tags1) {
            $body_tags = [];

            foreach ($body_tags1 as $name => $value) {
                $body_tags[] = $name . '="' . $value . '"';

                unset($name, $value);
            }

            $content = preg_replace("/<body/i", '<body ' . implode(' ', $body_tags), $content);

            unset($body_tags);
        }

        // custom head
        $html_head_custom = $this->html_head_custom;

        if ($html_head_custom) {
            $start = null;
            $end = null;

            foreach ($html_head_custom as $custom_code) {
                $start .= isset($custom_code['start']) ? $custom_code['start'] . "\n" : null;
                $end .= isset($custom_code['end']) ? $custom_code['end'] . "\n" : null;

                unset($custom_code);
            }

            if ($start) {
                $content = preg_replace("/<head>/i", '<head>' . $start, $content);
            }

            if ($end) {
                $content = preg_replace("/<\/head>/i", $end . '</head>', $content);
            }

            unset($start, $end);
        }

        unset($html_head_custom);

        // custom body
        $html_body_custom = $this->html_body_custom;

        if ($html_body_custom) {
            $start = null;
            $end = null;

            foreach ($html_body_custom as $custom_code) {
                $start .= isset($custom_code['start']) ? $custom_code['start'] . "\n" : null;
                $end .= isset($custom_code['end']) ? $custom_code['end'] . "\n" : null;

                unset($custom_code);
            }

            if ($start) {
                $content = preg_replace("/<body(.*?)>/i", '<body>' . "\n" . $start, $content);
            }

            if ($end) {
                $content = preg_replace("/<\/body>/i", $end . "\n" . '</body>', $content);
            }

            unset($start, $end);
        }

        return $content;
    }

    private function processViewConfiguration()
    {
        // metatags
        $config_metatags = (array)$this->configuration['view.metatags'];

        foreach ($config_metatags as $name => $value) {
            if (isset($this->metatags[$name])) {
                continue;
            }

            $this->metatags[$name] = (string)$value;

            unset($name, $value);
        }

        unset($config_metatags);


        // stylesheets
        $stylesheet_path = $this->configuration->getStylesheetPath();

        if ($this->view_stylesheets_enabled) {
            $stylesheets = (array)$this->configuration['view.stylesheets'];

            if ($stylesheets) {
                $config_stylesheets = (array)$this->configuration['view.stylesheets'];

                foreach ($config_stylesheets as $type => $stylesheets) {
                    if (!$stylesheets || !is_array($stylesheets)) {
                        continue;
                    }

                    foreach ($stylesheets as $sheet) {
                        // relative or absolute path
                        $p = ($sheet && ($sheet[0] == '/' || lcStrings::startsWith($sheet, 'http'))) ? $sheet : $stylesheet_path . $sheet;

                        $this->stylesheets[$sheet] = [
                            'href' => $p,
                            'type' => 'text/css',
                            'media' => $type,
                        ];

                        unset($sheet, $p);
                    }

                    unset($name, $type);
                }

                unset($config_stylesheets);
            }
        }

        unset($stylesheet_path);

        // javascripts
        $js_path = $this->configuration->getJavascriptPath();

        if ($this->view_javascripts_enabled) {
            // start javascripts
            $javascripts = (array)$this->configuration['view.javascripts'];

            if ($javascripts) {
                foreach ($javascripts as $jsidx => $jss) {
                    $js = is_array($jss) ? (isset($jss['src']) ? $jss['src'] : null) : $jss;

                    if (!$js) {
                        continue;
                    }

                    $async = is_array($jss) && isset($jss['async']) && $jss['async'];

                    // relative or absolute path
                    $p = ($js && ($js[0] == '/' || lcStrings::startsWith($js, 'http'))) ? $js : $js_path . $js;

                    $jd = [
                        'src' => $p,
                        'type' => 'text/javascript',
                        'async' => $async,
                    ];

                    $this->javascripts[$js_path . $js] = $jd;

                    unset($js, $p);
                }

                unset($config_js);
            }

            // end javascripts
            $javascripts = (array)$this->configuration['view.javascripts_end'];

            if ($javascripts) {
                foreach ($javascripts as $jsidx => $jss) {
                    $js = is_array($jss) ? (isset($jss['src']) ? $jss['src'] : null) : $jss;

                    if (!$js) {
                        continue;
                    }

                    $async = is_array($jss) && isset($jss['async']) && $jss['async'];

                    // relative or absolute path
                    $p = ($js && ($js[0] == '/' || lcStrings::startsWith($js, 'http'))) ? $js : $js_path . $js;

                    $jd = [
                        'src' => $p,
                        'type' => 'text/javascript',
                        'async' => $async,
                    ];

                    $this->javascripts_end[$js_path . $js] = $jd;

                    unset($jsidx, $js, $p);
                }

                unset($config_js);
            }
        }
    }

    protected function sendCookies()
    {
        $cookies = $this->cookies;

        if (!$cookies) {
            return;
        }

        // notify with an event
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.send_cookies', $this, []), $cookies);

        if ($event->isProcessed()) {
            $cookies = $event->getReturnValue();
        }

        $sent_cookies = [];

        if ($cookies && $cookies instanceof lcCookiesCollection) {
            $log = [];
            $cookies = $cookies->getAll();

            foreach ($cookies as $sl) {
                $set = setcookie(
                    $sl->getName(),
                    $sl->getValue(),
                    ($sl->getExpiration() ? $sl->getExpiration() : null),
                    $sl->getPath(),
                    $sl->getDomain(),
                    $sl->IsSecure());

                if (!$set && DO_DEBUG) {
                    throw new lcSystemException('Could not set cookie ' . $sl->getName());
                }

                $sent_cookies[$sl->getName()] = $sl->getValue();

                if (DO_DEBUG) {
                    $log[] = $sl->getName() . ': ' . $sl->getValue();
                }

                unset($sl, $set);
            }

            if (DO_DEBUG && $log) {
                $this->debug('response has output cookies: ' . "\n\n" . implode("\n", $log));
            }
        }

        return $sent_cookies;
    }

    protected function sendHeaders()
    {
        $prepared_headers = [];

        // set status code
        if ($this->http_version) {
            $http_version = lcHttpVersion::getString($this->http_version);
            $status_code = $this->status_code;
            $reason_string = $this->reason_string;

            if ($http_version && $status_code) {
                // fix reason string
                $reason_string = str_replace("\n", ' ', $reason_string);
                $reason_string = str_replace("\r", ' ', $reason_string);

                $header = $http_version . ' ' . $status_code . ' ' . $reason_string;
                $prepared_headers[] = $header;
                unset($header);
            }

            unset($http_version, $status_code, $reason_string);
        }

        $content_type = $this->content_type;
        $server_charset = $this->server_charset;

        if ($content_type) {
            $txt_based_header = 'text/';
            $is_text_based_content = substr($content_type, 0, strlen($txt_based_header)) == $txt_based_header;
            $header = 'Content-Type: ' . $content_type . ($is_text_based_content ? '; charset=' . $server_charset : null);
            $prepared_headers[] = $header;
            unset($header);
        }

        // our framework :)
        $header = 'X-Powered-By: Lightcast ' . LIGHTCAST_VER . ' PHP Framework - www.nimasystems.com/lightcast';
        $prepared_headers[] = $header;

        // debugging marker
        if (DO_DEBUG) {
            $header = 'X-LC-Debug: 1';
            $prepared_headers[] = $header;
        }

        // title
        if ($this->request->isAjax() && $this->title) {
            $t = str_replace("\n", ' ', $this->title);
            $t = str_replace("\r", ' ', $t);
            $prepared_headers[] = 'Title: ' . urlencode($t);
            unset($t);
        }

        // insert all custom headers
        $headers = $this->custom_headers->getAll();

        foreach ($headers as $header) {
            if ($header->getValue() != "") {
                // fix reason string
                $hv = $header->getValue();

                $hv = str_replace("\n", ' ', $hv);
                $hv = str_replace("\r", ' ', $hv);

                $header = $header->getName() . ': ' . $hv;
                $prepared_headers[] = $header;
                unset($header);
            } else {
                $prepared_headers[] = $header->getName();
            }
            unset($header);
        }

        unset($headers);

        // notify with an event
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.send_headers', $this, []), $prepared_headers);

        if ($event->isProcessed()) {
            $prepared_headers = $event->getReturnValue();
        }

        if ($prepared_headers) {
            foreach ($prepared_headers as $header) {
                header($header);

                unset($header);
            }
        }

        return $prepared_headers;
    }

    protected function _outputContent()
    {
        $this->clear();

        $content = $this->output_content;

        if ($content && is_resource($content)) {
            //fpassthru($content);
            echo stream_get_contents($content);
            fclose($content);
        } else {
            // notify with an event
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.optimize_content', $this, []), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }

            echo $content;
        }
    }

    public function getContentHreflangs()
    {
        return $this->content_hreflangs;
    }

    public function setContentHreflangs(array $content_hreflangs = null)
    {
        $this->content_hreflangs = $content_hreflangs;
    }

    public function setViewJavascriptsEnabled($enabled = true)
    {
        $this->view_javascripts_enabled = $enabled;
    }

    public function setViewStylesheetsEnabled($enabled = true)
    {
        $this->view_stylesheets_enabled = $enabled;
    }

    public function getStylesheets()
    {
        return $this->stylesheets;
    }

    public function getJavascripts()
    {
        return $this->javascripts;
    }

    /*
     * Set a javascript include
    * <script type="text/javascript" src=""></script>
    */

    public function getJavascriptsEnd()
    {
        return $this->javascripts_end;
    }

    /*
     * Prepends a javascript - before all other javascripts
    */

    public function setNoContentProcessing($process = true)
    {
        $this->content_should_be_processed = !(bool)$process;
    }

    public function getNoContentProcessing()
    {
        return $this->content_should_be_processed;
    }

    /*
     * Set a css include
    * <link rel="stylesheet" type="text/css" href="" media="screen" />
    */

    public function setAllowJavascripts($allow = true)
    {
        $this->allow_javascripts = $allow;
    }

    public function setAllowStylesheets($allow = true)
    {
        $this->allow_stylesheets = $allow;
    }

    /*
     * Set a metatag
    * <meta name="robots" content="" />
    */

    public function setAllowRssFeeds($allow = true)
    {
        $this->allow_rss_feeds = $allow;
    }

    /*
     * Set a RSS feed
    * <link media="all" rel="alternate" type="application/rss+xml" title=""  href=""  />
    */

    public function setAllowMetatags($allow = true)
    {
        $this->allow_metatags = $allow;
    }

    /**
     * Removes all included js files
     */
    public function clearJavascripts()
    {
        $this->javascripts = null;
        $this->javascripts_end = null;
    }

    /*
     * Set a Favorite Icon
    * <link rel="icon" href="" type="image/png" />
    */

    /**
     * Removes all included css files
     */
    public function clearStylesheets()
    {
        $this->stylesheets = null;
    }

    /*
     * Set Base
    * <base href="" />
    */

    public function removeJavascript($js_src)
    {
        if (isset($this->javascripts[$js_src])) {
            unset($this->javascripts[$js_src]);
        }

        if (isset($this->javascripts_end[$js_src])) {
            unset($this->javascripts_end[$js_src]);
        }
    }

    public function getJavascriptCode($combined = true)
    {
        if ($combined) {
            $jscode = is_array($this->javascript_code) ? implode("\n", array_filter(array_values($this->javascript_code))) : $this->javascript_code;
            $jscode = $jscode ? trim(preg_replace('/^\h*\v+/m', '', $jscode)) : null;
            return $jscode;
        } else {
            return $this->javascript_code;
        }
    }

    public function setJavascriptCode($code, $tag = null)
    {
        $tag = $tag ? $tag : 'js_' . lcStrings::randomString(20);
        $this->javascript_code = [$tag => $code];
    }

    public function addJavascriptCode($code, $tag = null)
    {
        $tag = $tag ? $tag : 'js_' . lcStrings::randomString(20);
        $this->javascript_code[$tag] = $code;
    }

    public function removeStylesheet($css_src)
    {
        if (isset($this->stylesheets[$css_src])) {
            unset($this->stylesheets[$css_src]);
        }
    }

    public function javascript($src, $type = null, $language = null, $at_end = false, array $other_attribs = null)
    {
        $at_end = ($this->js_at_end_forced ? true : $at_end);

        $this->setJavascript($src, $type, $language, $at_end, $other_attribs);
    }

    public function setJavascript($src, $type = null, $language = null, $at_end = false, array $other_attribs = null)
    {
        $type = $type ?: 'text/javascript';
        $language = $language ?: 'javascript';
        $at_end = ($this->js_at_end_forced ? true : $at_end);

        if (is_array($src)) {
            foreach ($src as $s) {
                $this->setJavascript($s, $type, $language, $at_end, $other_attribs);
                unset($s);
            }
        } else {
            if ($at_end) {
                $this->javascripts_end[$src] = ['src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs];
            } else {
                $this->javascripts[$src] = ['src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs];
            }

            if (DO_DEBUG) {
                $this->debug('set javascript: ' . $src);
            }
        }
    }

    public function prependJavascript($src, $type = null, $language = null, $at_end = false, array $other_attribs = null)
    {
        $type = $type ?: 'text/javascript';
        $language = $language ?: 'javascript';
        $at_end = ($this->js_at_end_forced ? true : $at_end);

        $new = [];
        $new[$src] = ['src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs];

        if ($at_end) {
            $this->javascripts_end = array_merge($new, (array)$this->javascripts_end);
        } else {
            $this->javascripts = array_merge($new, (array)$this->javascripts);
        }

        if (DO_DEBUG) {
            $this->debug('prepend javascript: ' . $src);
        }
    }

    /**
     * @param $href
     * @param string $media
     * @param string $type
     * @deprecated Use setStylesheet instead
     */
    public function css($href, $media = null, $type = null)
    {
        $this->setStylesheet($href, $media, $type);
    }

    public function setStylesheet($href, $media = null, $type = null)
    {
        $media = $media ?: 'all';
        $type = $type ?: 'text/css';

        if (is_array($href)) {
            foreach ($href as $h) {
                $this->setStylesheet($h, $media, $type);
                unset($h);
            }
        } else {
            $this->stylesheets[$href] = ['href' => $href, 'type' => $type, 'media' => $media];

            if (DO_DEBUG) {
                $this->debug('set stylesheet: ' . $href . ' : ' . $media);
            }
        }
    }

    public function clearMetatags()
    {
        $this->metatags = [];
    }

    public function getMetatag($name)
    {
        return isset($this->metatags[$name]) ? $this->metatags[$name] : null;
    }

    public function setMetatag($name, $value)
    {
        $this->metatags[$name] = $value;

        if (DO_DEBUG) {
            $this->debug('set metatag: ' . $name . '/' . $value);
        }
    }

    public function setRSSFeed($href, $title = '', $media = 'all')
    {
        $this->rssfeeds[$href] = ['href' => $href, 'title' => $title, 'media' => $media];

        if (DO_DEBUG) {
            $this->debug('set rss feed: ' . $href);
        }
    }

    public function getHtmlVer()
    {
        return $this->htmlver;
    }

    /*
     * Get the current Response content
    */

    public function setIcon($href, $type = 'image/png')
    {
        $this->icon = ['href' => $href, 'type' => $type];

        if (DO_DEBUG) {
            $this->debug('set icon: ' . $href);
        }
    }

    /*
     * Check if the response headers haven't been already
    * sent out
    */

    public function setBase($href = null)
    {
        $this->html_base = $href;

        if (DO_DEBUG) {
            $this->debug('set base: ' . $href);
        }
    }

    public function getTitleSuffix()
    {
        return $this->title_suffix;
    }

    public function setTitleSuffix($title)
    {
        $this->title_suffix = $title;
    }

    public function setBodyTag($name, $value)
    {
        $this->body_tags[$name] = $value;
    }

    /*
     * Send the Response
    * If the response headers have already been sent out
    * the script will silently stop
    */

    public function customHeadHtml($start = null, $end = null, $tag = null, $is_javascript = false)
    {
        if ($is_javascript && $this->clientside_js) {
            return false;
        }

        $this->html_head_custom[] = ['start' => $start, 'end' => $end, 'tag' => $tag, 'is_javascript' => $is_javascript];

        return null;
    }

    /**
     * @param $code
     * @param null $tag
     * @deprecated This is obsoleted by addJavascriptCode
     */
    public function addBodyJavascript($code, $tag = null)
    {
        $this->customBodyHtml(null, $code, $tag, true);
    }

    public function customBodyHtml($start = null, $end = null, $tag = null, $is_javascript = false)
    {
        if ($is_javascript && $this->clientside_js) {
            return false;
        }

        $this->html_body_custom[] = ['start' => $start, 'end' => $end, 'tag' => $tag, 'is_javascript' => $is_javascript];

        return null;
    }

    public function setCookie(lcCookie $cookie)
    {
        $this->cookies->append($cookie);

        if (DO_DEBUG) {
            $this->debug('set cookie: ' . $cookie->getName() . ' : ' . $cookie->getValue());
        }
    }

    public function getContentUrl()
    {
        return $this->content_url;
    }

    public function setContentUrl($content_url)
    {
        // notify listeners
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.set_content_url', $this, []), $content_url);

        if ($event->isProcessed()) {
            $content_url = $event->getReturnValue();
        }

        $this->content_url = $content_url;
    }

    public function getOutputContent()
    {
        return $this->output_content;
    }

    /*
     * Internal response send
    */

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function sendHttpError($message = null)
    {
        $this->setStatusCode(lcHttpStatusCode::INTERNAL_ERROR);

        // allow listeners to update the errorous page content
        $this->content = $message ? $message : lcHttpStatusCode::getMessage($this->status_code);

        $this->send();
    }

    public function send()
    {
        $this->sendResponse();
    }

    public function sendHttpNotFound()
    {
        $this->setStatusCode(lcHttpStatusCode::NOT_FOUND);

        // allow listeners to update the errorous page content
        if (!$this->content) {
            $this->content = lcHttpStatusCode::getMessage($this->status_code);
        }

        $this->send();
    }

    /*
     * Set a custom Response Header
    */

    public function setShouldExitUponSend($do_exit = true)
    {
        $this->exit_upon_send = (bool)$do_exit;
    }

    public function setDontProcessHttpErrors($dont = true)
    {
        $this->no_http_errors_processing = $dont;
    }

    /*
     * Send a HTTP Redirect and stop script
    */

    public function header($name, $value)
    {
        $this->custom_headers->set($name, $value);

        if ($name == 'Content-Type') {
            $this->setContentType($value);
        }

        if (DO_DEBUG) {
            $this->debug('set header: ' . $name . ' : ' . $value);
        }
    }

    public function getHeaders()
    {
        return $this->custom_headers;
    }

    /*
     * Disable response caching by outputing
    * Cache-Control and Expires headers
    */

    public function redirect($url, $http_code = 302)
    {
        if (!$url) {
            return;
        }

        $this->info('Will send HTTP Redirect (' . $http_code . '): ' . $url);

        $this->clear();
        $this->setStatusCode($http_code);
        $this->setLocation($url);

        // no processing for redirects
        $this->content_type = null;
        $this->content_should_be_processed = false;

        $this->sendResponse();
    }

    /*
     * Get the output charset encoding
    */

    public function setLocation($location)
    {
        $this->custom_headers->set('Location', $location);
    }

    /*
     * Sets the Response content type
    */

    public function disableCaching()
    {
        $this->setNoCaching();
    }

    /*
     * Sets the actual Response content
    */

    public function setNoCaching()
    {
        $this->setExpires('Sat, 26 Jul 1997 05:00:00 GMT');
        $this->setLastModified(gmdate("D, d M Y H:i:s") . ' GMT');
        $this->setCacheControl('max-age=0, post-check=0, pre-check=0, no-store, no-cache, must-revalidate');
        $this->setPragma('no-cache');

        if (DO_DEBUG) {
            $this->debug('disabled caching on response');
        }
    }

    /*
     * Get the current response cookies
    */

    public function setExpires($expires)
    {
        $this->custom_headers->set('Expires', $expires);
    }

    /*
     * Gets the current content type
    */

    public function setLastModified($last_modified)
    {
        $this->custom_headers->set('Last-Modified', $last_modified);
    }

    /*
     * gets the raw apache response headers (must flush the script before that!)
    * Platform-specific
    */
    /*private function getApacheRawResponseHeaders()
    {
        if (!function_exists('apache_response_headers')) {
            throw new lcSystemException('Cannot obtain apache response headers. Are you running on apache?');
        }

        flush();

        return apache_response_headers();
    }*/

    /*
     * Set a custom HTTP Response Code/Reason
    */

    public function setCacheControl($cache_control)
    {
        $this->custom_headers->set('Cache-Control', $cache_control);
    }

    /*
     * Get the current status code
    */

    public function setPragma($pragma)
    {
        $this->custom_headers->set('Pragma', $pragma);
    }

    /*
     * Get the current response reason string
    */

    public function getServerCharset()
    {
        return $this->server_charset;
    }

    /*
     * Sets the output content charset
    */

    public function getCookies()
    {
        return $this->cookies;
    }

    /*
     * HTTP Header:
    * Date
    */

    public function getContentType()
    {
        return $this->content_type;
    }

    /*
     * HTTP Header:
    * Via
    */

    public function setContentType($content_type = 'text/html')
    {
        $this->content_type = $content_type;
    }

    /*
     * HTTP Header:
    * Location
    */

    public function getStatusCode()
    {
        return $this->status_code;
    }

    /*
     * HTTP Header:
    * Version
    */

    public function setStatusCode($status_code, $reason_string = null)
    {
        $this->status_code = $status_code;

//        if (!$this->status_code = lcHttpStatusCode::getType($status_code)) {
//            throw new lcSystemException('Invalid HTTP Response Status Code');
//        }

        !$reason_string ?
            $this->reason_string = lcHttpStatusCode::getMessage($status_code) :
            $this->reason_string = $reason_string;

        if (DO_DEBUG) {
            $this->debug('set http status: ' . $status_code . ' : ' . $reason_string);
        }
    }

    /*
     * HTTP Header:
    * Content-Disposition
    */

    public function getReasonString()
    {
        return $this->reason_string;
    }

    /*
     * HTTP Header:
    * Content-Encoding
    */

    public function setCharset($charset)
    {
        $this->server_charset = $charset;
    }

    /*
     * HTTP Header:
    * Content-Language
    */

    public function setDate($date)
    {
        $this->custom_headers->set('Date', $date);
    }

    /*
     * HTTP Header:
    * Accept-Ranges
    */

    public function setVia($via)
    {
        $this->custom_headers->set('Via', $via);
    }

    /*
     * HTTP Header:
    * Age
    */

    public function setContentVersion($version)
    {
        $this->custom_headers->set('Version', $version);
    }

    /*
     * HTTP Header:
    * ETag
    */

    public function setContentEncoding($content_encoding)
    {
        $this->custom_headers->set('Content-Encoding', $content_encoding);
    }

    /*
     * HTTP Header:
    * Proxy-Authenticate
    */

    public function setContentLanguage($content_language)
    {
        $this->content_lang = $content_language;
        $this->custom_headers->set('Content-Language', $content_language);
    }

    /*
     * HTTP Header:
    * Retry-After
    */

    public function setAcceptRanges($accept_ranges)
    {
        $this->custom_headers->set('Accept-Ranges', $accept_ranges);
    }

    /*
     * HTTP Header:
    * Server
    */

    public function setAge($age)
    {
        $this->custom_headers->set('Age', $age);
    }

    /*
     * HTTP Header:
    * Vary
    */

    public function setETag($etag)
    {
        $this->custom_headers->set('ETag', $etag);
    }

    /*
     * HTTP Header:
    * WWW-Authenticate
    */

    public function setProxyAuthenticate($proxy_authenticate)
    {
        $this->custom_headers->set('Proxy-Authenticate', $proxy_authenticate);
    }

    /*
     * HTTP Header:
    * Connection
    */

    public function setRetryAfter($retry_after)
    {
        $this->custom_headers->set('Retry-After', $retry_after);
    }

    /*
     * HTTP Header:
    * Pragma
    */

    public function setServer($server)
    {
        $this->custom_headers->set('Server', $server);
    }

    /*
     * HTTP Header:
    * Expires
    */

    public function setVary($vary)
    {
        $this->custom_headers->set('Vary', $vary);
    }

    /*
     * HTTP Header:
    * Last-Modified
    */

    public function setWWWAuthenticate($www_authenticate)
    {
        $this->custom_headers->set('WWW-Authenticate', $www_authenticate);
    }

    /*
     * HTTP Header:
    * Cache-Control
    */

    public function setConnection($connection)
    {
        $this->custom_headers->set('Connection', $connection);
    }

    /*
     * HTTP Header:
    * Message-Id
    */

    public function setMessageId($message_id)
    {
        $this->custom_headers->set('Message-Id', $message_id);
    }

    /*
     * HTTP Header:
    * URI
    */
    public function setHttpUri($http_uri)
    {
        $this->custom_headers->set('URI', $http_uri);
    }

    /*
     * HTTP Header:
    * Version
    */
    public function setVersion($version)
    {
        $this->custom_headers->set('Version', $version);
    }

    /*
     * HTTP Header:
    * Derived-From
    */
    public function setDerivedFrom($derived_from)
    {
        $this->custom_headers->set('Derived-From', $derived_from);
    }

    /*
     * HTTP Header:
    * Cost
    */
    public function setCost($cost)
    {
        $this->custom_headers->set('Cost', $cost);
    }

    /*
     * HTTP Header:
    * Link
    */
    public function setLink($link)
    {
        $this->custom_headers->set('Link', $link);
    }

    /*
     * Clears all custom headers
    */
    public function clearHeaders()
    {
        $this->custom_headers->clear();
    }
}
