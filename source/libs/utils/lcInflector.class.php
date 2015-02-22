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
 * @changed $Id: lcInflector.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcInflector
{
	# returns a string from format: underscored, spaced to: ThisIsTheString
	public static function camelize($underscored_subject, $sanitize = true)
	{
		// allow passing a sanitize parameter to speed up this where
		// sanitizing is not necessary (it makes it a LOT slower)
		$r = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($underscored_subject))));
		$r = $sanitize ? lcStrings::toAlphaNum($r) : $r;
		return $r;
	}

	# returns a string from format: underscored, spaced to: thisIsTheString
	public static function subcamelize($underscored_subject, $sanitize = true)
	{
		$underscored_subject = self::camelize($underscored_subject, $sanitize);
		$underscored_subject{0} = strtolower($underscored_subject{0});
		return $underscored_subject;
	}

	# returns a well formated string to a controller class name - TheController => cTheController
	public static function controllerize($camelized_controller_name)
	{
		return 'c'.$camelized_controller_name;
	}

	public static function asClassName($input,$type)
	{
		$input = lcStrings::toAlphaNum($input,array('-','_','.',':'));
		$input = self::camelize($input);

		return $type.$input;
	}

	public static function asFolderName($input)
	{
		$input = lcStrings::toAlphaNum($input,array('-','.',':'));
		return $input;
	}

	# when we need to remove 'c','v','t' to find a filename
	public static function asFilename($input)
	{
		return substr($input,1,strlen($input));
	}

	public static function asFuncName($input)
	{
		$input = lcStrings::toAlphaNum($input,array('-','_','.',':'));
		$input = self::camelize($input);

		$input{0} = strtolower($input{0});

		return $input;
	}

	public static function asVarName($input)
	{
		$input = lcStrings::toAlphaNum($input,array('-','_','.',':'));
		$input = self::camelize($input);

		$input{0} = strtolower($input{0});

		return $input;
	}

	public static function underscore($camelCasedWord)
	{
		$replace = strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $camelCasedWord));
		return $replace;
	}
	
	public static function humanize($camelCasedWord)
	{
		$replace = ucfirst(strtolower(preg_replace('/(?<=\\w)([A-Z])/', ' \\1', $camelCasedWord)));
		return $replace;
	}
}


?>