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
 * @changed $Id: lcNameValuePair.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcNameValuePair extends lcObj
{
	protected $name;
	protected $value;

	public function __construct($name, $value = null)
	{
		parent::__construct();

		$this->name = $name;

		if (isset($value))
		{
			$this->value = $value;
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setValue($value = null)
	{
		$this->value = $value;
	}
}

?>