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
 * @changed $Id: lcHtmlTagAttribCollection.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcHtmlTagAttribCollection extends lcBaseCollection implements iAsHTML
{
	public function __construct(array $values=null)
	{
		parent::__construct();

		if (isset($values))
		{
			foreach ($values as $key=>$val)
			{
				$this->append($key, $val);
				unset($key, $val);
			}
		}
	}

	public function append($key, $value=null)
	{
		if (!$this->setPositionByKey($key))
		{
			parent::append(new lcHtmlAttribute($key, $value));
		}
		else
		{
			$this->current()->setValue($value);
		}
		return $this;
	}

	public function set($key, $value=null)
	{
		$this->append($key, $value);
		return $this;
	}

	public function get($key)
	{
		if (!$this->setPositionByKey($key)) 
		{
			return null;
		}

		return $this->current()->getValue();
	}

	public function delete($key=null)
	{
		if ($this->setPositionByKey($key)) 
		{
			parent::delete($this->key());
		}
		return $this;
	}

	private function setPositionByKey($key)
	{
		$this->first();

		$all = $this->getAll();

		foreach ($all as $el)
		{
			if ($el->getName() == $key) 
			{
				return true;
			}
			
			unset($el);
		}

		unset($all);
		return $this;
	}

	public function clear()
	{
		parent::clear();
		return $this;
	}

	public function __toString()
	{
		return $this->asHtml();
	}

	public function asHtml($tagsafe=true)
	{
		fnothing($tagsafe);

		$out = '';

		if ($this->count())
		{
			$a = array();

			$all = $this->getAll();

			foreach ($all as $val)
			{
				$a[] = $val->asHtml();
				unset($val);
			}

			$out = implode(' ',$a);
			unset($a, $all);
		}

		return $out;
	}
}

/* End of script */
?>