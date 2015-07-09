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
 * @changed $Id: lcHttpFile.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcHttpFile extends lcObj
{
    /*
     * The original name of the file on the client machine.
    */
    private $name;


    /*
     * The name in html file input field.
    */
    private $form_filename;

    /*
     * The mime type of the file, if the browser provided this information. An example would be "image/gif".
    * This mime type is however not checked on the PHP side and therefore don't take its value for granted.
    */
    private $mimetype;

    /*
     * The temporary filename of the file in which the uploaded file was stored on the server.
    */
    private $tmpname;

    /*
     * the file after we moved into the temporary directory
    */
    private $local_name;

    /*
     * if has been moved locally
    */
    private $is_moved;

    /*
     * The error code associated with this file upload. This element was added in PHP 4.2.0
    */
    private $error;

    /*
     * The size, in bytes, of the uploaded file
    */
    private $size = 0;

    /*
     * Value: 0; There is no error, the file uploaded with success.
    */
    const UPLOAD_ERR_OK = 0;

    /*
     * Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini.
    */
    const UPLOAD_ERR_INI_SIZE = 1;

    /*
     * Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form
    */
    const UPLOAD_ERR_FORM_SIZE = 2;

    /*
     * Value: 3; The uploaded file was only partially uploaded
    */
    const UPLOAD_ERR_PARTIAL = 3;

    /*
     * Value: 4; No file was uploaded
    */
    const UPLOAD_ERR_NO_FILE = 4;

    /*
     * Value: 6; Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3
    */
    const UPLOAD_ERR_NO_TMP_DIR = 5;

    /*
     * Value: 7; Failed to write file to disk. Introduced in PHP 5.1.0
    */
    const UPLOAD_ERR_CANT_WRITE = 6;

    /*
     * Value: 8; File upload stopped by extension. Introduced in PHP 5.2.0
    */
    const UPLOAD_ERR_EXTENSION = 7;

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

    public function getTempDir()
    {
        return $this->temp_dir;
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
        if ($this->is_moved) {
            return false;
        }

        return fopen($this->tmpname, 'rb');
    }

    public function __toString()
    {
        $str = "lcHttpFile: \n" .
            "Name: " . $this->name . "\n" .
            "Mimetype: " . $this->mimetype . "\n" .
            "Tmp Name: " . $this->tmpname . "\n" .
            "Local Name: " . $this->local_name . "\n" .
            "Has Moved: " . $this->is_moved . "\n" .
            "Error: " . $this->error . "\n" .
            "Size: " . $this->size . "\n\n";

        return $str;
    }
}
