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
 * @changed $Id: lcBaseCollection.class.php 1455 2013-10-25 20:29:31Z mkovachev $
* @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

abstract class lcBaseCollection extends lcObj
{
	protected $list;

	const SPL_OBJECT_NAME = 'ArrayIterator';

	/*
	 * Construct a new array iterator from anything that has a hash table.
	* That is any Array or Object.
	*/
	public function __construct(array $data = null)
	{
		parent::__construct();

		if (!class_exists(self::SPL_OBJECT_NAME, false))
		{
			throw new lcSystemException('Cannot load SPL Object: '.self::SPL_OBJECT_NAME);
		}

		$splclass = self::SPL_OBJECT_NAME;
		$this->list = new $splclass($data ? $data : array());
	}

	public function __destruct()
	{
		unset($this->list);

		parent::__destruct();
	}

	protected function appendColl($value)
	{
		$this->list->append($value);
	}

	public function offsetExists($index)
	{
		return $this->list->offsetExists($index);
	}

	public function offsetGet($index)
	{
		return $this->list->offsetGet($index);
	}

	protected function offsetSetColl($index, $value)
	{
		return $this->list->offsetSet($index, $value);
	}

	protected function offsetUnset($index)
	{
		return $this->list->offsetUnset($index);
	}

	protected function setColl($value, $offset=null)
	{
		return $this->list->offsetSet($offset ? $offset : $this->list->key(),$value);
	}

	protected function delete($offset=null)
	{
		return $this->list->offsetUnset($offset ? $offset : $this->key());
	}

	protected function clear()
	{
		$spl = self::SPL_OBJECT_NAME;
		$this->list = new $spl;
	}

	public function getArrayCopy()
	{
		return $this->list->getArrayCopy();
	}

	/*
	 * Get vars
	*/
	public function count()
	{
		return $this->list->count();
	}

	public function key()
	{
		return $this->list->key();
	}

	public function pos()
	{
		return $this->key();
	}

	public function isValid()
	{
		return $this->list->valid();
	}

	/*
	 * Manage Position of List
	*/
	public function current()
	{
		return $this->list->current();
	}

	public function selected()
	{
		return $this->current();
	}

	public function previous()
	{
		if (!$this->offsetExists($this->list->key()-1)) 
		{
			return null;
		}
		
		return $this->list->seek($this->list->key()-1);
	}

	public function next()
	{
		return $this->list->current();
	}

	public function seek($position)
	{
		return $this->list->seek($position);
	}

	public function rewind()
	{
		return $this->list->rewind();
	}

	public function first()
	{
		return $this->rewind();
	}

	public function last()
	{
		if ($this->pos() == $this->count()-1)
		{
			return;
		}

		$this->list->seek($this->count()-1);
	}

	public function getAll()
	{
		return $this->list;
	}

	/*
	 * Sorting functions
	*/
	protected function asort()
	{
		return $this->list->asort();
	}

	protected function ksort()
	{
		return $this->list->ksort();
	}

	protected function natcasesort()
	{
		return $this->list->natcasesort();
	}

	protected function natsort()
	{
		return $this->list->natsort();
	}

	protected function uasort()
	{
		return $this->list->uasort();
	}

	protected function uksort()
	{
		return $this->list->uksort();
	}

	/*
	 * Other
	*/
	protected function setSPLFlags($flags)
	{
		return $this->list->setFlags($flags);
	}

	public function getSPLFlags()
	{
		return $this->list->getFlags();
	}

	public function __toString()
	{
		$out = '';

		if ($this->count())
		{
			$a = array();
			$list = $this->list;

			if ($list && is_array($list))
			{
				foreach ($list as $val)
				{
					$a[] = (is_string($val) ? $val : var_export($val, true));

					unset($val);
				}
			}

			$out = implode(', ',$a);
			unset($a);
		}

		return $out;
	}
}

?>