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
 * @changed $Id: lcTagOption.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcTagOption extends lcHtmlTag
{
	public function __construct($content = null, $value = null, $selected = false, $class = null)
	{
		parent::__construct('option', true);

		$this->setContent($content);
		$this->setSelected($selected);
		$this->setValue($value);
		$this->setClass($class);
	}

	public static function getRequiredAttributes()
	{
		return array();
	}

	public static function getOptionalAttributes()
	{
		return array('selected', 'value');
	}

	public function setContent($content)
	{
		parent::setContent($content);
		return $this;
	}

	public function setSelected($selected = false)
	{
		$this->setAttribute('selected', $selected ? 'selected' : null);
		return $this;
	}

	public function getSelected()
	{
		return $this->getAttribute('selected') ? true : false;
	}

	public function setValue($value = null)
	{
		$this->setAttribute('value', $value);
		return $this;
	}

	public function getValue()
	{
		return $this->getAttribute('value');
	}
}

?>