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

class lcPropelMysqlSchemaParser extends MysqlSchemaParser
{
    // we need to redefine this method so VIEWs are also parsed
    public function parse(Database $database, Task $task = null)
    {
        $this->addVendorInfo = $this->getGeneratorConfig()->getBuildProperty('addVendorInfo');

        $stmt = $this->dbh->query("SHOW FULL TABLES");

        // First load the tables (important that this happen before filling out details of tables)
        $tables = [];

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


    /**
     * Factory method creating a Column object
     * based on a row from the 'show columns from ' MySQL query result.
     *
     * @param array $row An associative array with the following keys:
     *                       Field, Type, Null, Key, Default, Extra.
     *
     * @return Column
     */
    public function getColumnFromRow($row, Table $table)
    {
        $row['Type'] = preg_replace(
            '@(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|((?<!:)//.*)|[\t\r\n]@i',
            '',
            $row['Type']
        );

        $name = $row['Field'];
        $is_nullable = ($row['Null'] == 'YES');
        $autoincrement = (strpos($row['Extra'], 'auto_increment') !== false);
        $size = null;
        $precision = null;
        $scale = null;
        $sqlType = false;
        $desc = $row['Comment'];

        $regexp = '/^
            (\w+)        # column type [1]
            [\(]         # (
                ?([\d,]*)  # size or size, precision [2]
            [\)]         # )
            ?\s*         # whitespace
            (\w*)        # extra description (UNSIGNED, CHARACTER SET, ...) [3]
        $/x';
        if (preg_match($regexp, $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($matches[2]) {
                if (($cpos = strpos($matches[2], ',')) !== false) {
                    $size = (int)substr($matches[2], 0, $cpos);
                    $precision = $size;
                    $scale = (int)substr($matches[2], $cpos + 1);
                } else {
                    $size = (int)$matches[2];
                }
            }
            if ($matches[3]) {
                $sqlType = $row['Type'];
            }
            foreach (self::$defaultTypeSizes as $type => $defaultSize) {
                if ($nativeType == $type && $size == $defaultSize && $scale === null) {
                    $size = null;
                    continue;
                }
            }
        } else if (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
            $nativeType = $matches[1];
            if ($nativeType == 'enum') {
                $sqlType = $row['Type'];
            }
        } else {
            $nativeType = $row['Type'];
        }

        //BLOBs can't have any default values in MySQL
        $default = preg_match('~blob|text~', $nativeType) ? null : $row['Default'];

        $propelType = $this->getMappedPropelType($nativeType);

        if (!$propelType) {
            $propelType = Column::DEFAULT_TYPE;
            $sqlType = $row['Type'];
            $this->warn("Column [" . $table->getName() . "." . $name . "] has a column type (" . $nativeType . ") that Propel does not support.");
        }

        // Special case for TINYINT(1) which is a BOOLEAN
        if (PropelTypes::TINYINT === $propelType && 1 === $size) {
            $propelType = PropelTypes::BOOLEAN;
        }

        $column = new Column($name);
        $column->setTable($table);
        $column->setDomainForType($propelType);
        if ($sqlType) {
            $column->getDomain()->replaceSqlType($sqlType);
        }
        $column->getDomain()->replaceSize($size);
        $column->getDomain()->replaceScale($scale);
        if ($default !== null) {
            if ($propelType == PropelTypes::BOOLEAN) {
                if ($default == '1') {
                    $default = 'true';
                }
                if ($default == '0') {
                    $default = 'false';
                }
            }
            if (in_array($default, ['CURRENT_TIMESTAMP'])) {
                $type = ColumnDefaultValue::TYPE_EXPR;
            } else {
                $type = ColumnDefaultValue::TYPE_VALUE;
            }
            $column->getDomain()->setDefaultValue(new ColumnDefaultValue($default, $type));
        }
        $column->setAutoIncrement($autoincrement);
        $column->setNotNull(!$is_nullable);

        if ($this->addVendorInfo) {
            $vi = $this->getNewVendorInfoObject($row);
            $column->addVendorInfo($vi);
        }

        if ($desc) {
            if (!$this->isUtf8($desc))
                $desc = utf8_encode($desc);
            $column->setDescription($desc);
        }

        return $column;
    } // addColumn()
}