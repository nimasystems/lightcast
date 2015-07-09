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
 * @changed $Id: lcMailAttachment.class.php 1594 2015-06-20 18:47:08Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1594 $
 */
class lcMailAttachment extends lcObj
{
    private $filename;
    private $filepath;
    private $mimetype;

    public function __construct($filepath, $mimetype, $filename = null)
    {
        $this->filepath = $filepath;
        $this->mimetype = $mimetype;
        $this->filename = isset($filename) ? $filename : basename($filepath);
    }

    public static function create($filepath, $mimetype, $filename = null)
    {
        $obj = new lcMailAttachment($filepath, $mimetype, $filename);
        return $obj;
    }

    public function getFilePath()
    {
        return $this->filepath;
    }

    public function getMimetype()
    {
        return $this->mimetype;
    }

    public function getFilename()
    {
        return $this->filename;
    }
}
