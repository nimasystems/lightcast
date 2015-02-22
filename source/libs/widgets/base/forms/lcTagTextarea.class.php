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
 * @changed $Id: lcTagTextarea.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcTagTextarea extends lcHtmlTag
{
	public function __construct($rows = null, $cols = null, $content = null, $name = null, $disabled = false,
			$readonly = false, $accesskey = null, $tabindex = null)
	{
		parent::__construct('textarea', true);

		$this->setContent($content);
		$this->setRows($rows);
		$this->setCols($cols);
		$this->setName($name);
		$this->setIsDisabled($disabled);
		$this->setIsReadonly($readonly);
		$this->setTabIndex($tabindex);
		$this->setAccessKey($accesskey);
	}

	public static function getRequiredAttributes()
	{
		return array('rows', 'cols');
	}

	public static function getOptionalAttributes()
	{
		return array('name', 'disabled', 'readonly', 'accesskey', 'tabindex');
	}

	public function setContent($content)
	{
		parent::setContent($content);
		return $this;
	}

	public function setRows($value)
	{
		$this->setAttribute('rows', $value);
		return $this;
	}

	public function getRows()
	{
		return $this->getAttribute('rows');
	}

	public function setCols($value)
	{
		$this->setAttribute('cols', $value);
		return $this;
	}

	public function getCols()
	{
		return $this->getAttribute('cols');
	}

	public function setName($value = null)
	{
		$this->setAttribute('name', $value);
		return $this;
	}

	public function getName()
	{
		return $this->getAttribute('name');
	}

	public function setTabIndex($value = null)
	{
		$this->setAttribute('tabindex', $value);
		return $this;
	}

	public function getTabIndex()
	{
		return $this->getAttribute('tabindex');
	}

	public function setIsReadonly($value = false)
	{
		$this->setAttribute('readonly', $value ? 'readonly' : null);
		return $this;
	}

	public function getIsReadonly()
	{
		return $this->getAttribute('readonly') ? true : false;
	}

	public function setIsDisabled($value = false)
	{
		$this->setAttribute('disabled', $value ? 'disabled' : null);
		return $this;
	}

	public function getIsDisabled()
	{
		return $this->getAttribute('disabled') ? true : false;
	}

	public function setAccessKey($accesskey = null)
	{
		$this->setAttribute('accesskey', $accesskey);
		return $this;
	}

	public function getAccessKey()
	{
		return $this->getAttribute('accesskey');
	}
}

?>