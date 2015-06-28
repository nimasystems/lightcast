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
 * @changed $Id: lcNamespaceIterator.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
*/

class lcNamespaceIterator extends lcObj implements IteratorAggregate, ArrayAccess
{
	protected $data;

	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function getIterator()
	{
		return new ArrayIterator($this->data);
	}

	public function getData()
	{
		return $this->configuration;
	}

	public function get($name)
	{
        /** @noinspection PhpUnusedLocalVariableInspection */
        $data = $this->data;

		$arr_str = '$data[\'' . str_replace('.', '\'][\'', $name) . '\']';
		$arr_str = '(isset(' . $arr_str . ') ? ' . $arr_str . ' : null)';

		$tmp = null;
		$eval_str = '$tmp = ' . $arr_str . ';';

		eval($eval_str);

		return $tmp;
	}

	public function has($name)
	{
		$tmp = null;

        /** @noinspection PhpUnusedLocalVariableInspection */
        $data = $this->data;

		$arr_str = '$data[\'' . str_replace('.', '\'][\'', $name) . '\']';
		$eval_str = '$tmp = isset(' . $arr_str . ');';

		eval($eval_str);

		return $tmp;
	}

	// @codingStandardsIgnoreStart
	public function set($name, $value = null)
	{
		$arr_str = '$this->data[\'' . str_replace('.', '\'][\'', $name) . '\']';
		$eval_str = $arr_str . ' = $value;';

		return eval($eval_str);
	}
	// @codingStandardsIgnoreEnd

	public function remove($name)
	{
		$arr_str = '$this->data[\'' . str_replace('.', '\'][\'', $name) . '\']';
		$eval_str = 'unset(' . $arr_str . ');';
		return eval($eval_str);
	}

	public function offsetExists($name)
	{
		return $this->has($name);
	}

	public function offsetGet($name)
	{
		return $this->get($name);
	}

	public function offsetSet($name, $value)
	{
		return $this->set($name, $value);
	}

	public function offsetUnset($name)
	{
		return $this->remove($name);
	}

	public function __toString()
	{
		return (string)e($this->data, true);
	}
}