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
 * @changed $Id: lcCookiesCollection.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcCookiesCollection extends lcBaseCollection
{
	public function __construct(array $values=null)
	{
		parent::__construct();

		if (isset($values))
		{
			foreach ($values as $key=>$val)
			{
				$this->append(new lcCookie($key,$val));
			}
		}
	}

	public function append(lcCookie $cookie)
	{
		return parent::appendColl($cookie);
	}

	public function offsetSet($index, lcCookie $value)
	{
		return parent::offsetSetColl($index, $value);
	}

	public function offsetUnset($index)
	{
		return parent::offsetUnset($index);
	}

	public function get($name)
	{
		$cookies = $this->getAll();

		foreach ($cookies as $cookie)
		{
			if ($cookie->getName() == $name)
			{
				return $cookie;
			}
		}

		unset($cookies);

		return null;
	}

	public function set(lcCookie $value, $offset=null)
	{
		return parent::setColl($value, $offset);
	}

	public function delete($offset=null)
	{
		return parent::delete($offset);
	}

	public function clear()
	{
		return parent::clear();
	}

	public function getByName($name)
	{
		$this->first();

		foreach ($this->list as $el)
		{
			if ($el->getName() == $name) 
			{
				return $el;
			}
		}

		return null;
	}
	
	public function __toString()
	{
		$all = $this->getAll();
		
		if ($all)
		{
			$all = $all->getArrayCopy();
			
			$ret = array();
			
			foreach($all as $cookie)
			{
				$val = $cookie->getValue();
				
				if (!is_string($val))
				{
					continue;
				}
				
				$ret[] = $cookie->getName() . ': ' . $val;
				
				unset($cookie);
			}
			
			$ret = implode(', ', $ret);
			
			return $ret;
		}
		
		return '';
	}
}

?>