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

class lcInternalTranslationSource extends lcSerializedFileTranslationSource
{
    private $translations;

    public function serialize()
    {
        return
            serialize(
                [
                    $this->translations
                ]
            );
    }

    public function unserialize($serialized)
    {
        list(
            $this->translations
            ) = unserialize($serialized);
    }

    public function getTranslation($original_string)
    {
        if (!$this->translationExists($original_string)) {
            return null;
        }

        return $this->translations[$original_string];
    }

    public function translationExists($original_string)
    {
        return isset($this->translations[$original_string]);
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function setTranslations(array $translations)
    {
        $this->translations = array_merge(
            (array)$this->translations,
            $translations
        );
    }

    public function setTranslation($original_string, $translated_string)
    {
        $this->translations[$original_string] = $translated_string;
    }
}
