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
 * @changed $Id: lcTableMap.class.php 1592 2015-05-22 13:28:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1592 $
 */
class lcTableMap extends TableMap
{
    /*
     * The human readable title of the table - singular
    */
    protected $lc_title;

    /*
     * The human readable title of the table - plural
    */
    protected $lc_title_plural;

    /*
     * The context type in which the table is located
    */
    protected $lc_context_type;

    /*
     * The context name in which the table is located
    */
    protected $lc_context_name;

    public function getLcContextType()
    {
        return $this->lc_context_type;
    }

    public function getLcContextName()
    {
        return $this->lc_context_name;
    }

    public function setLcTitle($title)
    {
        return $this->lc_title = $title;
    }

    public function getLcTitle()
    {
        return $this->lc_title;
    }

    public function getLcTitlePlural()
    {
        return $this->lc_title_plural;
    }

    public function setLcTitlePlural($title)
    {
        $this->lc_title_plural = $title;
    }

    /**
     * Translated a validator string by using i18n
     * @param $string
     * @return null
     */
    public function translate($string)
    {
        if (!$string) {
            return null;
        }

        $translate_string = lcPropel::translateTableMapString($string, $this);

        if ($translate_string) {
            return $translate_string;
        }

        return $string;
    }

    public function addColumn($name, $phpName, $type, $isNotNull = false, $size = null, $defaultValue = null, $pk = false, $fkTable = null, $fkColumn = null)
    {
        $col = new lcColumnMap($name, $this);
        $col->setType($type);
        $col->setSize($size);
        $col->setPhpName($phpName);
        $col->setNotNull($isNotNull);
        $col->setDefaultValue($defaultValue);

        if ($pk) {
            $col->setPrimaryKey(true);
            $this->primaryKeys[$name] = $col;
        }

        if ($fkTable && $fkColumn) {
            $col->setForeignKey($fkTable, $fkColumn);
            $this->foreignKeys[$name] = $col;
        }

        $this->columns[$name] = $col;
        $this->columnsByPhpName[$phpName] = $col;
        $this->columnsByInsensitiveCase[strtolower($phpName)] = $col;

        return $col;
    }
}