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

// Format of PO file: http://www.gnu.org/software/gettext/manual/html_node/PO-Files.html

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcPoFile.class.php 1517 2014-05-15 12:25:06Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1517 $
 */
class lcPoFile extends lcObj
{
    const DATE_FORMAT = 'Y-m-d H:iO';

    private $data_loaded;
    private $filename;

    private $description;
    private $headers = array();
    //private $charset = 'UTF-8';

    /*
     white-space
     #  translator-comments
     #. extracted-comments
     #: reference...
     #, flag...
     #| msgid previous-untranslated-string
     msgid untranslated-string
     msgstr translated-string
     */

    private $translator_comments = array();
    private $extracted_comments = array();
    private $references = array();
    private $flags = array();

    private $messages = array();

    public function __construct($filename = null)
    {
        parent::__construct();

        $this->setDefaultHeaders();

        if (isset($filename)) {
            $this->open($filename);
        }
    }

    public function open($filename)
    {
        if ($this->data_loaded && $filename == $this->filename) {
            return;
        }

        $this->data_loaded = false;
        $this->filename = $filename;
        $this->messages = array();
        $this->translator_comments = array();
        $this->extracted_comments = array();
        $this->references = array();
        $this->flags = array();
        $this->clearHeaders();

        // open and parse
        try {
            $fdata = file_get_contents($filename);

            if ($fdata) {
                $this->parseHeader($fdata);
                $this->parseMessages($fdata);
            }

            unset($fdata);

            if (!$this->headers) {
                $this->setDefaultHeaders();
            }

            $this->data_loaded = true;
        } catch (Exception $e) {
            throw new lcIOException('Cannot read from PO file: ' . $e->getMessage(), null, $e);
        }
    }

    public function mergeTemplate(array $template_messages)
    {
        $this->messages = (array)$this->messages;

        if (!count($this->messages)) {
            $this->messages = $template_messages;
            return true;
        } else {
            // add missing
            $this->messages = array_merge(
                $template_messages,
                $this->messages
            );

            $template_messages = array_keys($template_messages);

            // remove stale
            foreach ($this->messages as $msgid => $msgstr) {
                if (array_search($msgid, $template_messages) === false) {
                    unset($this->messages[$msgid]);
                }

                unset($msgid, $msgstr);
            }
        }

        return $this->messages;
    }

    public function getHeaders()
    {
        return (array)$this->headers;
    }

    public function clearHeaders()
    {
        $this->headers = array();
    }

    /*private function addslashMultilineMsgId($string)
    {
        if ($spl = array_filter(explode("\n", $string))) {
            if (count($spl)) {
                $str = array();

                foreach ($spl as $el) {
                    $str[] = '"' . $el . '"';
                    unset($el);
                }

                unset($spl);

                $string = implode("\n", $str);
                $string = substr($string, 1, strlen($string) - 2);
                unset($str);
            }
        }

        return $string;
    }*/

    /*private function addslashMultilineMsgStr($string)
    {
        if ($spl = array_filter(explode("\n", $string))) {
            if (count($spl)) {
                $str = array();

                foreach ($spl as $el) {
                    $str[] = '"' . $el . '\n"';
                    unset($el);
                }

                $str[count($str) - 1] = substr($str[count($str) - 1], 0, strlen($str[count($str) - 1]) - 2);
                unset($spl);

                $string = implode("\n", $str);
                $string = substr($string, 1, strlen($string) - 2);
                unset($str);
            }
        }

        return $string;
    }*/

