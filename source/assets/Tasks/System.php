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

namespace Lightcast\Assets\Tasks;

use Exception;
use lcConsolePainter;
use lcDirs;
use lcFiles;
use lcFinder;
use lcInvalidArgumentException;
use lcIOException;
use lcNotAvailableException;
use lcProjectConfiguration;
use lcTaskController;
use lcYamlFileParser;
use ParagonIE\Halite\HiddenString;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto as Symmetric;
use Symfony\Component\Dotenv\Dotenv;

//require_once('parsers' . DS . 'lcYamlFileParser.class.php');

class System extends lcTaskController
{
    const CFG_BACKUP_FILE_EXT = 'frz';

    public function executeTask(): bool
    {
        switch ($this->getRequest()->getParam('action')) {
            case 'flush':
                return $this->flush();
            case 'clear-cache':
                return $this->clearCache();
            case 'clear-logs':
                return $this->clearLogs();

            case 'create-website':
                return $this->createWebsiteFromTemplate();

            case 'config':
                return $this->configMain();

            case 'config-freeze':
                return $this->configFreeze();

            case 'config-unfreeze':
                return $this->configUnFreeze();

            case 'config-clean':
                return $this->configClean();

            case 'config-list':
                return $this->configList();

            case 'config-load':
                return $this->configLoad();

            case 'config-backup':
                return $this->configBackup();

            case 'config-backup-view':
                return $this->configBackupView();

            case 'config-restore':
                return $this->configRestore();

            case 'config-generate-encryption-key':
                return $this->configGenerateEncryptionKey();

            case 'config-encrypt-secure-data':
                return $this->configEncryptSecureData();

            case 'config-decrypt-secure-data':
                return $this->configDecryptSecureData();

            default:
                return $this->displayHelp();
        }
    }

    private function flush(): bool
    {
        $this->clearTempDir();
        $this->clearCache();
        $this->clearSessionDir();

        return true;
    }

    private function clearTempDir(): bool
    {
        return lcDirs::rmdirRecursive($this->configuration->getTempDir(), true);
    }

    private function clearCache(): bool
    {
        return lcDirs::rmdirRecursive($this->configuration->getCacheDir(), true);
    }

    private function clearSessionDir(): bool
    {
        return lcDirs::rmdirRecursive($this->configuration->getSessionDir(), true);
    }

    private function clearLogs(): bool
    {
        return lcDirs::rmdirRecursive($this->configuration->getLogDir(), true);
    }

    private function createWebsiteFromTemplate(): bool
    {
        $target_dir = $this->getRequest()->getParam('target-directory');

        if (!$target_dir) {
            throw new lcInvalidArgumentException($this->t('Target directory not specified'));
        }

        $target_dir1 = realpath($target_dir);

        // check the dir
        if (!$target_dir1 || !is_dir($target_dir1) || !is_writable($target_dir1)) {
            throw new lcNotAvailableException('Directory (' . $target_dir . ') is not available or not writable');
        }

        if (!lcDirs::isDirEmpty($target_dir1)) {
            throw new lcNotAvailableException('Directory is not empty');
        }

        if (!$this->confirm(sprintf($this->t('A new website will be created in dir: %s. Please confirm with \'y\':'), $target_dir1))) {
            return true;
        }

        // copy it
        // source template
        $source_template_dir = ROOT . DS . 'source' . DS . 'assets' . DS . 'templates' . DS . 'default';

        $this->consoleDisplay('Copying website files...');

        lcDirs::xcopy($source_template_dir, $target_dir1);

        $this->consoleDisplay('Horay - your new website can be found in: ' . $target_dir1);
        return true;
    }

    private function configMain(): bool
    {
        $this->consoleDisplay($this->configHelpMenu(), false);

        return true;
    }

