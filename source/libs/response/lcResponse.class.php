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
 * @changed $Id: lcResponse.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcResponse extends lcResidentObj implements iProvidesCapabilities
{
    protected $exit_code = 0;
    protected $response_sent;

    public function getCapabilities()
    {
        return array(
            'response'
        );
    }

    abstract public function clear();

    abstract public function getContent();

    abstract public function getOutputContent();

    abstract public function setContent($content);

    abstract public function setShouldExitUponSend($do_exit = true);

    abstract public function sendResponse();

    public function getIsResponseSent()
    {
        return $this->response_sent;
    }

    public function getExitCode()
    {
        return $this->exit_code;
    }

    public function setExitCode($exit_code = 0)
    {
        assert($exit_code >= 0 && $exit_code <= 255);
        $this->exit_code = (int)$exit_code;
    }
}
