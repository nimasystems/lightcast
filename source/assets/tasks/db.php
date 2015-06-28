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
 * @changed $Id: db.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1592 $
*/

class tDb extends lcTaskController
{
	public function getHelpInfo()
	{
		$help =
		lcConsolePainter::formatColoredConsoleText('Database operations', 'green') . "\n" .
		lcConsolePainter::formatColoredConsoleText('--------------------', 'green') . "\n\n" .
		lcConsolePainter::formatColoredConsoleText('Schema:', 'cyan') . "\n\n" .
		'{fgcolor:red}schema:upgrade{/fgcolor} - Upgrades the current project\'s and plugins schemas to the latest version possible.' . "\n" .
		' {fgcolor:green}--only-project{/fgcolor} - Upgrade only the project\'s schema' . "\n" .
		' {fgcolor:green}--only-plugin=[PLUGIN_NAME]{/fgcolor} - Upgrade only the specified plugin\'s schema';

		return $help;
	}

	public function executeTask()
	{
		$action = $this->getRequest()->getParam('action');

		switch($action)
		{
			/* schema:upgrade */
			case 'schema:upgrade':
				return $this->upgradeSchema();

			default:
				$this->display($this->getHelpInfo(), false);
				return true;
		}
	}

	private function upgradeSchema()
	{
		return true;
	}
}