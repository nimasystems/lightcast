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
 * @changed $Id: lcStrings.class.php 1563 2014-12-09 14:09:50Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1563 $
 */
class lcStrings
{
    const LATIN_CHARS = 'qwertyuiopasdfghjklzxcvbnm';
    const NUMBERS = '1234567890';
    const WORD_COUNT_MASK = "/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}\p{Nd}]*/u";

    protected static $dict_index = array();
    protected static $dict_data = array();

    public static function startsWith($haystack, $needle)
    {
        return ($haystack{0} == $needle{0}) ? strncmp($haystack, $needle, strlen($needle)) === 0 : false;
    }

    public static function endsWith($haystack, $needle, $case = true)
    {
        if ($case) {
            return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
        }

        return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
    }

    public static function contains($haystack, $needle)
    {
        return strstr($haystack, $needle);
    }

    public static function getLatinChars()
    {
        $narr = array();
        $str = self::LATIN_CHARS;

        for ($i = 0; $i < strlen($str); $i++) {
            $narr[] = $str{$i};
        }

        return $narr;
    }

    public static function permaLink(array $key_cols, array $keywords, $prefix = null)
    {
        if (isset($prefix)) {
            if ($prefix{0} != '/') {
                $prefix = '/' . $prefix;
            }
        }

        $key_separator = '-';
        $segment_separator = '/';

        $key_ = isset($key_cols) ? implode($key_separator, $key_cols) : null;

        if (isset($keywords)) {
            $keys = array();

            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 60) {
                    $keyword = substr($keyword, 0, 60);
                }

                $keys[] = trim(self::keyLink($keyword));
                unset($keyword);
            }

