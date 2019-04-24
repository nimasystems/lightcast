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

abstract class lcGettext extends lcI18n implements iDebuggable
{
    const DEFAULT_LOCALE_UNIX = 'en_US';
    const DEFAULT_LOCALE_WIN = 'us';
    const DEFAULT_CATEGORY = [LC_COLLATE, LC_CTYPE, LC_MONETARY, LC_TIME, LC_MESSAGES];
    const DEFAULT_CHARSET = 'UTF-8';
    protected $locale;
    protected $charset = self::DEFAULT_CHARSET;
    protected $category = self::DEFAULT_CATEGORY;
    protected $domain_path;
    protected $domain;

    public static function getDomainFullPath($locale_path, $locale)
    {
        return $locale_path . DS . $locale . DS . 'LC_MESSAGES' . DS . $locale . '.mo';
    }

    public function initialize()
    {
        parent::initialize();

        if (!function_exists('gettext')) {
            throw new lcI18NException('PHP gettext cannot be loaded');
        }

        if (!defined('LC_MESSAGES')) {
            define('LC_MESSAGES', 6);
        }

        $default_locale = $this->getDefaultLocale();
        $locale = isset($this->configuration['i18n.locale']) ? (string)$this->configuration['i18n.locale'] : $default_locale;

        // set default locale
        try {
            if ($locale) {
                $this->setLocale($locale);
            }
        } catch (Exception $e) {
            $this->err('Could not set gettext locale (' . $locale . '): ' . $e->getMessage());

            if (DO_DEBUG) {
                throw $e;
            }
        }
    }

    public function getDefaultLocale()
    {
        return setlocale(LC_ALL, null);
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = [
            'charset' => $this->charset,
            'category' => $this->category,
            'domain_path' => $this->domain_path,
            'domain' => $this->domain,
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        false;
    }

    public function getDomainPath()
    {
        return $this->domain_path;
    }

    public function setDomainPath($path)
    {
        $this->domain_path = $path;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        $locale = (string)$locale;

        if (!$locale) {
            throw new lcInvalidArgumentException('Invalid locale');
        }

        $this->category = self::DEFAULT_CATEGORY;

        // check if we have already set it
        if ($this->locale == $locale) {
            return;
        }

        // find out charset if such
        $ex = explode('.', $locale);

        if (count($ex) == 2) {
            $this->locale = $ex[0];
            $this->charset = $ex[1];
        } else {
            $this->locale = $locale;
            $this->charset = self::DEFAULT_CHARSET;
        }

        unset($ex);

        //set envs
        $this->setEnv($locale);

        //set locale
        $this->internalSetLocale($locale, $this->category);
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setDomain($domain, $domain_path = null)
    {
        $domain = (string)$domain;

        if (!$domain) {
            throw new lcInvalidArgumentException('Invalid domain');
        }

        $current_domain_path = $this->domain_path;
        $current_domain = $this->domain;

        // check if we are already working with the same domain
        if ($current_domain == $domain && (!isset($domain_path) || isset($domain_path) && $domain_path == $current_domain_path)) {
            return true;
        }

        $domain_path = isset($domain_path) ? $domain_path : $current_domain_path;

        // check if path is set
        if (!$domain_path) {
            throw new lcInvalidArgumentException('Domain path not set');
        }

        //$domain_full_path = $this->getDomainFullPath($domain_path, $domain);

        try {
            /*if (!chdir($domain_path))
             {
            throw new lcIOException('Cannot switch to domain directory');
            }*/

            if (!bindtextdomain($domain, $domain_path . DS)) {
                throw new lcI18NException('Cannot bind domain');
            }

            if (textdomain($domain) != $domain) {
                throw new lcI18NException('Cannot switch to gettext domain');
            }

            if (function_exists('bind_textdomain_codeset')) {
                if (!bind_textdomain_codeset($domain, 'UTF-8')) {
                    throw new lcI18NException('Cannot bind domain codeset');
                }
            }

            $this->domain = $domain;
            $this->domain_path = $domain_path;

            return true;
        } catch (Exception $e) {
            throw new lcI18NException('Gettext domain set error (' . $domain . ' / ' . $domain_path . '): ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function translate($string)
    {
        if (!$string) {
            return false;
        }

        // no locale / domain set yet
        if (!$this->locale || !$this->domain_path || !$this->domain) {
            return $string;
        }

        $string = gettext($string);

        return $string;
    }

    private function setEnv($locale)
    {
        # Some systems only require LANG, some (like Mandrake) seem to require LANGUAGE als
        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");

        if (getenv('LANG') != $locale || getenv('LANGUAGE') != $locale) {
            throw new lcI18NException('Could not set locale environment for locale: ' . $locale);
        }

        return true;
    }

    private function internalSetLocale($locale, $category)
    {
        $categories = is_array($category) ? $category : [$category];

        /*
         * Try appending some character set names; some systems (like FreeBSD) need this.  Some
        * require a format with hyphen (eg. Gentoo) and others without (eg. FreeBSD).
        */
        $charsets = ['UTF-8', 'UTF8', 'utf8', 'utf-8',
                     'ISO8859-1', 'ISO8859-2', 'ISO8859-5', 'ISO8859-7', 'ISO8859-9',
                     'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-5', 'ISO-8859-7', 'ISO-8859-9',
                     'EUC', 'Big5'];

        foreach ($charsets as $charset) {
            $ret = false;

            foreach ($categories as $cat_) {
                if (($ret = setlocale($cat_, $locale . '.' . $charset)) !== false) {
                    $this->charset = $charset;
                    $ret = true;
                } else {
                    break;
                }
            }

            if ($ret) {
                return $ret;
            }

            unset($charset);
        }

        unset($charsets);

        /*
         * Try setting the alternative three letter language code
        */
        if ($codes = i18nHelper::LangCountryLocalesToThreeLetterCode($locale)) {
            foreach ($codes as $code) {
                $ret = false;

                foreach ($categories as $cat_) {
                    if (($ret = setlocale($cat_, $code)) !== false) {
                        $this->charset = 'UTF-8';
                        $ret = true;
                    } else {
                        break;
                    }
                }

                if ($ret) {
                    return $ret;
                }

                unset($code);
            }
        }

        unset($code, $codes);

        $ret = false;

        foreach ($categories as $cat_) {
            if (($ret = setlocale($cat_, $locale)) !== false) {
                $ret = true;
            }
        }

        if ($ret) {
            return $ret;
        }

        throw new lcI18NException('Cannot set system locale: ' . $locale);
    }
}