    private function stripslashMultilineMsgId($string)
    {
        $string = str_replace('\"', '~*~', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace("\n", '', $string);
        $string = str_replace('~*~', '"', $string);
        return $string;
    }

    private function stripslashMultilineMsgStr($string)
    {
        $string = str_replace("\n", '', $string);
        $string = str_replace('\n', "\n", $string);
        $string = str_replace('\"', '~*~', $string);
        $string = str_replace('"', '', $string);
        $string = str_replace('~*~', '"', $string);
        return $string;
    }

    public function setDefaultHeaders()
    {
        $this->headers = array(
            'Project-Id-Version' => '1.0.0.0',
            'Report-Msgid-Bugs-To' => 'i18n@nimasystems.com',
            'POT-Creation-Date' => date(self::DATE_FORMAT),
            'PO-Revision-Date' => date(self::DATE_FORMAT),
            'Last-Translator' => 'Nimasystems Ltd',
            'Language' => '',
            'Language-Team' => 'Nimasystems Ltd Translation Team',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        );

        $this->description =
            '# TRANSLATION FILE' . "\n" .
            '# Copyright (C) ' . date('Y') . ' NIMASYSTEMS LTD' . "\n" .
            '# NIMASYSTEMS LTD <i18n@nimasystems.com> - ' . date('Y') . "\n" .
            '#';
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setHeader($name, $value = null)
    {
        $name = trim($name);
        $value = trim($value);

        if (!isset($value)) {
            if (isset($this->headers[$name])) {
                unset($this->headers[$name]);
                return;
            }
        } else {
            $this->headers[$name] = $value;
        }
    }

    private function parseHeader(&$data)
    {
        // parse description
        if (preg_match("/(.*?)msgid\s\"\"\nmsgstr\s\"\"/is", $data, $matches)) {
            if ($matches && is_array($matches)) {
                $this->description = trim($matches[1]);
            }

            unset($matches);
        }

        // parse headers
        if (preg_match_all("/\"(.*?):(.*?).n\"/i", $data, $matches)) {
            if ($matches && is_array($matches)) {
                $c = 0;
                foreach ($matches[1] as $header) {
                    $this->setHeader($header, $matches[2][$c]);
                    $c++;
                    unset($header);
                }
                unset($c);
            }
            unset($matches);
        }
    }

    private function parseMessages(&$data)
    {
        $tmp = $data;

        // remove \r chars as they are breaking things
        $tmp = str_replace("\r", '', $tmp);

        // first we isolate each separate string by finding empty lines (which act as separators)
        $found_strings = array_filter(explode("\n\n", $tmp));

        if (!$found_strings) {
            return;
        }

        $translator_comments = array();
        $extracted_comments = array();
        $references = array();
        $flags = array();
        $message_keys = array();
        $message_vals = array();

        $i = 0;

        foreach ($found_strings as $found) {
            $tmpex = array_filter(explode("\n", $found));

            if (!$tmpex) {
                continue;
            }

            $msgstr_next = false;

            foreach ($tmpex as $str) {
                $tmp = null;
                $ss = mb_strlen($str);

                if (mb_substr($str, 0, 2) == '# ') {
                    // translator comment
                    $tmp = trim(mb_substr($str, 2, $ss));

                    if ($tmp) {
                        $translator_comments[$i][$tmp] = $tmp;
                    }
                } elseif (mb_substr($str, 0, 3) == '#. ') {
                    // extracted comment
                    $tmp = trim(mb_substr($str, 3, $ss));

                    if ($tmp) {
                        $extracted_comments[$i][$tmp] = $tmp;
                    }
                } elseif (mb_substr($str, 0, 3) == '#: ') {
                    // referenced file
                    $tmp = trim(mb_substr($str, 3, $ss));

                    if ($tmp) {
                        $references[$i][$tmp] = $tmp;
                    }
                } elseif (mb_substr($str, 0, 3) == '#, ') {
                    // flag
                    $tmp = trim(mb_substr($str, 3, $ss));

                    if ($tmp) {
                        $flags[$i][$tmp] = $tmp;
                    }
                } elseif (mb_substr($str, 0, 8) == 'msgstr "' && mb_substr($str, $ss - 1, $ss) == '"') {
                    // msgstr line
                    $tmp = mb_substr($str, 8, $ss - 9);
                    $message_vals[$i] = $tmp;

                    $msgstr_next = true;
                } elseif (mb_substr($str, 0, 1) == '"' && mb_substr($str, $ss - 1, $ss) == '"') {
                    // msgid/msgstr extended lines
                    $res = mb_substr($str, 1, $ss - 2);

                    if ($msgstr_next) {
                        $message_vals[$i] = isset($message_vals[$i]) ? $message_vals[$i] . $res : $res;
                    } else {
                        $message_keys[$i] = isset($message_keys[$i]) ? $message_keys[$i] . $res : $res;
                    }

                    unset($res);
                } elseif (mb_substr($str, 0, 7) == 'msgid "' && mb_substr($str, $ss - 1, $ss) == '"') {
                    // msgid lines
                    $res = mb_substr($str, 7, $ss - 8);

                    if ($res && strlen($res)) {
                        $message_keys[$i] = isset($message_keys[$i]) ? $message_keys[$i] . $res : $res;
                    }

                    unset($res);
                }

                unset($str, $tmp, $ss);
            }

            $i++;
            unset($found, $tmpex);
        }

        // after separation - set to class
        if (!$message_keys) {
            return;
        }

        $translator_comments_ = array();
        $extracted_comments_ = array();
        $references_ = array();
        $flags_ = array();
        $messages_ = array();

        // start walking keys
        foreach ($message_keys as $idx => $key) {
            // messages
            $key_ = $this->unescapeMessage($this->stripslashMultilineMsgId($key));
            $val = isset($message_vals[$idx]) ? $this->unescapeMessage($this->stripslashMultilineMsgStr($message_vals[$idx])) : null;

            $messages_[$key_] = $val;

            // translation comments
            if (isset($translator_comments[$idx])) {
                foreach ($translator_comments[$idx] as $tmp) {
                    $tmp = $this->unescapeMessage($this->stripslashMultilineMsgId($tmp));
                    $translator_comments_[$key_][$tmp] = $tmp;

                    unset($tmp);
                }
            }

            // extracted comments
            if (isset($extracted_comments[$idx])) {
                foreach ($extracted_comments[$idx] as $tmp) {
                    $tmp = $this->unescapeMessage($this->stripslashMultilineMsgId($tmp));
                    $extracted_comments_[$key_][$tmp] = $tmp;

                    unset($tmp);
                }
            }

            // references
            if (isset($references[$idx])) {
                foreach ($references[$idx] as $tmp) {
                    $tmp = $this->unescapeMessage($this->stripslashMultilineMsgId($tmp));
                    $references_[$key_][$tmp] = $tmp;

                    unset($tmp);
                }
            }

            // flags
            if (isset($flags[$idx])) {
                foreach ($flags[$idx] as $tmp) {
                    $tmp = $this->unescapeMessage($this->stripslashMultilineMsgId($tmp));
                    $flags_[$key_][$tmp] = $tmp;

                    unset($tmp);
                }
            }

            unset($key, $idx, $key_, $val);
        }

        $this->translator_comments = array_filter($translator_comments_);
        $this->extracted_comments = array_filter($extracted_comments_);
        $this->references = array_filter($references_);
        $this->flags = array_filter($flags_);
        $this->messages = $messages_;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function save($filename = null)
    {
        if (!isset($filename) && (!$this->filename)) {
            throw new lcSystemException('Cannot save PO file - no filename set');
        }

        $filename = isset($filename) ? $filename : $this->filename;

        try {
            return lcFiles::putFile($filename, $this->compile());
        } catch (Exception $e) {
            throw new lcIOException('Cannot save PO file: ' . $e->getMessage(), null, $e);
        }
    }

    public function toMo($filename = null)
    {
        if (!isset($filename) && (!$this->filename)) {
            throw new lcSystemException('Cannot convert PO to MO - no filename set');
        }

        $filename = isset($filename) ? $filename : $this->filename;

        $fmt = new lcMsgFmt();

        $res = $fmt->process($filename);

        return $res;
    }

    public function __toString()
    {
        return $this->compile();
    }

    public function compile()
    {
        $contents = array();

        // set description
        if ($this->description) {
            $ex = explode("\n", $this->description);

            foreach ($ex as $l) {
                $contents[] = $l;

                unset($l);
            }

            unset($ex);
        }

        // set the headers
        $headers = $this->headers;

        if ($headers) {
            $contents[] = 'msgid ""';
            $contents[] = 'msgstr ""';

            foreach ($headers as $key => $value) {
                if (!$key) {
                    continue;
                }

                $contents[] = '"' . $this->slashMessageValue($key) . ': ' . $this->slashMessageValue($value) . '\n"';

                unset($key, $value);
            }

            unset($headers);
        }

        // messages
        $contents[] = '';

        $messages = $this->messages;
        $translator_comments = array_filter((array)$this->translator_comments);
        $extracted_comments = array_filter((array)$this->extracted_comments);
        $references = array_filter((array)$this->references);
        $flags = array_filter((array)$this->flags);

        if ($messages && is_array($messages)) {
            foreach ($messages as $msgid => $msgstr) {
                $msgid_ = $msgid;
                $msgid = $this->slashMessageValue($msgid);
                $msgstr = $this->slashMessageValue($msgstr);

                if (!$msgid) {
                    continue;
                }

                assert(is_string($msgid));
                assert(is_string($msgstr));

                // translator comments
                if ($translator_comments && isset($translator_comments[$msgid_])) {
                    foreach ($translator_comments[$msgid_] as $tmp) {
                        $contents[] = '# ' . $this->slashMessageValue($tmp);
                        unset($tmp);
                    }
                }

                // extracted comments
                if ($extracted_comments && isset($extracted_comments[$msgid_])) {
                    foreach ($extracted_comments[$msgid_] as $tmp) {
                        $contents[] = '#. ' . $this->slashMessageValue($tmp);
                        unset($tmp);
                    }
                }

                // references
                if ($references && isset($references[$msgid_])) {
                    foreach ($references[$msgid_] as $tmp) {
                        $contents[] = '#: ' . $this->slashMessageValue($tmp);
                        unset($tmp);
                    }
                }

                // flags
                if ($flags && isset($flags[$msgid_])) {
                    foreach ($flags[$msgid_] as $tmp) {
                        $contents[] = '#, ' . $this->slashMessageValue($tmp);
                        unset($tmp);
                    }
                }

                $contents[] = 'msgid "' . $msgid . '"' . "\n" . 'msgstr "' . $msgstr . '"' . "\n";

                unset($msgid, $msgid_, $msgstr);
            }
        }

        if (!$imploded = implode("\n", $contents)) {
            return false;
        }

        return $imploded;
    }

    private function slashMessageValue($value)
    {
        $value = str_replace("\'", "'", $value);
        $value = str_replace('"', '\\"', $value);
        $value = str_replace("\n", '', $value);
        $value = str_replace("\r", '', $value);

        return $value;
    }

    public function setMessage($msgid, $msgstr)
    {
        $this->messages[$msgid] = $msgstr;
    }

    public function setTranslatorComment($msgid, $comment)
    {
        $this->translator_comments[$msgid][$comment] = $comment;
    }

    public function setExtractedComment($msgid, $comment)
    {
        $this->extracted_comments[$msgid][$comment] = $comment;
    }

    public function setReferencedFile($msgid, $file)
    {
        $this->references[$msgid][$file] = $file;
    }

    public function setFlag($msgid, $flag)
    {
        $this->flags[$msgid][$flag] = $flag;
    }

    public function clearTranslatorComments($msgid = null)
    {
        if (!$msgid) {
            $this->translator_comments = array();
            return;
        }

        if (isset($this->translator_comments[$msgid])) {
            unset($this->translator_comments[$msgid]);
        }
    }

    public function clearExtractedComments($msgid = null)
    {
        if (!$msgid) {
            $this->extracted_comments = array();
            return;
        }

        if (isset($this->extracted_comments[$msgid])) {
            unset($this->extracted_comments[$msgid]);
        }
    }

    public function clearReferencedFiles($msgid = null)
    {
        if (!$msgid) {
            $this->references = array();
            return;
        }

        if (isset($this->references[$msgid])) {
            unset($this->references[$msgid]);
        }
    }

    public function clearFlags($msgid = null)
    {
        if (!$msgid) {
            $this->flags = array();
            return;
        }

        if (isset($this->flags[$msgid])) {
            unset($this->flags[$msgid]);
        }
    }

    public function hasMessage($msgid)
    {
        return isset($this->messages[$msgid]);
    }

    public function hasMessages()
    {
        return (bool)empty($this->messages);
    }

    public function getMessage($msgid)
    {
        return isset($this->messages[$msgid]) ?
            $this->messages[$msgid] :
            null;
    }

    /*private function escapeMessage($message)
    {
        $message = str_replace('"', '\"', $message);

        return $message;
    }*/

    private function unescapeMessage($message)
    {
        $message = str_replace('\"', '"', $message);

        return $message;
    }

    public function removeMessage($msgid)
    {
        if (!isset($this->messages[$msgid])) {
            return;
        }

        unset($this->messages[$msgid]);
    }

    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function getMessages()
    {
        return (array)$this->messages;
    }

    public function clearMessages()
    {
        $this->messages = array();
    }

    public function getMessageCount()
    {
        return count((array)$this->messages);
    }
}