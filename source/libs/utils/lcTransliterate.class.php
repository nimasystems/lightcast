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
 * @changed $Id: lcTransliterate.class.php 1524 2014-05-26 10:00:52Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1524 $
 */

class lcTransliterate
{
	public static function cyrLat($str, $inverse = false) {
		if (!$str) {
			return $str;
		}

		$cyr  = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у',
				'ф','х','ц','ч','ш','щ','ъ', 'ы','ь', 'э', 'ю','я','А','Б','В','Г','Д','Е','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У',
				'Ф','Х','Ц','Ч','Ш','Щ','Ъ', 'Ы','Ь', 'Э', 'Ю','Я' );
		$lat = array( 'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p','r','s','t','u',
				'f' ,'h' ,'ts' ,'ch','sh' ,'sht' ,'a', 'i', 'y', 'e' ,'yu' ,'ya','A','B','V','G','D','E','Zh',
				'Z','I','Y','K','L','M','N','O','P','R','S','T','U',
				'F' ,'H' ,'Ts' ,'Ch','Sh' ,'Sht' ,'A' ,'Y' ,'Yu' ,'Ya' );

		if (!$inverse) {
			$str = str_replace($cyr, $lat, $str);
		} else {
			$str = str_replace($lat, $cyr, $str);
		}

		return $str;
	}
}

?>