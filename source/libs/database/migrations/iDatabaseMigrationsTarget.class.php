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
 * @changed $Id: iDatabaseMigrationsTarget.class.php 1473 2013-11-17 10:38:32Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1473 $
 */
interface iDatabaseMigrationsTarget
{
    public function getSchemaIdentifier();

    public function getMigrationsVersion();

    public function executeMigrationUpgrade($from_version, $to_version);

    public function executeMigrationDowngrade($from_version, $to_version);

    public function executeSchemaInstall();

    public function executeSchemaRemove();

    public function executeDataInstall();

    public function executeDataRemove();

    public function beforeExecute();

    public function afterExecute();
}