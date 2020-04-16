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

class lcHttpFile extends lcObj
{
    /*
     * The original name of the file on the client machine.
    */
    const UPLOAD_ERR_OK = 0;


    /*
     * The name in html file input field.
    */
    const UPLOAD_ERR_INI_SIZE = 1;

    /*
     * The mime type of the file, if the browser provided this information. An example would be "image/gif".
    * This mime type is however not checked on the PHP side and therefore don't take its value for granted.
    */
    const UPLOAD_ERR_FORM_SIZE = 2;

    /*
     * The temporary filename of the file in which the uploaded file was stored on the server.
    */
    const UPLOAD_ERR_PARTIAL = 3;

    /*
     * the file after we moved into the temporary directory
    */
    const UPLOAD_ERR_NO_FILE = 4;

    /*
     * if has been moved locally
    */
    const UPLOAD_ERR_NO_TMP_DIR = 5;

    /*
     * The error code associated with this file upload. This element was added in PHP 4.2.0
    */
    const UPLOAD_ERR_CANT_WRITE = 6;

    /*
     * The size, in bytes, of the uploaded file
    */
    const UPLOAD_ERR_EXTENSION = 7;

    /*
     * Value: 0; There is no error, the file uploaded with success.
    */
    private $name;

    /*
     * Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
    */
    private $form_filename;

    /*
     * Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form
    */
    private $mimetype;

    /*
     * Value: 3; The uploaded file was only partially uploaded
    */
    private $tmpname;

    /*
     * Value: 4; No file was uploaded
    */
    private $local_name;

    /*
     * Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3
    */
    private $is_moved;

    /*
     * Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0
    */
    private $error;

    /*
     * Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0
    */
    private $size;

    public function __construct($form_filename, $name, $tmpname, $error = self::UPLOAD_ERR_OK, $size = 0, $mimetype = null)
    {
        parent::__construct();

        $this->form_filename = $form_filename;
        $this->name = $name;
        $this->mimetype = $mimetype;
        $this->tmpname = $tmpname;
        $this->error = (int)$error;
        $this->size = (int)$size;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFormName()
    {
        return $this->form_filename;
    }

    public function getMimetype()
    {
        return $this->mimetype;
    }

    public function getTempName()
    {
        return $this->tmpname;
    }

    public function getErrorCode()
    {
        return $this->error;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function isErrorous()
    {
        return $this->error ? true : false;
    }

    public function moveTo($new_filename)
    {
        if ($this->is_moved) {
            return false;
        }

        if (!lcDirs::writable(dirname($new_filename))) {
            throw new lcIOException('Directory ' . dirname($new_filename) . ' is not writeable');
        }

        try {
            if (!move_uploaded_file($this->tmpname, $new_filename)) {
                throw new lcFileUploadException('Unsuccessful file move');
            }
        } catch (Exception $e) {
            throw new lcFileUploadException('Cannot upload file to: ' . $new_filename . ': ' . $e->getMessage());
        }

        $this->is_moved = true;

        return true;
    }

    public function isMoved()
    {
        return $this->is_moved;
    }

    public function & getUploadFilePointer()
    {
        $v = false;

        if ($this->is_moved) {
            return $v;
        }

        $v = fopen($this->tmpname, 'rb');
        return $v;
    }

    public function __toString()
    {
        return "lcHttpFile: \n" .
            "Name: " . $this->name . "\n" .
            "Mimetype: " . $this->mimetype . "\n" .
            "Tmp Name: " . $this->tmpname . "\n" .
            "Local Name: " . $this->local_name . "\n" .
            "Has Moved: " . $this->is_moved . "\n" .
            "Error: " . $this->error . "\n" .
            "Size: " . $this->size . "\n\n";
    }
}
