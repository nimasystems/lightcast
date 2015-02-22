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
 * @changed $Id: lcPropelMysqlSchemaParser.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
* @version $Revision: 1455 $
*/

class lcPropelMysqlSchemaParser extends MysqlSchemaParser
{
	// we need to redefine this method so VIEWs are also parsed
	public function parse(Database $database, Task $task = null)
	{
		$this->addVendorInfo = $this->getGeneratorConfig()->getBuildProperty('addVendorInfo');

		$stmt = $this->dbh->query("SHOW FULL TABLES");

		// First load the tables (important that this happen before filling out details of tables)
		$tables = array();

		if ($task) {
			$task->log("Reverse Engineering Tables", Project::MSG_VERBOSE);
		}

		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$name = $row[0];
			$type = $row[1];

			if ($name == $this->getMigrationTable() || ($type != "BASE TABLE" && $type != "VIEW")) {
				continue;
			}

			if ($task) {
				$task->log("  Adding table '" . $name . "'", Project::MSG_VERBOSE);
			}

			$table = new Table($name);
			$table->setIdMethod($database->getDefaultIdMethod());
			$database->addTable($table);
			$tables[] = $table;
		}

		// Now populate only columns.
		if ($task) {
			$task->log("Reverse Engineering Columns", Project::MSG_VERBOSE);
		}

		foreach ($tables as $table) {
			if ($task) {
				$task->log("  Adding columns for table '" . $table->getName() . "'", Project::MSG_VERBOSE);
			}
			$this->addColumns($table);
		}

		// Now add indices and constraints.
		if ($task) {
			$task->log("Reverse Engineering Indices And Constraints", Project::MSG_VERBOSE);
		}

		foreach ($tables as $table) {
			if ($task) {
				$task->log("  Adding indices and constraints for table '" . $table->getName() . "'", Project::MSG_VERBOSE);
			}

			$this->addForeignKeys($table);
			$this->addIndexes($table);
			$this->addPrimaryKey($table);

			if ($this->addVendorInfo) {
				$this->addTableVendorInfo($table);
			}
		}

		return count($tables);
	}
}


?>