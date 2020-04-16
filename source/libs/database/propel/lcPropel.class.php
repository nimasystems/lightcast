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

class lcPropel extends Propel
{
    const TRANSLATION_DOMAIN = 'database_models';

    const BASE_CLASS = 'lcBasePropelObject';
    const BASE_QUERY_CLASS = 'lcBaseQueryObject';
    const BASE_PEER_CLASS = 'lcBasePeer';

    const CONTEXT_TYPE_ATTR = 'lcContextType';
    const CONTEXT_NAME_ATTR = 'lcContextName';

    /** @var iCacheStore */
    protected static $cache;

    protected static $cache_key;

    /** @var lcI18n */
    protected static $i18n;

    /** @var lcEventDispatcher */
    protected static $event_dispatcher;

    /** @var lcConfiguration */
    protected static $app_configuration;

    public static function shutdown()
    {
        self::$cache =
        self::$cache_key =
        self::$i18n =
        self::$event_dispatcher =
        self::$app_configuration =
            null;
    }

    public static function setI18n(lcI18n $i18n = null)
    {
        self::$i18n = $i18n;
    }

    public static function setCache(iCacheStore $cache = null, $cache_key = null)
    {
        self::$cache = $cache;
        self::$cache_key = $cache_key;
    }

    public static function setEventDispatcher(lcEventDispatcher $event_dispatcher)
    {
        self::$event_dispatcher = $event_dispatcher;
    }

    public static function setAppConfiguration(lcConfiguration $configuration)
    {
        self::$app_configuration = $configuration;
    }

    /*
     * Validators
     */
    public static function translateValidatorMessage($string, lcTableMap $map_object, $locale = null)
    {
        return self::translateTableMapString($string, $map_object);
    }

    public static function translateTableMapString($string, lcTableMap $map_object)
    {
        if (!$string || !$map_object) {
            return $string;
        }

        if (!self::$i18n) {
            return $string;
        }

        // extract context info
        $context_type = $map_object->getLcContextType();
        $context_type = $context_type ? $context_type : 'project';
        $context_type = lcController::getContextTypeAsConst($context_type);

        $context_name = $map_object->getLcContextName();

        //ee(self::$i18n->getLocale() . ' > ' . $context_name . ': ' . $string . ' ::::: ' . $translated_string);
        //e($context_type . ' :: ' . $context_name . ' ---- ' . $string . ' - ' . $translated_string);

        return self::$i18n->translateInContext($context_type, $context_name, $string, self::TRANSLATION_DOMAIN);
    }
}