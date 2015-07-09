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
 * @changed $Id: integration.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class tIntegration extends lcTaskController
{
    public function getHelpInfo()
    {
        return
            'Possible commands:' . "\n\n" .
            'Validation:' . "\n\n" .
            lcConsolePainter::formatConsoleText('validate-php-files', 'info') . ' - Validates the syntax of all PHP files ' . "\n" .
            "\t- directory - specify the directory which should be checked (recursively)\n" .
            "\n";
    }

    public function executeTask()
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'validate-php-files':
                return $this->validatePhpFiles();
            default:
                return $this->displayHelp();
        }
    }

    private function displayHelp()
    {
        $this->consoleDisplay($this->getHelpInfo(), false);
        return true;
    }

    public function _validatePhpFiles($filename)
    {
        if (lcFiles::getFileExt($filename) != '.php') {
            return;
        }

        $result = null;
        $ret = lcSys::execCmd('php -l ' . escapeshellarg($filename), $result);

        if ($result !== 0) {
            $this->displayError('Validation error (' . $filename . '): ' . $ret);
        } else {
            $this->display('OK: ' . $filename);
        }
    }

    private function validatePhpFiles()
    {
        $r = $this->request;
        $root_dir = $r->getParam('directory');

        if (!$root_dir) {
            throw new lcInvalidArgumentException('A directory must be specified');
        }

        if (!is_dir($root_dir) || !is_readable($root_dir)) {
            throw new lcIOException('Directory is not readable');
        }

        $root_dir = realpath($root_dir) ? realpath($root_dir) : $root_dir;

        $this->display('Validating PHP syntax in: ' . $root_dir);

        lcDirs::recursiveFilesCallback($root_dir, array($this, '_validatePhpFiles'), array(), true);

        return true;
    }
}
