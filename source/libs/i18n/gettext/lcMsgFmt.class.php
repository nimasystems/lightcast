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
 * @changed $Id: lcMsgFmt.class.php 1475 2013-11-26 16:51:48Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1475 $
*/

class lcMsgFmt extends lcObj
{
	const SHELL_CMD = 'msgfmt -c %s -o %s';

	public function process($filepath, $target_filename = null)
	{
		if (!$filepath)
		{
			throw new lcInvalidArgumentException('Invalid MO filename');
		}

		$f = lcFiles::splitFileName(basename($filepath));

		if (!$f)
		{
			throw new lcInvalidArgumentException('Invalid PO filename', 1);
		}

		$mo_filename = $target_filename ? $target_filename : (realpath(dirname($filepath)) . DS . $f['name'] . '.mo');

		if (!$mo_filename)
		{
			throw new lcInvalidArgumentException('Invalid MO filename', 2);
		}

		// remove it first
		if (file_exists($mo_filename))
		{
			unlink($mo_filename);
			touch($mo_filename);
		}

		unset($f);

		exec(sprintf(self::SHELL_CMD, $filepath, $mo_filename), $output, $return);

		if ($return !== 0 && !empty($output))
		{
			throw new lcIOException(implode("\n", $output));
		}

		return true;
	}
}

?>