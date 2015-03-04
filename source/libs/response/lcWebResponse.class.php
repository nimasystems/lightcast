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
 * @changed $Id: lcWebResponse.class.php 1536 2014-06-09 11:56:08Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1536 $
 */
class lcWebResponse extends lcResponse implements iKeyValueProvider, iDebuggable
{
    protected $request;

    protected $status_code = lcHttpStatusCode::OK;
    protected $http_version = lcHttpVersion::HTTPVER_1_1;
    protected $reason_string = lcHttpStatusCode::OK_MESSAGE;

    protected $exit_upon_send;

    protected $custom_headers;

    protected $server_charset = 'utf-8';
    protected $content_type = 'text/html';

    protected $no_http_errors_processing;

    protected $content;
    protected $output_content;

    private $cookies;

    protected $javascripts;
    protected $javascripts_end;
    protected $stylesheets;
    protected $metatags;
    protected $rssfeeds;
    protected $icon;
    protected $html_base;
    protected $title;
    protected $lang_dir;
    protected $body_tags;
    protected $html_head_custom;
    protected $html_body_custom;

    protected $allow_javascripts;
    protected $allow_rss_feeds;
    protected $allow_stylesheets;
    protected $allow_metatags;

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

        // default output type
        $this->content_type = (string)$this->configuration['view.content_type'];

        // charset
        $this->server_charset = (string)$this->configuration['view.charset'];

        // base
        $this->html_base = (string)$this->configuration['view.base'];

        // dir
        $this->lang_dir = (string)$this->configuration['view.dir'];

        // allowances
        $this->allow_javascripts = (bool)$this->configuration['view.allow_javascripts'];
        $this->allow_stylesheets = (bool)$this->configuration['view.allow_stylesheets'];
        $this->allow_rss_feeds = (bool)$this->configuration['view.allow_rss_feeds'];
        $this->allow_metatags = (bool)$this->configuration['view.allow_metatags'];

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

    public function getDebugInfo()
    {
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
            'status_code' => $this->status_code,
            'http_response_message' => $this->reason_string,
            'http_version' => $this->http_version,
            'custom_headers' => ($this->custom_headers ? $this->custom_headers->getKeyValueArray() : null),
            'charset' => $this->server_charset,
            'content_type' => $this->content_type,
            'cookies' => ($ca ? $ca : null),
        );