    private function configHelpMenu(): string
    {

        return
            'Config Menu:' . "\n" .
            'Available commands:' . "\n\n" .
            lcConsolePainter::formatConsoleText('config-freeze', 'info') . ' - Freezes the current configuration files (YML based) ' . "\n" .
            lcConsolePainter::formatConsoleText('config-unfreeze', 'info') . ' - Restores frozen configurations, param: --force (use to continue loading the frozen data even if it\'s detected to be problematic) ' . "\n" .
            lcConsolePainter::formatConsoleText('config-clean', 'info') . ' - Remove all local config files"' . "\n" .
            lcConsolePainter::formatConsoleText('config-list', 'info') . ' - List all frozen configurations' . "\n" .
            lcConsolePainter::formatConsoleText('config-load', 'info') . ' - Load a frozen configuration, param: --file=file_id - where file_id is the number obtained by issuing the \'config_list\' action, --force (forces the operation) ' . "\n" .
            lcConsolePainter::formatConsoleText('config-backup', 'info') . ' - Backups the current configuration files ' . "\n" .
            lcConsolePainter::formatConsoleText('config-backup-view', 'info') . ' - View coonfigurations in frozen data files ' . "\n" .
            lcConsolePainter::formatConsoleText('config-restore', 'info') . ' - Restore a single configuration file. Param: --file==yml_id (the number obtained by issuing the \'config_list\' action) ' . "\n";
    }

    private function configFreeze(): bool
    {
        $path = $this->getFreezeFileData();

        $files = lcFinder::search('files')->set_filter('*.yml')->set_filter('*.yaml')->do_search_in(DIR_APP);

        if (!$files) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No configuration files have been detected', 'error'));

            return true;
        }

        $this->consoleDisplay(lcConsolePainter::formatConsoleText('Configuration freeze operation started', 'error'));

        $tmp = [];

        $tmp['cache_info'] = 'Lightcast settings Backup Date: ' . date('d.m.Y H:i');
        $tmp['user_info'] = 'Configs Owner: "' . php_uname('n') . '", phpversion: "' . phpversion() . '"';

        foreach ($files as $file_path) {
            try {
                //load content
                $parser = new lcYamlFileParser($file_path);
                $content = $parser->parse();
                unset($parser);

                //remove DIR_APP//
                $file_path = str_replace(DIR_APP, '', $file_path);

                $this->consoleDisplay(sprintf('File %s has been frozen!', lcConsolePainter::formatConsoleText('"' . $file_path . '"', 'info')));

                $tmp['configs'][$file_path] = $content;
            } catch (Exception $e) {
                $this->consoleDisplay('ERROR: Could not freeze config file (' . $file_path . '): ' . $e->getMessage());
                continue;
            }

            unset($file_path, $content);
        }

        @mkdir($path['path'], 0755, true);
        lcFiles::putFile($path['path'] . $path['filename'], serialize($tmp));

        $this->consoleDisplay(lcConsolePainter::formatConsoleText('"' . count($files) . '" files frozen.! Filename " ' . $path['filename'] . '"', 'warning'));

