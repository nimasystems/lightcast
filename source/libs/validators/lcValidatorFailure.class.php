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
 * @changed $Id: lcValidatorFailure.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
*/

class lcValidatorFailure implements Serializable, JsonSerializable
{
	protected $name;
	protected $validator;
	protected $message;
	protected $extra_data;

	public function __construct($name, $failure_message, lcValidator $validator = null, array $extra_data = null)
	{
		$this->name = $name;
		$this->message = $failure_message;
		$this->validator = $validator;
		$this->extra_data = $extra_data;
	}

	public function setExtraData(array $data = null)
	{
		$this->extra_data = $data;
	}

	public function getExtraData()
	{
		return $this->extra_data;
	}

	public function getValidator()
	{
		return $this->validator;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getMessage()
	{
		return $this->message;
	}

	#pragma mark - Serializable

	public function serialize()
	{
		return serialize(array(
				'name' => $this->name,
				'message' => $this->message,
				'extra_data' => $this->extra_data
		));
	}

	public function unserialize($serialized)
	{
		$serialized = unserialize($serialized);
		$this->message = isset($serialized['message']) ? $serialized['message'] : null;
		$this->name = isset($serialized['name']) ? $serialized['name'] : null;
		$this->extra_data = isset($serialized['extra_data']) ? $serialized['extra_data'] : null;
	}

	#pragma mark - JsonSerializable

	public function jsonSerialize()
	{
		return json_encode(array(
				'name' => $this->name,
				'message' => $this->message,
				'extra_data' => $this->extra_data
		));
	}
}

?>