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

abstract class lcI18n extends lcResidentObj implements iProvidesCapabilities, iKeyValueProvider, iI18nProvider, iDebuggable
{
    public function initialize()
    {
        parent::initialize();
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getCapabilities()
    {
        return [
            'i18n',
        ];
    }

    public function getDebugInfo(): array
    {
        return [
            'locale' => $this->getLocale(),
            'context_type' => $this->getTranslationContextType(),
            'context_name' => $this->getTranslationContextName(),
        ];
    }

    abstract public function getLocale();

    abstract public function getLanguageCode();

    abstract public function getCountryCode();

    public function getShortDebugInfo(): array
    {
        return [
            'locale' => $this->getLocale(),
        ];
    }

    abstract public function setLocale($locale);

    public function splitLocale($locale, $set_default_country = true)
    {
        $locale = (string)$locale;

        if (!$locale) {
            return false;
        }

        $delimiters = ['_', '-'];

        $found_delimiter = null;

        foreach ($delimiters as $delimiter) {
            if (strstr($locale, $delimiter)) {
                $found_delimiter = $delimiter;
                break;
            }

            unset($delimiter);
        }

        if (!$found_delimiter) {
            $locale = strtolower($locale);

            $res = ['locale' => $locale, 'lang_code' => $locale, 'country_code' => null];
        } else {
            $locale = array_filter(explode($found_delimiter, $locale));

            if (!isset($locale[0])) {
                return false;
            }

            $country_code = isset($locale[1]) ? $locale[1] : null;

            $lang_code = strtolower($locale[0]);
            $country_code = strtoupper($country_code);
            $locale = $lang_code . '_' . $country_code;

            $res = ['locale' => $locale, 'lang_code' => $lang_code, 'country_code' => $country_code];
        }

        $res['locale_is_default'] = true;

        //$country_code_is_default = true;
        $default_country = null;

        // detect default country / locale
        $defaults = i18nHelper::getAll();
        $default_country = strtoupper(@$defaults[1][$res['lang_code']]);

        // set default country if none detected
        if ($set_default_country) {
            if ($res['lang_code'] && !$res['country_code']) {
                if ($default_country) {
                    $res['country_code'] = $default_country;

                    $res['locale'] = $res['lang_code'] . '_' . $res['country_code'];
                }
            }
        }

        if ($res['country_code'] != $default_country) {
            $res['default_country_code'] = $default_country;
        }

        $ak = array_flip($defaults[1]);

        if ($res['country_code']) {
            $default_locale = isset($ak[$res['country_code']]) ? $ak[$res['country_code']] : null;

            if (!$default_locale || $default_locale != $res['lang_code']) {
                $res['locale_is_default'] = false;
            }

            unset($default_locale);
        }

        unset($ak);

        return $res;
    }

    #pragma mark - iKeyValueProvider

    public function getAllKeys()
    {
        return [
            'locale',
        ];
    }

    public function getValueForKey($key)
    {
        if (!$key) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        if ($key == 'locale') {
            return $this->getLocale();
        }

        return null;
    }
}
