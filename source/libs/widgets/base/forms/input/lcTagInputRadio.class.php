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
 * @changed $Id: lcTagInputRadio.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcTagInputRadio extends lcTagInput
{
	public function __construct($value, $name = null, $id = null, $checked = false, $size = null, $maxsize = null, $disabled = null, $readonly = null, $accesskey = null)
	{
		parent::__construct('radio', $name, $id, $value, $size, $maxsize, $disabled, $readonly, $accesskey);

		$this->setIsChecked($checked);
	}

	public static function getRequiredAttributes()
	{
		return array_implode(array('value'), parent::getRequiredAttributes());
	}

	public function getIsChecked()
	{
		return $this->getAttribute('checked') ? true : false;
	}

	public function setIsChecked($value = false)
	{
		$this->setAttribute('checked', $value ? 'checked' : null);
		return $this;
	}
}

?>