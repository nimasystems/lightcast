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
 * @changed $Id: lcTagRichText.class.php 1464 2013-10-29 02:38:39Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1464 $
*/

class lcTagRichText extends lcTagInput
{
	protected $fck_component;

	private $width = 700;

	public function __construct($name, $width)
	{
		$this->width = $width;

		parent::__construct('',$name);
	}

	public function setFckComponent(componentFckEdit $editor)
	{
		$this->fck_component = $editor;
	}

	public function asHtml()
	{
		$fck = $this->fck_component;

		assert(isset($fck));

		$fck->setWidth($this->width);
		$fck->setInstanceName($this->getName());
		$fck->setValue($this->getValue());

		$e = $fck->execute();

		return (string)$e;
	}
}

?>