            $keywords_ = implode($segment_separator, $keys);
        }

        $url =
            (isset($prefix) ? $prefix . '/' : null) .
            (($key_ && $keywords_) ? $key_ . '/' : null) .
            ($keywords_ ? $keywords_ : null);

        return $url;
    }

    public static function protectEmail($mail)
    {
        $len = strlen($mail);
        $i = 0;

        $par = array();

        while ($i < $len) {
            $c = mt_rand(1, 4);
            $par[] = substr($mail, $i, $c);
            $i += $c;
        }

        return 'javascript:location=\'ma\'+\'il\'+\'to:' . implode('\'+ \'', $par) . '\'';
    }

    public static function tokenize($string)
    {
        $tokens = array();
        preg_match_all('/"[^"]+"|[^"\s,]+/', $string, $tokens);

        if (!$tokens) {
            return null;
        }

        return $tokens[0];
    }

    public static function getTopKeywords($string, $min_word_len = 3, $max_words = 30, $imploded = false)
    {
        $skipwords = array(
            'and', 'the', 'is', 'of', 'from', 'to', 'an', 'a', 'with',
            'are', 'me', 'she', 'because', 'otherwise', 'without', 'select',
            'selected', 'were', 'by', 'in', 'his', 'her', 'at', 'to'
        );

        if (!isset($min_word_len)) {
            $min_word_len = 3;
        }

        $string = lcUnicode::strtolower($string);
        $tokens = self::tokenize($string);
        $tokens = self::unhtmlentities(implode('|', $tokens));
        $tokens = explode('|', $tokens);

        $latinchars = self::getLatinChars();

        $narr = array();

        foreach ($tokens as $key => $token) {
            $str = '';
            $len = strlen($token);

            for ($i = 0; $i < $len; $i++) {
                if (!in_array(lcUnicode::strtolower($token{$i}), $latinchars)) {
                    continue;
                }
                $str .= $token{$i};
            }

            $token = $str;

            if (strlen($token) < $min_word_len || in_array($token, $skipwords)) {
                continue;
            }

            $narr[] = $token;
            unset($key, $token);
        }

        $narr = array_count_values($narr);
        arsort($narr);
        $narr = array_reverse($narr);
        $narr = array_slice($narr, count($narr) - $max_words);
        $narr = array_reverse($narr);

        if ($imploded) {
            $str = '';

            foreach ($narr as $k => $v) {
                $str .= $k . ',';
                unset($k, $v);
            }

            $str = substr($str, 0, strlen($str) - 1);

            return $str;
        }

        return $narr;
    }

    # prepare a string for javascript
    public static function slashJs($string)
    {
        $o = "";
        $l = strlen($string);

        for ($i = 0; $i < $l; $i++) {
            $c = $string[$i];

            switch ($c) {
                case '<':
                    $o .= '\\x3C';
                    break;
                case '>':
                    $o .= '\\x3E';
                    break;
                case '\'':
                    $o .= '\\\'';
                    break;
                case '\\':
                    $o .= '\\\\';
                    break;
                case '"':
                    $o .= '\\"';
                    break;
                case "\n":
                    $o .= '\\n';
                    break;
                case "\r":
                    $o .= '\\r';
                    break;
                default:
                    $o .= $c;
            }
        }
        return $o;
    }

    // stripslashes - recursive on arrays
    public static function slashStrip($value)
    {
        if (is_array($value)) {
            $return = array_map('lcStrings::slashStrip', $value);
            return $return;
        } else {
            $return = stripslashes($value);
            return $return;
        }
    }

    // addslashes - recursive on arrays
    public static function slashAdd($value)
    {
        if (is_array($value)) {
            $return = array_map('lcStrings::slashAdd', $value);
            return $return;
        } else {
            $return = addslashes($value);
            return $return;
        }
    }

    public static function trimAll($string)
    {
        if (strlen($string) < 1) {
            return '';
        }

        return (string)str_replace(' ', '', $string);
    }

    public static function parseUrlQueryString($url)
    {
        if (!$url) {
            return false;
        }

        $p = parse_url($url);
        $query = isset($p['query']) ? (string)$p['query'] : null;

        if (!$query) {
            return false;
        }

        $query = explode('&', $query);

        $params = array();

        foreach ($query as $t) {
            $t = explode('=', $t);

            if (!isset($t[0]) || !isset($t[1])) {
                continue;
            }

            $params[$t[0]] = $t[1];

            unset($t);
        }

        return $params;
    }

    public static function toAlphaNum($string, array $allowed = array())
    {
        $allow = null;

        if (!empty($allowed)) {
            foreach ($allowed as $value) {
                $allow .= "\\$value";
            }
        }

        $cleaned = null;

        if (is_array($string)) {
            foreach ($string as $key => $clean) {
                $cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $clean);
            }
        } else {
            $cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $string);
        }

        return $cleaned;
    }

    public static function randomString($length, $readable = false)
    {
        mt_srand((double)microtime() * 1000000);

        $string = '';

        if ($readable) {
            $possible_charactors = "abcdefghmnprstuvwz23457ABCDEFGHMNPRSTUVWYZ";
        } else {
            $possible_charactors = "abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }


        for ($i = 0; $i < $length; $i++) {
            if ($readable) {
                $string .= substr($possible_charactors, mt_rand(0, 41), 1);
            } else {
                $string .= substr($possible_charactors, mt_rand(0, 61), 1);
            }
        }

        return $string;
    }

    public static function strCaseSplit($string)
    {
        $parts = array();

        $t = 0;
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $tmp = $string{$i};
            if (strtoupper($tmp) === $tmp) {
                ++$t;
            }
            @$parts[$t] .= $tmp;
        }

        return $parts;
    }

    public static function htmlEncodeString($string)
    {
        return htmlspecialchars($string);
    }

    public static function unhtmlentities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        $ret = strtr($string, $trans_tbl);

        return preg_replace('/&#(\d+);/me', "chr('\\1')", $ret);
    }

    public static function htmlToClearText($htmlcode, $no_new_lines = false)
    {
        $htmlcode = strip_tags($htmlcode);

        if ($no_new_lines) {
            $htmlcode = str_replace("\n", ' ', $htmlcode);
            $htmlcode = str_replace("\r", ' ', $htmlcode);
        }

        return $htmlcode;
    }

    public static function getLongTailKeywords($str, $len = 3, $min = 2)
    {
        if (!$str) {
            return array();
        }

        $keywords = array();
        $common = array('i', 'a', 'about', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'the', 'www');
        $str = preg_replace('/[^a-z0-9\s-]+/', '', strtolower(strip_tags($str)));
        $str = preg_split('/\s+-\s+|\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);

        while (0 < $len--) {
            for ($i = 0; $i < count($str) - $len; $i++) {
                $word = array_slice($str, $i, $len + 1);

                if (in_array($word[0], $common) || in_array(end($word), $common)) {
                    continue;
                }

                $word = implode(' ', $word);

                if (!isset($keywords[$len][$word])) {
                    $keywords[$len][$word] = 0;
                }

                $keywords[$len][$word]++;
            }
        }

        $return = array();

        foreach ($keywords as &$keyword) {
            $keyword = array_filter($keyword, function ($v) use ($min) {
                return !!($v > $min);
            });

            arsort($keyword);
            $return = array_merge($return, $keyword);
        }

        return $return;
    }

    /*
     * Deprecated
    */
    public static function keyLink($txt)
    {
        if (strlen($txt) < 1) {
            return false;
        }

        $txt = str_replace('@', ' ', $txt);
        $txt = str_replace('%', ' ', $txt);

        $txt = str_replace('+', ' ', $txt);
        $txt = str_replace('/', ' ', $txt);
        $txt = str_replace('.', ' ', $txt);

        $txt = str_replace('_', ' ', $txt);

        $txt = urlencode($txt);
        return $txt;
    }

    public static function keyLink_1($txt)
    {
        $t = (array)self::str_word_count_utf8($txt, 1);

        if (!$t) {
            return null;
        }

        $txt = implode('-', $t);
        $txt = self::sluggable($txt);
        return $txt;
    }

    public static function sluggable($string, $separator = '-')
    {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = array('&' => 'and');
        $string = mb_strtolower(trim($string), 'UTF-8');
        $string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
        $string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
        $string = preg_replace("/[^\w\d]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }

    public static function str_word_count_utf8($string, $format = 0)
    {
        if (!$string) {
            return null;
        }

        $ret = array();

        switch ($format) {
            case 1:
                preg_match_all(self::WORD_COUNT_MASK, $string, $matches);
                $ret = $matches[0];
                break;
            case 2:
                preg_match_all(self::WORD_COUNT_MASK, $string, $matches, PREG_OFFSET_CAPTURE);
                $result = array();

                foreach ($matches[0] as $match) {
                    $result[$match[1]] = $match[0];
                }

                $ret = $result;
                break;
            default:
                $ret = preg_match_all(self::WORD_COUNT_MASK, $string, $matches);
                break;
        }
        return $ret;
    }

    public static function shorten($string, $max_len)
    {
        $str = (strlen($string) > $max_len) ? lcUnicode::substr($string, 0, $max_len) : $string;
        return $str;
    }

    /**
     * Checks if a string is an utf8.
     *
     * Yi Stone Li<yili@yahoo-inc.com>
     * Copyright (c) 2007 Yahoo! Inc. All rights reserved.
     * Licensed under the BSD open source license
     *
     *
     * @return bool true if $string is valid UTF-8 and false otherwise.
     */
    public static function isUTF8($string)
    {
        $len = strlen($string);
        for ($idx = 0, $strlen = $len; $idx < $strlen; $idx++) {
            $byte = ord($string[$idx]);

            if ($byte & 0x80) {
                if (($byte & 0xE0) == 0xC0) {
                    // 2 byte char
                    $bytes_remaining = 1;
                } else if (($byte & 0xF0) == 0xE0) {
                    // 3 byte char
                    $bytes_remaining = 2;
                } else if (($byte & 0xF8) == 0xF0) {
                    // 4 byte char
                    $bytes_remaining = 3;
                } else {
                    return false;
                }

                if ($idx + $bytes_remaining >= $strlen) {
                    return false;
                }

                while ($bytes_remaining--) {
                    if ((ord($string[++$idx]) & 0xC0) != 0x80) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public static function utf8_substr($str, $offset, $length = null)
    {
        if (function_exists('mb_substr')) {
            if ($length === null) {
                return mb_substr($str, $offset);
            } else {
                return mb_substr($str, $offset, $length);
            }
        }

        /*
         * Notes:
        *
        * no mb string support, so we'll use pcre regex's with 'u' flag
        * pcre only supports repetitions of less than 65536, in order to accept up to MAXINT values for
        * offset and length, we'll repeat a group of 65535 characters when needed (ok, up to MAXINT-65536)
        *
        * substr documentation states false can be returned in some cases (e.g. offset > string length)
        * mb_substr never returns false, it will return an empty string instead.
        *
        * calculating the number of characters in the string is a relatively expensive operation, so
        * we only carry it out when necessary. It isn't necessary for +ve offsets and no specified length
        */

        // cast parameters to appropriate types to avoid multiple notices/warnings
        $str = (string)$str;                          // generates E_NOTICE for PHP4 objects, but not PHP5 objects
        $offset = (int)$offset;
        if (!is_null($length)) {
            $length = (int)$length;
        }

        // handle trivial cases
        if ($length === 0) {
            return '';
        }

        if ($offset < 0 && $length < 0 && $length < $offset) {
            return '';
        }

        $offset_pattern = '';
        $length_pattern = '';

        // normalise -ve offsets (we could use a tail anchored pattern, but they are horribly slow!)
        if ($offset < 0) {
            $strlen = strlen(utf8_decode($str));        // see notes
            $offset = $strlen + $offset;
            if ($offset < 0) {
                $offset = 0;
            }
        }

        // establish a pattern for offset, a non-captured group equal in length to offset
        if ($offset > 0) {
            $Ox = (int)($offset / 65535);
            $Oy = $offset % 65535;

            if ($Ox) {
                $offset_pattern = '(?:.{65535}){' . $Ox . '}';
            }

            $offset_pattern = '^(?:' . $offset_pattern . '.{' . $Oy . '})';
        } else {
            $offset_pattern = '^';                      // offset == 0; just anchor the pattern
        }

        // establish a pattern for length
        if (is_null($length)) {
            $length_pattern = '(.*)$';                  // the rest of the string
        } else {
            if (!isset($strlen)) {
                $strlen = strlen(utf8_decode($str));    // see notes
            }

            if ($offset > $strlen) {
                return '';           // another trivial case
            }

            if ($length > 0) {

                $length = min($strlen - $offset, $length);  // reduce any length that would go passed the end of the string

                $Lx = (int)($length / 65535);
                $Ly = $length % 65535;

                // +ve length requires ... a captured group of length characters
                if ($Lx) {
                    $length_pattern = '(?:.{65535}){' . $Lx . '}';
                }

                $length_pattern = '(' . $length_pattern . '.{' . $Ly . '})';

            } else if ($length < 0) {

                if ($length < ($offset - $strlen)) {
                    return '';
                }

                $Lx = (int)((-$length) / 65535);
                $Ly = (-$length) % 65535;

                // -ve length requires ... capture everything except a group of -length characters
                //                         anchored at the tail-end of the string
                if ($Lx) {
                    $length_pattern = '(?:.{65535}){' . $Lx . '}';
                }

                $length_pattern = '(.*)(?:' . $length_pattern . '.{' . $Ly . '})$';
            }
        }

        if (!preg_match('#' . $offset_pattern . $length_pattern . '#us', $str, $match)) {
            return '';
        }

        return $match[1];
    }

    public static function utf8_strlen($string)
    {
        return strlen(utf8_decode($string));
    }

    public static function prettyTrim($string, $len = 100, $formater = '..')
    {
        $overflow = false;

        //if($formater instanceof HtmlBaseTag) $formater = $formater->asHtml();

        if (strlen($string) > $len) {
            $cut_to = (int)strpos($string, ' ', $len);

            if ($cut_to - $len > 10) {
                $overflow = true;
            } elseif ($cut_to - $len < -10) {
                $overflow = true;
            }

            if ($cut_to && !$overflow) {
                $string = self::utf8_substr($string, 0, $cut_to) . $formater;
            } else {
                $string = self::utf8_substr($string, 0, $len);
            }
        }

        return $string;
    }

    /**
     *    Calculates Age by birh date
     *
     * @return (int)
     */
    public static function getAgeByBirthDate($date)
    {
        $year = date('Y', $date);
        $month = date('m', $date);
        $day = date('d', $date);

        $year_diff = date('Y') - $year;

        if (date("m") < $month || (date("m") == $month && date("d") < $day)) {
            $year_diff--;
        }

        return $year_diff;
    }

    /**
     * Gets a random word from the dictionary database, minimum len is 3, max is 12
     * @param <int> $minLen
     * @param <int> $maxLen
     *
     * @return <string> $word
     */
    public static function getWord($minLen = 3, $maxLen = 12)
    {
        $dir = ROOT . DS . "source" . DS . "libs" . DS . "utils" . DS;
        if (empty(self::$dict_data)) {
            self::$dict_data = file($dir . "data/dictionary/dict_data.dat");
        }

        if (empty(self::$dict_index)) {
            self::$dict_index = unserialize(file_get_contents($dir . "data/dictionary/dict_index.dat"));
        }

        if ($maxLen < $minLen) {
            $maxLen = $minLen;
        }

        $start_range = self::$dict_index[$minLen]['start'];
        $end_range = self::$dict_index[$maxLen]['end'];

        srand();

        $word_line = rand($start_range, $end_range);

        return @self::$dict_data[$word_line - 1];
    }

    public static function generateRandomNumberKey($lenght, $prefix = '', $suffix = '')
    {
        $ret = $prefix;

        srand();

        for ($i = 0, $lenght = ($lenght - strlen($prefix) - strlen($suffix)); $i < $lenght; $i++) {
            $ret .= rand(0, 9);
        }

        return $ret .= $suffix;
    }
}

?>