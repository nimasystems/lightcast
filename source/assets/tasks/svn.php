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

class tSvn extends lcTaskController
{
    const SVN_COMMAND = 'svn';
    const SVN_DATA_DIR = 'svn';

    public function executeTask()
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'list-changed-files':
                return $this->listChangedFiles();
                break;

            case 'download-changed-files':
                return $this->downloadChangedFiles();
                break;

            default:
                return $this->displayHelp();
        }
    }

    private function listChangedFiles()
    {
        $r = $this->getRequest();
        $repo_url = (string)$r->getParam('repo');
        $rev1 = (string)$r->getParam('rev1');
        $rev2 = (string)$r->getParam('rev2');

        if (!$repo_url || !$rev1) {
            throw new lcInvalidArgumentException('Missing REPO Url (--repo) / Rev1 (--rev1)');
        }

        $rev2 = $rev2 ? $rev2 : 'HEAD';

        $list = $this->svnGetDiffSummary($repo_url, $rev1, $rev2);

        $this->consoleDisplay('Changed files in repo (' . $repo_url . ') from: \'' . $rev1 . '\' to: \'' . $rev2 . '\':' . "\n\n");

        if (!$list) {
            $this->consoleDisplay('No changes detected');
        } else {
            foreach ($list as $file) {
                $this->consoleDisplay($file, false);

                unset($file);
            }
        }

        $this->consoleDisplay("\n", false);

        return $list;
    }

    private function svnGetDiffSummary($repo_url, $rev1, $rev2)
    {
        $repo_url = (string)$repo_url;
        $rev1 = (string)$rev1;
        $rev2 = (string)$rev2;

        if (!$repo_url || !$rev1 || !$rev2) {
            throw new lcInvalidArgumentException('Invalid params');
        }

        // exec the cmd
        $cmd = self::SVN_COMMAND . ' diff -r ' . escapeshellarg($rev1) . ':' . escapeshellarg($rev2) . ' --summarize ' . escapeshellarg($repo_url);

        $result = 0;
        $output = lcSys::execCmd($cmd, $result, true);

        if ($result != 0) {
            throw new lcSystemException('Could not execute svn command: ' . print_r($output, true));
        }

        if (!$output || !is_array($output)) {
            return false;
        }

        $files = [];

        foreach ($output as $line) {
            $mod = trim(substr($line, 0, strpos($line, ' ')));

            // skip deleted files
            if ($mod == 'D') {
                continue;
            }

            $file = substr($line, strrpos($line, ' '), strlen($line));

            if (!$file) {
                assert(false);
                continue;
            }

            // strip the url
            $file = str_replace($repo_url, '', $file);

            $files[] = trim($file);

            unset($line);
        }

        $files = array_unique($files);

        sort($files);

        return $files;
    }

    private function downloadChangedFiles()
    {
        $r = $this->getRequest();
        $repo_url = (string)$r->getParam('repo');
        $rev1 = (string)$r->getParam('rev1');
        $rev2 = (string)$r->getParam('rev2');

        if (!$repo_url || !$rev1) {
            throw new lcInvalidArgumentException('Missing REPO Url (--repo) / Rev1 (--rev1)');
        }

        $rev2 = $rev2 ? $rev2 : 'HEAD';

        $t = parse_url($repo_url);

        if (!$t) {
            throw new lcInvalidArgumentException('Repo URL seems to be invalid - cannot be parsed');
        }

        $dt = $t['host'] . '_' . str_replace('/', '.', $t['path']);
        $download_path = $this->configuration->getDataDir() . DS . self::SVN_DATA_DIR . DS . 'diff_downloads' . DS . $dt . DS . $rev1 . '-' . $rev2;

        $changed_files = $this->listChangedFiles();

        if (!$changed_files) {
            return true;
        }

        $this->consoleDisplay('Storing changed files in: ' . $download_path);

        // remove / create the dir
        if (is_dir($download_path)) {
            lcDirs::rmdirRecursive($download_path);
        }

        lcDirs::mkdirRecursive($download_path);

        $this->consoleDisplay("\n", true);

        foreach ($changed_files as $file) {
            $this->consoleDisplay('Downloading ' . $file);

            $repo_file_url = $repo_url . $file;
            $local_destination = $download_path . $file;
            $local_destination_dir = dirname($local_destination);

            // create the dir
            lcDirs::mkdirRecursive($local_destination_dir);

            $res = $this->svnExportFile($repo_file_url, $local_destination);

            if (!$res) {
                throw new lcSystemException('Could not download file');
            }

            unset($file, $local_destination, $repo_file_url);
        }

        return true;
    }

    private function svnExportFile($repo_file_url, $local_destination, $rev = null)
    {
        $local_destination = (string)$local_destination;
        $repo_file_url = (string)$repo_file_url;
        $rev = isset($rev) ? (string)$rev : null;

        if (!$repo_file_url) {
            throw new lcInvalidArgumentException('Invalid file URL');
        }

        if (!$local_destination) {
            throw new lcInvalidArgumentException('Local destionation is not set');
        }

        // exec the cmd
        $cmd = self::SVN_COMMAND . ' export ' . ($rev ? '-r ' . escapeshellarg($rev) . ' ' : null) . escapeshellarg($repo_file_url) . ' ' . escapeshellarg($local_destination);

        $result = 0;
        $output = lcSys::execCmd($cmd, $result, true);

        if ($result != 0) {
            throw new lcSystemException('Could not execute svn command: ' . print_r($output, true));
        }

        return true;
    }

    private function displayHelp()
    {
        $this->consoleDisplay($this->getHelpInfo(), false);

        return true;
    }

    public function getHelpInfo()
    {
        return
            'Possible commands:

				SVN:

- ' . lcConsolePainter::formatConsoleText('list-changed-files', 'info') . ' - Get a list of all changed files between --rev1 and --rev2
- ' . lcConsolePainter::formatConsoleText('download-changed-files', 'info') . ' - Download all changed files into --dest from --rev1 to --rev2';
    }
}
