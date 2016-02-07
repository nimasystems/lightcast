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

abstract class lcSerializedFileTranslationSource extends lcTranslationMessageSource implements Serializable
{
    const DEFAULT_EXT = '.dat';

    private $file_path;
    private $is_open;
    private $ext;
    private $locale;

    public function getFileExtension()
    {
        return $this->ext;
    }

    public function getPath()
    {
        return $this->file_path;
    }

    public function readFile($path, $locale, $ext = self::DEFAULT_EXT)
    {
        if ($this->is_open) {
            return;
        }

        $this->file_path = $path;
        $this->locale = $locale;
        $this->ext = isset($ext) ? $ext : self::DEFAULT_EXT;
        $this->setLocale($locale);
        $this->is_open = false;

        if (!$this->file_path || !$this->locale || !$this->ext || !$this->getLocale()) {
            throw new lcSystemException('Cannot open translation file. Not all params set');
        }

        try {
            $fname = $path . DS . $locale . $ext;

            $mod = lcFiles::exists($fname) ?
                'r+' :
                'a+';

            $file_resource = fopen($fname, $mod);

            $this->unserialize(stream_get_contents($file_resource));
            @fclose($file_resource);

            unset($fname, $file_resource, $mod);
        } catch (Exception $e) {
            throw new lcI18NException('Error while reading translation file: ' . $e->getMessage(), null, $e);
        }
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale)
    {
        throw new lcInvalidArgumentException('Cannot set locale ' . $locale . ' - try reading a file');
    }

    public function saveFile()
    {
        try {
            $f = fopen($this->getFullFilename(), 'w');
            fwrite($f, $this->serialize());
            @fclose($f);
            unset($f);
        } catch (Exception $e) {
            throw new lcI18NException('Error while writing to translation file: ' . $e->getMessage(), null, $e);
        }
    }

    public function getFullFilename()
    {
        return $this->file_path . DS . $this->locale . $this->ext;
    }
}