        return true;
    }

    private function getFreezeFileData(): array
    {
        $filename = $this->getFrzFilename();

        return [
            'path' => DIR_APP . DS . 'data' . DS . 'config_backups' . DS,
            'filename' => $filename,
            'backup_filename' => $filename,
        ];
    }

    private function getFrzFilename(): string
    {
        $filename = [
            $this->configuration->getProjectName(),
            $this->configuration->getVersion(),
            'at_' . date('Y_m_d_H_i_s'),
            'by_' . get_current_user(),
            php_uname('n'),
        ];
        $filename = implode('_', $filename) . '.' . self::CFG_BACKUP_FILE_EXT;

        return $filename;
    }

    private function configUnFreeze(): bool
    {
        $force = (bool)$this->getRequest()->getParam('force');

        $path = $this->getFreezeFileData();

        if (!file_exists($path['path'] . $path['filename'])) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No backup files are available', 'error'));

            return true;
        }

        //avaivable files on the system//
        $cleanup_files = lcFinder::search('files')->set_filter('*.yml')->set_filter('*.yaml')->do_search_in(DIR_APP);

        //stored files//
        $file_data = lcFiles::getFile($path['path'] . $path['filename']);
        $file_data = unserialize($file_data);

        //configs count//
        $avaivable_configs = count($cleanup_files);
        $stored_configs = count($file_data['configs']);

        //config diff
        //security check
        if ($avaivable_configs != $stored_configs && !$force) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('Configuration conflict detected', 'error'));

            if ($avaivable_configs > $stored_configs) {
                $this->consoleDisplay(lcConsolePainter::formatConsoleText('The current available configuration files are more than the frozen ones', 'error'));

                foreach ($cleanup_files as $file_path) {
                    $file_path = str_replace(DIR_APP, '', $file_path);

                    if (!array_key_exists($file_path, $file_data['configs'])) {
                        $this->consoleDisplay(sprintf('File %s not found in stored file',
                            lcConsolePainter::formatConsoleText($file_path, 'info')));
                    }

                    unset($file_path);
                }

            } else {
                $this->consoleDisplay(lcConsolePainter::formatConsoleText('Frozen configuration files are more than the currently available ones', 'error'));

                //prepare for path check//
                $tmp = [];

                foreach ($cleanup_files as $file_path) {
                    $tmp[] = str_replace(DIR_APP, '', $file_path);

                    unset($file_path);
                }

                foreach ($file_data['configs'] as $file_path => $data) {
                    if (!in_array($file_path, $tmp)) {
                        $this->consoleDisplay(sprintf('File %s is not found in the current configuration',
                            lcConsolePainter::formatConsoleText($file_path, 'info')));
                    }

                    unset($file_path, $data);
                }

                unset($tmp);
            }

            $this->consoleDisplay(lcConsolePainter::formatConsoleText('Use --force to continue loading the frozen configuration', 'error'));

            return true;
        }


        if ($cleanup_files) {
            foreach ($cleanup_files as $file_path) {
                $this->consoleDisplay(sprintf('File %s has been removed', lcConsolePainter::formatConsoleText('"' . $file_path . '"', 'info')));

                if (file_exists($file_path)) {
                    lcFiles::rm($file_path);
                }

                unset($file_path);
            }

        }

        $file_data = lcFiles::getFile($path['path'] . $path['filename']);
        $file_data = unserialize($file_data);

        $this->consoleDisplay(lcConsolePainter::formatConsoleText($file_data['cache_info'], 'question'));
        $this->consoleDisplay(lcConsolePainter::formatConsoleText($file_data['user_info'], 'question'));

        if (isset($file_data['configs'])) {
            foreach ($file_data['configs'] as $path => $config_array) {
                try {
                    assert(is_array($config_array));

                    $parser = new lcYamlFileParser(DIR_APP . $path);
                    $parser->writeData($config_array);
                    unset($parser);

                    $this->consoleDisplay(sprintf('File %s has been unfrozen!', lcConsolePainter::formatConsoleText($path, 'info')));
                } catch (Exception $e) {
                    $this->consoleDisplay('Could not unfreeze config file (' . $path . '): ' . $e->getMessage());
                    continue;
                }

                unset($path, $config_array);
            }
        }

        return true;
    }

    private function configClean(): bool
    {
        $path = $this->getFreezeFileData();

        if (!file_exists($path['path'] . $path['filename'])) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No configuration files have been detected', 'error'));
            return true;
        }

        $files = lcFinder::search('files')->set_filter('*.yml')->set_filter('*.yaml')->do_search_in(DIR_APP);

        if (!$files) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No configuration files have been detected', 'error'));

            return true;
        }

        $this->consoleDisplay(sprintf(' %s files found. Cleanup started', lcConsolePainter::formatConsoleText('"' . count($files) . '"', 'comment')));

        foreach ($files as $file_path) {
            $this->consoleDisplay(sprintf('File %s has been removed', lcConsolePainter::formatConsoleText('"' . $file_path . '"', 'info')));

            if (file_exists($file_path)) {
                lcFiles::rm($file_path);
            }

            unset($file_path);
        }

        $this->consoleDisplay(lcConsolePainter::formatConsoleText('Removed', 'error'));

        return true;
    }

    private function configList(): bool
    {
        $files = lcFinder::search('files')->set_filter('*.' . self::CFG_BACKUP_FILE_EXT)->do_search_in(DIR_APP);

        if (!$files) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No configuration files have been detected', 'error'));

            return true;
        }

        $acc_key = 1;

        foreach ($files as $key => $val) {
            $val = strrev($val);
            $val = explode('/', $val);
            $val = strrev($val[0]);

            $this->consoleDisplay(lcConsolePainter::formatConsoleText($acc_key . ')  ', 'error') . lcConsolePainter::formatConsoleText($val, 'info'));

            $acc_key++;

            unset($key, $val);
        }

        return true;
    }

    private function configLoad(): bool
    {
        $force = (bool)$this->getRequest()->getParam('force');
        $file_id = (int)$this->getRequest()->getParam('file');

        if (!$file_id) {
            $this->consoleDisplay($this->configHelpMenu(), false);

            return true;
        }

        $files = lcFinder::search('files')->set_filter('*.' . self::CFG_BACKUP_FILE_EXT)->do_search_in(DIR_APP);

        if (!$filename = $files[$file_id - 1]) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('Invalid config file structure detected', 'error'), false);
            $this->consoleDisplay($this->configHelpMenu(), false);

            return true;
        } else {
            //iternal restore//
            $cleanup_files = lcFinder::search('files')->set_filter('*.yml')->set_filter('*.yaml')->do_search_in(DIR_APP);
            $file_data = unserialize(file_get_contents($filename));

            //configs count//
            $avaivable_configs = count($cleanup_files);
            $stored_configs = count($file_data['configs']);


            //config diff
            //security check
            if ($avaivable_configs != $stored_configs && !$force) {
                $this->consoleDisplay(lcConsolePainter::formatConsoleText('Configuration conflict detected', 'error'));

                if ($avaivable_configs > $stored_configs) {
                    $this->consoleDisplay(lcConsolePainter::formatConsoleText('The current available configuration files are more than the frozen ones', 'error'));

                    foreach ($cleanup_files as $file_path) {
                        $file_path = str_replace(DIR_APP, '', $file_path);

                        if (!array_key_exists($file_path, $file_data['configs'])) {
                            $this->consoleDisplay(sprintf('File %s not found in the frozen package',
                                lcConsolePainter::formatConsoleText($file_path, 'info')));
                        }

                        unset($file_path);
                    }
                } else {
                    $this->consoleDisplay(lcConsolePainter::formatConsoleText('Frozen configuration files are more than the currently available ones', 'error'));

                    //prepare for path check//
                    $tmp = [];

                    foreach ($cleanup_files as $file_path) {
                        $tmp[] = str_replace(DIR_APP, '', $file_path);

                        unset($file_path);
                    }

                    foreach ($file_data['configs'] as $file_path => $data) {
                        if (!in_array($file_path, $tmp)) {
                            $this->consoleDisplay(sprintf('File %s not found in the current configuration',
                                lcConsolePainter::formatConsoleText($file_path, 'info')));
                        }

                        unset($file_path, $data);
                    }

                    unset($tmp);
                }

                $this->consoleDisplay(lcConsolePainter::formatConsoleText('Use --force to continue loading the frozen configuration', 'warning'));
                return true;
            }

            if ($cleanup_files) {
                $this->consoleDisplay(lcConsolePainter::formatConsoleText('Cleanup started', 'error'));

                foreach ($cleanup_files as $file_path) {
                    $this->consoleDisplay(sprintf('File %s has been removed', lcConsolePainter::formatConsoleText('"' . $file_path . '"', 'info')));

                    if (file_exists($file_path)) {
                        lcFiles::rm($file_path);
                    }

                    unset($file_path);
                }

            }

            $this->consoleDisplay(lcConsolePainter::formatConsoleText($file_data['cache_info'], 'question'));
            $this->consoleDisplay(lcConsolePainter::formatConsoleText($file_data['user_info'], 'question'));

            if (isset($file_data['configs'])) {
                foreach ($file_data['configs'] as $path => $config_array) {
                    try {
                        assert(is_array($config_array));

                        // mkdir
                        $dir = dirname(DIR_APP . $path);
                        lcDirs::create($dir, true);

                        $parser = new lcYamlFileParser(DIR_APP . $path);
                        $parser->writeData($config_array);
                        unset($parser);

                        $this->consoleDisplay(sprintf('File %s has been unfrozen!', lcConsolePainter::formatConsoleText($path, 'info')));
                    } catch (Exception $e) {
                        $this->consoleDisplay('Could not unfreeze config file (' . $path . '): ' . $e->getMessage());
                        continue;
                    }

                    unset($path, $config_array);
                }
            }
        }

        return true;
    }

    private function configBackup(): bool
    {
        $path = $this->getFreezeFileData();

        $files = lcFinder::search('files')->set_filter('*.yml')->set_filter('*.yaml')->do_search_in(DIR_APP);

        if (!$files) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No configuration files have been detected', 'error'));

            return true;
        }

        $this->consoleDisplay(lcConsolePainter::formatConsoleText('Configuration freeze operation started', 'error'));

        $tmp = [];

        $tmp['cache_info'] = 'Lightcast settings Backup Date: ' . date('d.m.Y H:i');
        $tmp['user_info'] = 'Configs Owner: "' . php_uname('n') . '", phpversion: "' . phpversion() . '"';
        $tmp['system_info'] = $this->getCfgFileSystemInfo();

        foreach ($files as $file_path) {
            //load content
            $parser = new lcYamlFileParser($file_path);
            $content = $parser->parse();
            unset($parser);

            //remove DIR_APP//
            $file_path = str_replace(DIR_APP, '', $file_path);

            $tmp['configs'][$file_path] = $content;

            $this->consoleDisplay(sprintf('File %s has been frozen!', lcConsolePainter::formatConsoleText('"' . $file_path . '"', 'info')));

            unset($file_path, $content);
        }


        lcFiles::putFile($path['path'] . $path['backup_filename'], serialize($tmp));

        $this->consoleDisplay(lcConsolePainter::formatConsoleText('"' . count($files) . '" files frozen.! Filename " ' . $path['backup_filename'] . '"', 'information'));

        return true;

    }

    private function getCfgFileSystemInfo(): array
    {
        return [
            'project_name' => $this->configuration->getProjectName(),
            'version' => $this->configuration->getVersion(),
            'date_created' => date('Y-m-d H-i-s'),
            'user' => get_current_user(),
            'lc_version' => LC_VER,
            'machine' => php_uname('n'),
            'php_version' => phpversion(),
        ];
    }

    private function configBackupView(): bool
    {
        $path = $this->getFreezeFileData();

        if (!file_exists($path['path'] . $path['filename'])) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No backup files are available', 'error'));

            return true;
        }

        $file_data = lcFiles::getFile($path['path'] . $path['filename']);
        $file_data = unserialize($file_data);

        $i = 0;

        foreach ($file_data['configs'] as $file_path => $config) {
            $i++;
            $this->consoleDisplay(lcConsolePainter::formatConsoleText($i . ')', 'warning') . '  ' . lcConsolePainter::formatConsoleText($file_path, 'info'));

            unset($file_path, $config);
        }

        return true;
    }

    private function configGenerateEncryptionKey(): bool
    {
        $filename = $this->getRequest()->getParam('filename');
        $overwrite = (bool)$this->getRequest()->getParam('overwrite');
        $filename = $filename ?: $this->getConfig()->getProjectConfiguration()->getConfigDir() . DS .
            lcProjectConfiguration::ENCRYPTION_KEY_FILENAME;

        if (file_exists($filename) && !$overwrite) {
            throw new lcIOException('File already exists');
        }

        $encKey = KeyFactory::generateEncryptionKey();
        KeyFactory::save($encKey, $filename);

        return true;
    }

    private function configEncryptSecureData(): bool
    {
        return $this->configEncryptDecryptSecureData(true);
    }

    private function configDecryptSecureData(): bool
    {
        return $this->configEncryptDecryptSecureData(false);
    }

    private function configEncryptDecryptSecureData(bool $encrypt): bool
    {
        $key_filename = $this->getRequest()->getParam('key');
        $key_filename = $key_filename ?: $this->getConfig()->getProjectConfiguration()->getConfigDir() . DS .
            lcProjectConfiguration::ENCRYPTION_KEY_FILENAME;

        $filename = $this->getRequest()->getParam('filename');
        $filename = $filename ?: $this->getConfig()->getProjectDir() . DS .
            ($encrypt ? lcProjectConfiguration::SECURE_UNENCRYPTED_FILENAME :
                lcProjectConfiguration::SECURE_ENCRYPTED_FILENAME);

        $output = $this->getRequest()->getParam('output');
        $output = $output ?: $this->getConfig()->getProjectDir() . DS .
            (!$encrypt ? lcProjectConfiguration::SECURE_UNENCRYPTED_FILENAME :
                lcProjectConfiguration::SECURE_ENCRYPTED_FILENAME);

        if (!is_readable($filename)) {
            throw new lcIOException('Cannot open source file');
        }

        // load all the .env files
        $dotenv = new Dotenv();
        $data = $dotenv->parse(file_get_contents($filename));

        $encryption_key = KeyFactory::loadEncryptionKey($key_filename);

        $ndata = [];

        foreach ($data as $key => $val) {
            $message = new HiddenString($val);

            if ($encrypt) {
                $outtext = Symmetric::encrypt($message, $encryption_key);
            } else {
                $outtext = Symmetric::decrypt($val, $encryption_key)->getString();
            }

            $ndata[] = $key . '=' . '"' . $outtext . '"';

            unset($key, $val);
        }

        file_put_contents($output, implode("\n", $ndata));

        return true;
    }

    private function configRestore(): bool
    {
        $config_id = (int)$this->getRequest()->getParam('file');

        $path = $this->getFreezeFileData();

        if (!file_exists($path['path'] . $path['filename'])) {
            $this->consoleDisplay(lcConsolePainter::formatConsoleText('No backup files are available', 'error'));

            return true;
        }

        $file_data = lcFiles::getFile($path['path'] . $path['filename']);
        $file_data = unserialize($file_data);

        $j = 0;
        $parser = null;

        foreach ($file_data['configs'] as $file_path => $data) {
            $j++;

            if ($j == $config_id) {
                $parser = new lcYamlFileParser(DIR_APP . $file_path);
                $parser->writeData($data);

                $this->consoleDisplay(sprintf('File %s has been unfrozen!', lcConsolePainter::formatConsoleText($file_path, 'info')));

                unset($parser);
            }

            unset($file_path, $data);
        }

        return true;
    }

    private function displayHelp(): bool
    {
        $this->consoleDisplay($this->getHelpInfo(), false);

        return true;
    }

    public function getHelpInfo(): string
    {
        return
            'Possible commands:' . "\n\n" .
            'CONFIG:' . "\n\n" .
            lcConsolePainter::formatConsoleText('config-freeze', 'info') . ' - Freezes current configuration files (yamls) ' . "\n" .
            lcConsolePainter::formatConsoleText('config-unfreeze', 'info') . ' - Restores freezed configurations, params --force (suppress errors)' . "\n" .
            lcConsolePainter::formatConsoleText('config-clean', 'info') . ' - cleanup all config files"' . "\n" .
            lcConsolePainter::formatConsoleText('config-list', 'info') . ' - Lists all freezed files' . "\n" .
            lcConsolePainter::formatConsoleText('config-load', 'info') . ' - Loads external freeze file, params --file=file_id, you can see it in the config-list!, --force (suppress errors)' . "\n" .
            lcConsolePainter::formatConsoleText('config-backup', 'info') . ' - Backups configuration files' . "\n" .
            lcConsolePainter::formatConsoleText('config-backup-view', 'info') . ' - View stored configs in the freeze file' . "\n" .
            lcConsolePainter::formatConsoleText('config-restore', 'info') . ' - Restores single yml file. params --file==yml_id (you can see in in the backup-view cmd)' . "\n\n" .

            lcConsolePainter::formatConsoleText('config-generate-encryption-key', 'info') . ' - Generate a new encryption key for configuration files' . "\n" .
            ' --filename - Optional filename' . "\n" .

            lcConsolePainter::formatConsoleText('config-encrypt-secure-data', 'info') . ' - Encrypts an unencrypted .env file' . "\n" .
            ' --key - Optional key filename' . "\n" .
            ' --filename - Optional source filename' . "\n" .
            ' --output - Optional target encrypted filename' . "\n" .

            lcConsolePainter::formatConsoleText('config-decrypt-secure-data', 'info') . ' - Decrypts an unencrypted .env file' . "\n" .
            ' --key - Optional key filename' . "\n" .
            ' --filename - Optional source filename' . "\n" .
            ' --output - Optional target decrypted filename' . "\n" .

            "\n" . 'MAINTENANCE:' . "\n\n" .

            lcConsolePainter::formatConsoleText('flush', 'info') . ' - clears all website caches, temporary files and sessions' . "\n" .
            lcConsolePainter::formatConsoleText('clear-cache', 'info') . ' - clears website caches only' . "\n" .
            lcConsolePainter::formatConsoleText('clear-logs', 'info') . ' - clears all logs' . "\n" .

            "\n" . 'INITIALIZATION:' . "\n\n" .

            lcConsolePainter::formatConsoleText('create-website', 'info') . ' - initializes a new empty folder with the basic template structure of a lightcast website' .
            ' The website target folder must be specified with --target-directory';
    }
}
