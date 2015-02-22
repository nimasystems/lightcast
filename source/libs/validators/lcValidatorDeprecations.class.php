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
 * @changed $Id: lcValidatorDeprecations.class.php 1552 2014-08-01 07:13:50Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1552 $
 */
abstract class lcValidatorBase extends lcObj
{
    /*
     * The following are deprecated methods left here only for compatibility
    * as they were put in a class with the same name
    */

    public static function validateEmail($email)
    {
        $validator = new lcEmailValidator();
        return $validator->validate($email);
    }

    public static function validateUrl($url)
    {
        $validator = new lcUrlValidator();
        return $validator->validate($url);
    }

    public static function validateDate($str)
    {
        $validator = new lcDateValidator();
        return $validator->validate($str);
    }

    /*
      Explaining $\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$
      $ = beginning of string
      \S* = any set of characters
      (?=\S{8,}) = of at least length 8
      (?=\S*[a-z]) = containing at least one lowercase letter
      (?=\S*[A-Z]) = and at least one uppercase letter
      (?=\S*[\d]) = and at least one number
      (?=\S*[\W]) = and at least a special character (non-word characters)
      $ = end of the string

   */
    public static function validatePasswordComplex($password)
    {
        if (!preg_match_all('$\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$', $password))
            return FALSE;
        return TRUE;
    }

    public static function validateUsername($username, $min_length = 6, $max_length = 30, &$errors = null)
    {
        fnothing($errors);
        $options = array('min_length' => $min_length, 'max_length' => $max_length);
        $validator = new lcUsernameValidator($options);
        return $validator->validate($username);
    }

    public static function validateNumeric($num)
    {
        $validator = new lcNumericValidator();
        return $validator->validate($num);
    }

    public static function validatePhone($phone)
    {
        $validator = new lcPhoneValidator();
        return $validator->validate($phone);
    }

    public static function validateAlnum($string, $allowWhiteSpace = false)
    {
        $options = array('alpha_numeric' => true, 'allow_whitespace' => $allowWhiteSpace);
        $validator = new lcStringValidator($options);
        return $validator->validate($string);
    }

    public static function validateAlpha($string)
    {
        $options = array('alpha_numeric' => true);
        $validator = new lcStringValidator($options);
        return $validator->validate($string);
    }

    public static function validateAndCleanNumeric($string)
    {
        return preg_replace('/[a-zA-Z \+\\\\\/]*/', '', $string);
    }


    public static function valideteAlphaNum($string)
    {
        /// use at your own risk
        return (bool)preg_replace('/[a-zA-Z0-9\+\\\\\/]*/', '', $string);
    }
}

?>