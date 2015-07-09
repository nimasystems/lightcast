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
 * @changed $Id: lcProjectTranslationsParser.class.php 1519 2014-05-19 09:11:03Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1519 $
 */
class lcProjectTranslationsParser extends lcTranslationsParser
{
    public function getCategorizationMap()
    {
        return array(
            'models' => 'database_models',
            'gen' => 'database_models',
        );
    }

    public function getDirsToParse()
    {
        return array(
            'extensions',
            'models',
            'gen',
            'tasks',
            'ws'
        );
    }
}