        return $debug;
    }

    public function getShortDebugInfo()
    {
        return false;
    }

    #pragma mark - iKeyValueProvider

    public function getAllKeys()
    {
        $ret = array(
            'page_title'
        );
        return $ret;
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

    public function getStylesheets()
    {
        return $this->stylesheets;
    }

    public function getJavascripts()
    {
        return $this->javascripts;
    }

    public function getJavascriptsEnd()
    {
        return $this->javascripts_end;
    }

    public function clear()
    {
        if ($this->response_sent) {
            return false;
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

    public function setNoContentProcessing($process = true)
    {
        $this->content_should_be_processed = !(bool)$process;
    }

    public function getNoContentProcessing()
    {
        return $this->content_should_be_processed;
    }

    public function setAllowJavascripts($allow = true)
    {
        $this->allow_javascripts = $allow;
    }

    public function setAllowStylesheets($allow = true)
    {
        $this->allow_stylesheets = $allow;
    }

    public function setAllowRssFeeds($allow = true)
    {
        $this->allow_rss_feeds = $allow;
    }

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

    /**
     * Removes all included css files
     */
    public function clearStylesheets()
    {
        $this->stylesheets = null;
    }

    /**
     * Removes an included js file
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

    /**
     * Removes an included css file
     */
    public function removeStylesheet($css_src)
    {
        if (isset($this->stylesheets[$css_src])) {
            unset($this->stylesheets[$css_src]);
        }
    }

    public function javascript($src, $type = 'text/javascript', $language = 'javascript', $at_end = false, array $other_attribs = null)
    {
        return $this->setJavascript($src, $type, $language, $at_end, $other_attribs);
    }

    /*
     * Set a javascript include
    * <script type="text/javascript" src=""></script>
    */
    public function setJavascript($src, $type = 'text/javascript', $language = 'javascript', $at_end = false, array $other_attribs = null)
    {
        if (is_array($src)) {
            foreach ($src as $s) {
                $this->setJavascript($s, $type, $language, $at_end, $other_attribs);
                unset($s);
            }
        } else {
            if ($at_end) {
                $this->javascripts_end[$src] = array('src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs);
            } else {
                $this->javascripts[$src] = array('src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs);
            }

            if (DO_DEBUG) {
                $this->debug('set javascript: ' . $src);
            }
        }
    }

    /*
     * Prepends a javascript - before all other javascripts
    */
    public function prependJavascript($src, $type = 'text/javascript', $language = 'javascript', $at_end = false, array $other_attribs = null)
    {
        $new = array();
        $new[$src] = array('src' => $src, 'type' => $type, 'language' => $language, 'other_attribs' => $other_attribs);

        if ($at_end) {
            $this->javascripts_end = array_merge($new, (array)$this->javascripts_end);
        } else {
            $this->javascripts = array_merge($new, (array)$this->javascripts);
        }

        if (DO_DEBUG) {
            $this->debug('prepend javascript: ' . $src);
        }
    }

    public function css($href, $media = 'all', $type = 'text/css')
    {
        return $this->setStylesheet($href, $media, $type);
    }

    /*
     * Set a css include
    * <link rel="stylesheet" type="text/css" href="" media="screen" />
    */
    public function setStylesheet($href, $media = 'all', $type = 'text/css')
    {
        if (is_array($href)) {
            foreach ($href as $h) {
                $this->setStylesheet($h, $media, $type);
                unset($h);
            }
        } else {
            $this->stylesheets[$href] = array('href' => $href, 'type' => $type, 'media' => $media);

            if (DO_DEBUG) {
                $this->debug('set stylesheet: ' . $href . ' : ' . $media);
            }
        }
    }

    public function clearMetatags()
    {
        $this->metatags = array();
    }

    /*
     * Set a metatag
    * <meta name="robots" content="" />
    */
    public function setMetatag($name, $value)
    {
        $this->metatags[$name] = $value;

        if (DO_DEBUG) {
            $this->debug('set metatag: ' . $name . '/' . $value);
        }
    }

    /*
     * Set a RSS feed
    * <link media="all" rel="alternate" type="application/rss+xml" title=""  href=""  />
    */
    public function setRSSFeed($href, $title = '', $media = 'all')
    {
        $this->rssfeeds[$href] = array('href' => $href, 'title' => $title, 'media' => $media);

        if (DO_DEBUG) {
            $this->debug('set rss feed: ' . $href);
        }
    }

    /*
     * Set a Favorite Icon
    * <link rel="icon" href="" type="image/png" />
    */
    public function setIcon($href, $type = 'image/png')
    {
        $this->icon = array('href' => $href, 'type' => $type);

        if (DO_DEBUG) {
            $this->debug('set icon: ' . $href);
        }
    }

    /*
     * Set Base
    * <base href="" />
    */
    public function setBase($href = null)
    {
        $this->html_base = $href;

        if (DO_DEBUG) {
            $this->debug('set base: ' . $href);
        }
    }

    /*
     * Set Title
    */
    public function setTitle($title)
    {
        $this->title = $title;

        if (DO_DEBUG) {
            $this->debug('set title: ' . $title);
        }
    }

    /*
     * Get Title
    */
    public function getTitle()
    {
        return $this->title;
    }

    /*
     * Set body tags
    */
    public function setBodyTag($name, $value)
    {
        $this->body_tags[$name] = $value;
    }

    /*
     * Set <head> custom
    */
    public function customHeadHtml($start = null, $end = null)
    {
        $this->html_head_custom[] = array('start' => $start, 'end' => $end);
    }

    /*
     * Set <body> custom
    */
    public function customBodyHtml($start = null, $end = null)
    {
        $this->html_body_custom[] = array('start' => $start, 'end' => $end);
    }

    /*
     * Insert/Append a Response Cookie
    */
    public function setCookie(lcCookie $cookie)
    {
        $this->cookies->append($cookie);

        if (DO_DEBUG) {
            $this->debug('set cookie: ' . $cookie->getName() . ' : ' . $cookie->getValue());
        }
    }

    public function getOutputContent()
    {
        return $this->output_content;
    }

    /*
     * Get the current Response content
    */
    public function getContent()
    {
        return $this->content;
    }

    /*
     * Check if the response headers haven't been already
    * sent out
    */
    public function headersSent()
    {
        return headers_sent();
    }

    public function sendHttpError($message = null)
    {
        $this->setStatusCode(lcHttpStatusCode::INTERNAL_ERROR);

        // allow listeners to update the errorous page content
        $this->content = $message ? $message : lcHttpStatusCode::getMessage($this->status_code);

        $this->send();
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

    public function send()
    {
        return $this->sendResponse();
    }

    /*
     * Send the Response
    * If the response headers have already been sent out
    * the script will silently stop
    */
    public function sendResponse()
    {
        if ($this->headersSent()) {
            return false;
        }

        if ($this->response_sent) {
            return false;
        }

        // check if response sending is allowed
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.should_send_response', $this, array()), array());

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

    public function setShouldExitUponSend($do_exit = true)
    {
        $this->exit_upon_send = (bool)$do_exit;
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

        $stylesheets = (array)$this->configuration['view.stylesheets'];

        if ($stylesheets) {
            $config_stylesheets = (array)$this->configuration['view.stylesheets'];

            foreach ($config_stylesheets as $type => $stylesheets) {
                if (!$stylesheets || !is_array($stylesheets)) {
                    continue;
                }

                foreach ($stylesheets as $sheet) {
                    // relative or absolute path
                    $p = ($sheet && $sheet{0} == '/') ? $sheet : $stylesheet_path . $sheet;

                    $this->stylesheets[$stylesheet_path . $sheet] = array(
                        'href' => $p,
                        'type' => 'text/css',
                        'media' => $type
                    );

                    unset($sheet, $p);
                }

                unset($name, $type);
            }

            unset($config_stylesheets);
        }

        unset($stylesheet_path);

        // javascripts
        $js_path = $this->configuration->getJavascriptPath();

        // start javascripts
        $javascripts = (array)$this->configuration['view.javascripts'];

        if ($javascripts) {
            foreach ($javascripts as $js) {
                // relative or absolute path
                $p = ($js && $js{0} == '/') ? $js : $js_path . $js;

                $this->javascripts[$js_path . $js] = array(
                    'src' => $p,
                    'type' => 'text/javascript',
                    'language' => 'javascript'
                );

                unset($js, $p);
            }

            unset($config_js);
        }

        // end javascripts
        $javascripts = (array)$this->configuration['view.javascripts_end'];

        if ($javascripts) {
            foreach ($javascripts as $js) {
                // relative or absolute path
                $p = ($js && $js{0} == '/') ? $js : $js_path . $js;

                $this->javascripts_end[$js_path . $js] = array(
                    'src' => $p,
                    'type' => 'text/javascript',
                    'language' => 'javascript'
                );

                unset($js, $p);
            }

            unset($config_js);
        }
    }

    /*
     * Parses the output content before sending
    * and sets XHTML specific tags
    */
    private function processHtmlContent($content)
    {
        if (!$content) {
            return;
        }

        $head = array();

        // html_base
        if ($this->html_base) {
            $head[] = '<base href="' . $this->html_base . '" />';
        }

        // title
        if ($this->title) {
            $head[] = '<title>' . htmlspecialchars($this->title) . '</title>';
        }

        // flush based on allowances
        if (!$this->allow_metatags) {
            $this->metatags = array();
        }

        if (!$this->allow_javascripts) {
            $this->javascripts = array();
            $this->javascripts_end = array();
        }

        if (!$this->allow_stylesheets) {
            $this->stylesheets = array();
        }

        if (!$this->allow_rss_feeds) {
            $this->rssfeeds = array();
        }

        // process view based config vars
        $this->processViewConfiguration();

        // meta equiv
        // http-equiv="Content-Type" content="' . $this->content_type . ';
        $head[] = '<meta charset=' . $this->server_charset . '" />';

        // html5 compat
        //$head[] = '<!-- From HTML 5 Boilerplate: Use .htaccess instead. See: h5bp.com/i/378 -->';
        $head[] = '<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />';

        // metatags
        $metatags = $this->metatags;

        if ($metatags) {
            foreach ($metatags as $name => $value) {
                $head[] =
                    '<meta name="' . $name . '" content="' . $value . '" />';

                unset($name, $value);
            }
        }

        unset($metatags);

        // icon
        $icon = $this->icon;

        if ($icon) {
            $head[] = '<link rel="icon" href="' . $icon['href'] . '" type="' . $icon['type'] . '" />';
        }

        unset($icon);

        // stylesheets
        $stylesheets = $this->stylesheets;

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

        if ($javascripts) {
            foreach ($javascripts as $src => $data) {
                $oattr = array();
                if (isset($data['other_attribs']) && $data['other_attribs']) {
                    foreach ($data['other_attribs'] as $key => $v) {
                        $oattr[] = $key . '="' . htmlspecialchars($v) . '"';
                        unset($key, $v);
                    }
                }
                $scr = '<script type="' . $data['type'] . '" src="' . $data['src'] . '"' . ($oattr ? ' ' . implode(' ', $oattr) : null) . '></script>';
                $head[] = $scr;

                unset($src, $data, $oattr);
            }
        }

        // javascripts end
        $javascripts = $this->javascripts_end;

        if ($javascripts) {
            foreach ($javascripts as $src => $data) {
                $oattr = array();
                if (isset($data['other_attribs']) && $data['other_attribs']) {
                    foreach ($data['other_attribs'] as $key => $v) {
                        $oattr[] = $key . '="' . htmlspecialchars($v) . '"';
                        unset($key, $v);
                    }
                }
                $scr = '<script type="' . $data['type'] . '" src="' . $data['src'] . '"' . ($oattr ? ' ' . implode(' ', $oattr) : null) . '></script>';
                $this->html_body_custom['end'][] = $scr;

                unset($src, $data);
            }
        }

        unset($javascripts);

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

        // google analytics
        $google_analytics = (string)$this->configuration['view.google_analytics'];

        if ($google_analytics) {
            $this->html_body_custom['end'][] =
                '<script type="text/javascript">/* <![CDATA[ */
					var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
					document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
					/* ]]> */</script>
					<script type="text/javascript">
					/* <![CDATA[ */
					try {
					var pageTracker = _gat._getTracker("' . $google_analytics . '");
							pageTracker._trackPageview();
		} catch(err) {}/* ]]> */</script>';
        }

        // custom body end
        $html_body_custom = $this->html_body_custom;

        if (isset($html_body_custom['end'])) {
            $content = preg_replace("/\<\/body\>/i", implode("\n", $html_body_custom['end']) . '</body>', $content);
        }

        // head parts
        $imploded = implode("\n", $head);

        if ($imploded) {
            $content = preg_replace("/\<head\>/i", '<head>' . $imploded, $content);
        }

        unset($head);

        // custom body tags
        $body_tags1 = $this->body_tags;

        if ($body_tags1) {
            $body_tags = array();

            foreach ($body_tags1 as $name => $value) {
                $body_tags[] = $name . '="' . $value . '"';

                unset($name, $value);
            }

            $content = preg_replace("/\<body/i", '<body ' . implode(' ', $body_tags), $content);

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
                $content = preg_replace("/\<head\>/i", '<head>' . $start, $content);
            }

            if ($end) {
                $content = preg_replace("/\<\/head\>/i", $end . '</head>', $content);
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
                $content = preg_replace("/\<body(.*?)\>/i", '<body>' . "\n" . $start, $content);
            }

            if ($end) {
                $content = preg_replace("/\<\/body\>/i", $end . "\n" . '</body>', $content);
            }

            unset($start, $end);
        }

        unset($html_body_custom);

        unset($body_tags1);

        return $content;
    }

    public function setDontProcessHttpErrors($dont = true)
    {
        $this->no_http_errors_processing = $dont;
    }

    /*
     * Internal response send
    */
    protected function _internalSend()
    {
        if (!$this->server_charset) {
            $this->server_charset = isset($this->configuration['view.charset']) ? (string)$this->configuration['view.charset'] : 'utf-8';
        }

        if (!$this->content_type) {
            $this->content_type = isset($this->configuration['view.content_type']) ? (string)$this->configuration['view.content_type'] : 'text/html';
        }

        $content = $this->content;
        $this->content = null;

        // if exit code is error - do not process content
        if ($this->exit_code != 0 || !is_string($content)) {
            $this->content_should_be_processed = false;
        }

        // notify listeners if this is an errorous response
        if (!$this->no_http_errors_processing && $this->status_code != lcHttpStatusCode::OK) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.send_http_error', $this, array(
                    'status_code' => $this->status_code,
                )), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }
        }

        // notify listeners
        if ($this->content_should_be_processed) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.send_response', $this, array()), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }

            unset($event);
        }

        // notify with an event
        $this->event_dispatcher->notify(
            new lcEvent('response.will_send_response', $this, array()));

        if ($this->content_should_be_processed) {
            // set html customizations
            if ($this->content_type == 'text/html') {
                $content = $this->processHtmlContent($content);
            }
        }

        // notify listeners
        if ($this->content_should_be_processed) {
            $event = $this->event_dispatcher->filter(
                new lcEvent('response.output_content', $this, array()), $content);

            if ($event->isProcessed()) {
                $content = $event->getReturnValue();
            }

            unset($event);
        }

        $this->output_content = $content;

        // IMPORTANT: COOKIES / HEADERS MUST BE SENT LAST!

        // send the cookies
        $this->sendCookies();

        // send the headers
        $this->sendHeaders();
    }

    protected function sendCookies()
    {
        $cookies = $this->cookies;

        if (!$cookies) {
            return;
        }

        // notify with an event
        $event = $this->event_dispatcher->filter(
            new lcEvent('response.send_cookies', $this, array()), $cookies);

        if ($event->isProcessed()) {
            $cookies = $event->getReturnValue();
        }

        if ($cookies && $cookies instanceof lcCookiesCollection) {
            $log = array();
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

                if (DO_DEBUG) {
                    $log[] = $sl->getName() . ': ' . $sl->getValue();
                }

                unset($sl, $set);
            }

            if (DO_DEBUG && $log) {
                $this->debug('response has output cookies: ' . "\n\n" . implode("\n", $log));
            }
        }
    }

    protected function sendHeaders()
    {
        $prepared_headers = array();

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
            new lcEvent('response.send_headers', $this, array()), $prepared_headers);

        if ($event->isProcessed()) {
            $prepared_headers = $event->getReturnValue();
        }

        if ($prepared_headers) {
            foreach ($prepared_headers as $header) {
                header($header);

                unset($header);
            }
        }
    }

    protected function _outputContent()
    {
        $this->clear();

        $content = $this->output_content;

        if ($content && is_resource($content)) {
            fpassthru($content);
            fclose($content);
        } else {
            echo $content;
        }
    }

    /*
     * Set a custom Response Header
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
     * Send a HTTP Redirect and stop script
    */
    public function redirect($url, $http_code = 302)
    {
        $this->clear();
        $this->setStatusCode($http_code);
        $this->setLocation($url);

        $this->info('Will send HTTP Redirect (' . $http_code . '): ' . $url);

        $this->sendResponse();
    }

    public function disableCaching()
    {
        $this->setNoCaching();
    }

    /*
     * Disable response caching by outputing
    * Cache-Control and Expires headers
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
     * Get the output charset encoding
    */
    public function getServerCharset()
    {
        return $this->server_charset;
    }

    /*
     * Sets the Response content type
    */
    public function setContentType($content_type = 'text/html')
    {
        $this->content_type = $content_type;
    }

    /*
     * Sets the actual Response content
    */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /*
     * Get the current response cookies
    */
    public function getCookies()
    {
        return $this->cookies;
    }

    /*
     * Gets the current content type
    */
    public function getContentType()
    {
        return $this->content_type;
    }

    /*
     * gets the raw apache response headers (must flush the script before that!)
    * Platform-specific
    */
    private function getApacheRawResponseHeaders()
    {
        if (!function_exists('apache_response_headers')) {
            throw new lcSystemException('Cannot obtain apache response headers. Are you running on apache?');
        }

        flush();

        return apache_response_headers();
    }

    /*
     * Set a custom HTTP Response Code/Reason
    */
    public function setStatusCode($status_code, $reason_string = null)
    {
        if (!$this->status_code = lcHttpStatusCode::getType($status_code)) {
            throw new lcSystemException('Invalid HTTP Response Status Code');
        }

        !$reason_string ?
            $this->reason_string = lcHttpStatusCode::getMessage($status_code) :
            $this->reason_string = $reason_string;

        if (DO_DEBUG) {
            $this->debug('set http status: ' . $status_code . ' : ' . $reason_string);
        }
    }

    /*
     * Get the current status code
    */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /*
     * Get the current response reason string
    */
    public function getReasonString()
    {
        return $this->reason_string;
    }

    /*
     * Sets the output content charset
    */
    public function setCharset($charset)
    {
        $this->server_charset = $charset;
    }

    /*
     * HTTP Header:
    * Date
    */
    public function setDate($date)
    {
        return $this->custom_headers->set('Date', $date);
    }

    /*
     * HTTP Header:
    * Via
    */
    public function setVia($via)
    {
        return $this->custom_headers->set('Via', $via);
    }

    /*
     * HTTP Header:
    * Location
    */
    public function setLocation($location)
    {
        return $this->custom_headers->set('Location', $location);
    }

    /*
     * HTTP Header:
    * Version
    */
    public function setContentVersion($version)
    {
        return $this->custom_headers->set('Version', $version);
    }

    /*
     * HTTP Header:
    * Content-Disposition
    */
    public function setContentDisposition($content_disposition)
    {
        return $this->custom_headers->set('Content-Disposition', $content_disposition);
    }

    /*
     * HTTP Header:
    * Content-Encoding
    */
    public function setContentEncoding($content_encoding)
    {
        return $this->custom_headers->set('Content-Encoding', $content_encoding);
    }

    /*
     * HTTP Header:
    * Content-Language
    */
    public function setContentLanguage($content_language)
    {
        return $this->custom_headers->set('Content-Language', $content_language);
    }

    /*
     * HTTP Header:
    * Accept-Ranges
    */
    public function setAcceptRanges($accept_ranges)
    {
        return $this->custom_headers->set('Accept-Ranges', $accept_ranges);
    }

    /*
     * HTTP Header:
    * Age
    */
    public function setAge($age)
    {
        return $this->custom_headers->set('Age', $age);
    }

    /*
     * HTTP Header:
    * ETag
    */
    public function setETag($etag)
    {
        return $this->custom_headers->set('ETag', $etag);
    }

    /*
     * HTTP Header:
    * Proxy-Authenticate
    */
    public function setProxyAuthenticate($proxy_authenticate)
    {
        return $this->custom_headers->set('Proxy-Authenticate', $proxy_authenticate);
    }

    /*
     * HTTP Header:
    * Retry-After
    */
    public function setRetryAfter($retry_after)
    {
        return $this->custom_headers->set('Retry-After', $retry_after);
    }

    /*
     * HTTP Header:
    * Server
    */
    public function setServer($server)
    {
        return $this->custom_headers->set('Server', $server);
    }

    /*
     * HTTP Header:
    * Vary
    */
    public function setVary($vary)
    {
        return $this->custom_headers->set('Vary', $vary);
    }

    /*
     * HTTP Header:
    * WWW-Authenticate
    */
    public function setWWWAuthenticate($www_authenticate)
    {
        return $this->custom_headers->set('WWW-Authenticate', $www_authenticate);
    }

    /*
     * HTTP Header:
    * Connection
    */
    public function setConnection($connection)
    {
        return $this->custom_headers->set('Connection', $connection);
    }

    /*
     * HTTP Header:
    * Pragma
    */
    public function setPragma($pragma)
    {
        return $this->custom_headers->set('Pragma', $pragma);
    }

    /*
     * HTTP Header:
    * Expires
    */
    public function setExpires($expires)
    {
        return $this->custom_headers->set('Expires', $expires);
    }

    /*
     * HTTP Header:
    * Last-Modified
    */
    public function setLastModified($last_modified)
    {
        return $this->custom_headers->set('Last-Modified', $last_modified);
    }

    /*
     * HTTP Header:
    * Cache-Control
    */
    public function setCacheControl($cache_control)
    {
        return $this->custom_headers->set('Cache-Control', $cache_control);
    }

    /*
     * HTTP Header:
    * Message-Id
    */
    public function setMessageId($message_id)
    {
        return $this->custom_headers->set('Message-Id', $message_id);
    }

    /*
     * HTTP Header:
    * URI
    */
    public function setHttpUri($http_uri)
    {
        return $this->custom_headers->set('URI', $http_uri);
    }

    /*
     * HTTP Header:
    * Version
    */
    public function setVersion($version)
    {
        return $this->custom_headers->set('Version', $version);
    }

    /*
     * HTTP Header:
    * Derived-From
    */
    public function setDerivedFrom($derived_from)
    {
        return $this->custom_headers->set('Derived-From', $derived_from);
    }

    /*
     * HTTP Header:
    * Cost
    */
    public function setCost($cost)
    {
        return $this->custom_headers->set('Cost', $cost);
    }

    /*
     * HTTP Header:
    * Link
    */
    public function setLink($link)
    {
        return $this->custom_headers->set('Link', $link);
    }

    /*
     * Clears all custom headers
    */
    public function clearHeaders()
    {
        return $this->custom_headers->clear();
    }
}

?>