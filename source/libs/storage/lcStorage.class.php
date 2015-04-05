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
 * @changed $Id: lcStorage.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

abstract class lcStorage extends lcSysObj implements iProvidesCapabilities, ArrayAccess, iDebuggable
{
	protected $ignore_time_tracking;
	
	public function initialize()
	{
		parent::initialize();
		
		$this->readFromStorage();
	}

	public function shutdown()
	{
		if (!$this->ignore_time_tracking)
		{
			$this->trackTime();
		}
		
		$this->writeToStorage();
		
		parent::shutdown();
	}

	public function getCapabilities()
	{
		return array(
				'storage'
		);
	}
	
	public function getDebugInfo()
	{
		$namespaces = $this->getNamespaces();

		$out = array();

		if ($namespaces)
		{
			foreach($namespaces as $namespace)
			{
				$vals = $this->getAll($namespace);

				$out[$namespace]['total_items'] = (is_array($vals) ? count($vals) : 0);

				if ($vals)
				{
					foreach($vals as $key => $value)
					{
						if (!$value)
						{
							continue;
						}

						if (!is_numeric($value) && !is_string($value) && !is_array($value) && !is_bool($value))
						{
							$value = '(complex)';
						}

						// shorten
						if (is_string($value) && strlen($value) > 255)
						{
							$value = substr($value, 0, 255) . '...';
						}

						$out[$namespace]['items'][$key] = $value;

						unset($key, $value);
					}
				}

				unset($namespace);
			}
		}

		$debug = array(
				'items' => $out
				);

		return $debug;
	}

	public function getShortDebugInfo()
	{
		return false;
	}
	
	public function setIgnoreTimeTracking($ignore_time_tracking = false)
	{
		$this->ignore_time_tracking = $ignore_time_tracking;
	}

	protected function trackTime()
	{
		// to be overriden by subclassers
	}
	
	protected function writeToStorage()
	{
		// to be overriden by subclassers
	}
	
	protected function readFromStorage()
	{
		// to be overriden by subclassers
	}
	
	abstract public function has($key, $namespace = null);
	abstract public function get($key,$namespace = null);
	abstract public function set($key, $value, $namespace = null);
	abstract public function remove($key, $namespace = null);

	abstract public function clear($namespace = null);

	abstract public function clearAll();

	abstract public function hasValues($namespace = null);
	abstract public function count($namespace = null);

	abstract public function getNamespaces();
	abstract public function getAll($namespace = null);
	abstract public function getBackendData();

	public function offsetExists($name)
	{
		return $this->get($name) ? true : false;
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
		$all = $this->getBackendData();

		return (string)e($all, true);
	}
}

?>