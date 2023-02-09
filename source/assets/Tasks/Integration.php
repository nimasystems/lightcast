<?php
declare(strict_types=1);

namespace Lightcast\Assets\Tasks;

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

use lcConsolePainter;
use lcDirs;
use lcFiles;
use lcInvalidArgumentException;
use lcIOException;
use lcSys;
use lcTaskController;

/**
 *
 */
class Integration extends lcTaskController
{
    public function executeTask()
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'validate-php-files':
                $this->validatePhpFiles();
                break;
            default:
                $this->displayHelp();
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

        lcDirs::recursiveFilesCallback($root_dir, [$this, '_validatePhpFiles'], []);
    }

    private function displayHelp()
    {
        $this->consoleDisplay($this->getHelpInfo(), false);
    }

    public function getHelpInfo(): string
    {
        return
            'Possible commands:' . "\n\n" .
            'Validation:' . "\n\n" .
            lcConsolePainter::formatConsoleText('validate-php-files', 'info') . ' - Validates the syntax of all PHP files ' . "\n" .
            "\t- directory - specify the directory which should be checked (recursively)\n" .
            "\n";
    }

    /**
     * @param $filename
     * @return void
     */
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
}
