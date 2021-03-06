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

abstract class lcTranslationMessageSource extends lcObj
{
    public static function & getInstance($type)
    {
        $c = 'lc' . lcInflector::camelize($type, false) . 'TranslationSource';

        if (!class_exists($c)) {
            throw new lcSystemException('Invalid Translation Source: ' . $type);
        }

        $t = new $c;

        unset($c);

        return $t;
    }

    abstract public function getLocale();

    abstract public function setLocale($locale);

    abstract public function translationExists($original_string);

    abstract public function getTranslation($original_string);

    abstract public function getTranslations();

    abstract public function setTranslation($original_string, $translated_string);

    abstract public function setTranslations(array $translations);
}
