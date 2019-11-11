<?php /** @noinspection PhpDuplicateArrayKeysInspection */

class lcStrings
{
    const LATIN_CHARS = 'qwertyuiopasdfghjklzxcvbnm';
    const NUMBERS = '1234567890';
    const WORD_COUNT_MASK = "/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}\p{Nd}]*/u";

    protected static $dict_index = [];
    protected static $dict_data = [];

    public static function isValidBase64($string)
    {
        $decoded = base64_decode($string, true);

        // Check if there is no invalid character in string
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) return false;

        // Decode the string in strict mode and send the response
        if (!base64_decode($string, true)) return false;

        // Encode and compare it to original one
        if (base64_encode($decoded) != $string) return false;

        return true;
    }

    /**
     * Returns a v4 UUID.
     *
     * @return string
     */
    public static function uuid()
    {
        $arr = array_values(unpack('N1a/n4b/N1c', openssl_random_pseudo_bytes(16)));
        $arr[2] = ($arr[2] & 0x0fff) | 0x4000;
        $arr[3] = ($arr[3] & 0x3fff) | 0x8000;
        return vsprintf('%08x-%04x-%04x-%04x-%04x%08x', $arr);
    }

    public static function startsWith($haystack, $needle)
    {
        return ($haystack && $needle && $haystack{0} == $needle{0}) ? 0 === lcUnicode::strpos($haystack, $needle) : false;
    }

    public static function endsWith($haystack, $needle, $case = true)
    {
        if ($case) {
            return (strcmp(lcUnicode::substr($haystack, lcUnicode::strlen($haystack) - lcUnicode::strlen($needle)), $needle) === 0);
        }

        return (strcasecmp(lcUnicode::substr($haystack, lcUnicode::strlen($haystack) - lcUnicode::strlen($needle)), $needle) === 0);
    }

    public static function removeUtf8Bom($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }

    public static function contains($haystack, $needle)
    {
        return strstr($haystack, $needle);
    }

    public static function isSerialized($string)
    {
        return (@unserialize($string) !== false || $string == 'b:0;');
    }

    public static function splitLocaleCode($locale)
    {
        $locale_code = null;
        $country_code = null;

        $separators = ['_', '-'];
        $sel_sep = null;

        foreach ($separators as $sep) {

            if (strstr($locale, $sep)) {
                $sel_sep = $sep;
                break;
            }

            unset($sep);
        }

        if ($sel_sep) {
            $tmp = explode($sel_sep, $locale);
            $locale_code = $tmp[0];

            if (isset($tmp[1])) {
                $country_code = $tmp[1];
            }
        } else {
            $locale_code = $locale;
        }

        return [
            'country' => $country_code,
            'locale' => $locale_code,
        ];
    }

    public static function findWords($string, $min_length = 3, $with_numbers = true)
    {
        $match_arr = [];
        $num = $with_numbers ? '0-9' : '';
        $n_words = preg_match_all('/([a-zA-Z' . $num . ']|\xC3[\x80-\x96\x98-\xB6\xB8-\xBF]|\xC5[\x92\x93\xA0\xA1\xB8\xBD\xBE]){' . (int)$min_length . ',}/', $string, $match_arr);
        return $n_words ? $match_arr[0] : [];
    }

    public static function splitEmail($str)
    {
        $regex = '/(("([^"]*)"|[^",]*)\\s*<(.*?)>|[^",\\s]+)(?=(,|$))/';
        preg_match_all($regex, $str, $matches, PREG_SET_ORDER);

        if (!$matches) {
            return null;
        }

        $match = $matches[0];

        $out = [
            'name' => $match[3] ?: trim($match[2]),
            'email' => trim($match[4]) ?: $match[1],
        ];

        foreach ($matches as $match) $out = [
            'name' => $match[3] ?: trim($match[2]),
            'email' => trim($match[4]) ?: $match[1],
        ];

        return $out;
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

        $keywords_ = null;

        if (isset($keywords)) {
            $keys = [];

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

    public static function protectEmail($mail)
    {
        $len = strlen($mail);
        $i = 0;

        $par = [];

        while ($i < $len) {
            $c = mt_rand(1, 4);
            $par[] = substr($mail, $i, $c);
            $i += $c;
        }

        return 'javascript:location=\'ma\'+\'il\'+\'to:' . implode('\'+ \'', $par) . '\'';
    }

    public static function isAbsolutePath($file)
    {
        return strspn($file, '/\\', 0, 1)
            || (strlen($file) > 3 && ctype_alpha($file[0])
                && substr($file, 1, 1) === ':'
                && strspn($file, '/\\', 2, 1)
            )
            || null !== parse_url($file, PHP_URL_SCHEME);
    }

    public static function getTopKeywords($string, $min_word_len = 3, $max_words = 30, $imploded = false)
    {
        $skipwords = [
            'and', 'the', 'is', 'of', 'from', 'to', 'an', 'a', 'with',
            'are', 'me', 'she', 'because', 'otherwise', 'without', 'select',
            'selected', 'were', 'by', 'in', 'his', 'her', 'at', 'to',
        ];

        if (!isset($min_word_len)) {
            $min_word_len = 3;
        }

        $string = lcUnicode::strtolower($string);
        $tokens = self::tokenize($string);
        $tokens = self::unhtmlentities(implode('|', $tokens));
        $tokens = explode('|', $tokens);

        $latinchars = self::getLatinChars();

        $narr = [];

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

    public static function tokenize($string)
    {
        $tokens = [];
        preg_match_all('/"[^"]+"|[^"\s,]+/', $string, $tokens);

        if (!$tokens) {
            return null;
        }

        return $tokens[0];
    }

    # prepare a string for javascript

    public static function unhtmlentities($string)
    {
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        $ret = strtr($string, $trans_tbl);

        return preg_replace('/&#(\d+);/me', "chr('\\1')", $ret);
    }

    // stripslashes - recursive on arrays

    public static function getLatinChars()
    {
        $narr = [];
        $str = self::LATIN_CHARS;

        for ($i = 0; $i < strlen($str); $i++) {
            $narr[] = $str{$i};
        }

        return $narr;
    }

    // addslashes - recursive on arrays

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

        $params = [];

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

    public static function toAlphaNum($string, array $allowed = [])
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
        $parts = [];

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

    /**
     * Function: sanitize
     * Returns a sanitized string, typically for URLs.
     *
     * Parameters:
     *     $string - The string to sanitize.
     *     $force_lowercase - Force the string to lowercase?
     *     $anal - If set to *true*, will remove all non-alphanumeric characters.
     * @param $string
     * @param bool $force_lowercase
     * @param bool $anal
     * @return mixed|string
     */
    public static function sanitize($string, $force_lowercase = true, $anal = false)
    {
        $strip = ["~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                  "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                  "â€”", "â€“", ",", "<", ".", ">", "/", "?"];
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;
        return ($force_lowercase) ?
            (function_exists('mb_strtolower')) ?
                mb_strtolower($clean, 'UTF-8') :
                strtolower($clean) :
            $clean;
    }

    public static function htmlEncodeString($string)
    {
        return htmlspecialchars($string);
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

    /*
     * Deprecated
    */

    public static function getLongTailKeywords($str, $len = 3, $min = 2)
    {
        if (!$str) {
            return [];
        }

        $keywords = [];
        $common = ['i', 'a', 'about', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when', 'where', 'who', 'will', 'with', 'und', 'the', 'www'];
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

        $return = [];

        foreach ($keywords as &$keyword) {
            $keyword = array_filter($keyword, function ($v) use ($min) {
                return !!($v > $min);
            });

            arsort($keyword);
            $return = array_merge($return, $keyword);
        }

        return $return;
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

    public static function str_word_count_utf8($string, $format = 0)
    {
        if (!$string) {
            return null;
        }

        switch ($format) {
            case 1:
                preg_match_all(self::WORD_COUNT_MASK, $string, $matches);
                $ret = $matches[0];
                break;
            case 2:
                preg_match_all(self::WORD_COUNT_MASK, $string, $matches, PREG_OFFSET_CAPTURE);
                $result = [];

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

    public static function sluggable($string, $separator = '-')
    {
        $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $special_cases = ['&' => 'and'];
        $string = mb_strtolower(trim($string), 'UTF-8');
        $string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
        $string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
        $string = preg_replace("/[^\w\d]/u", "$separator", $string);
        $string = preg_replace("/[$separator]+/u", "$separator", $string);
        return $string;
    }

    public static function url_slug($str, $options = [])
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        $defaults = [
            'delimiter' => '-',
            'limit' => null,
            'lowercase' => true,
            'replacements' => [],
            'transliterate' => false,
        ];

        // Merge options
        $options = array_merge($defaults, $options);

        $char_map = [
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',
            // Latin symbols
            '©' => '(c)',
            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',
            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',
            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',
            // Latvian
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
            'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z',
        ];

        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
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
     * @param $string
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
            } else if ($cut_to - $len < -10) {
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

    /**
     *    Calculates Age by birh date
     *
     * @param $date
     * @return bool|string
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
     * @param int $minLen
     * @param int $maxLen
     * @return mixed <string> $word
     * @internal param $ <int> $minLen
     * @internal param $ <int> $maxLen
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

        $ret .= $suffix;

        return $ret;
    }
}