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
 * @changed $Id: lcTagDel.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcTagDel extends lcHtmlTag
{
	public function __construct($content = null, $cite = null, $datetime = null)
	{
		parent::__construct('del', true);

		$this->setContent($content);
		$this->setCite($cite);
		$this->setDateTime($datetime);
	}

	public static function getRequiredAttributes()
	{
		return array();
	}

	public static function getOptionalAttributes()
	{
		return array('cite');
	}

	public function setCite($cite = null)
	{
		$this->setAttribute('cite', $cite);
		return $this;
	}

	public function getCite()
	{
		return $this->getAttribute('cite');
	}

	public function setDateTime($value = null)
	{
		$this->setAttribute('datetime', $value);
		return $this;
	}

	public function getDateTime()
	{
		return $this->getAttribute('datetime');
	}
}

?>