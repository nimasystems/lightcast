<?php
declare(strict_types=1);
/** @noinspection PhpComposerExtensionStubsInspection */

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

use Exception;
use lcConsolePainter;
use lcInvalidArgumentException;
use lcNotAvailableException;
use lcTaskController;
use PDO;

/**
 *
 */
class Db extends lcTaskController
{
    public function executeTask(): bool
    {
        $action = $this->getRequest()->getParam('action');

        switch ($action) {
            case 'schema:upgrade_encoding':
                return $this->upgradeEncoding();

            default:
                $this->display($this->getHelpInfo(), false);
                return true;
        }
    }

    private function upgradeEncoding(): bool
    {
        $db_name = $this->getRequest()->getParam('db');

        $hostname = $this->getRequest()->getParam('hostname');
        $hostname = $hostname ?: 'localhost';

        $user = $this->getRequest()->getParam('user');
        $user = $user ?: 'root';

        $pass = $this->getRequest()->getParam('pass');
        $encoding = $this->getRequest()->getParam('encoding');
        $collation = $this->getRequest()->getParam('collation');

        if (!$db_name || !$hostname || !$encoding || !$collation) {
            throw new lcInvalidArgumentException($this->t('Invalid database'));
        }

        $dsn = 'mysql:host=' . $hostname . ';dbname=' . $db_name;
        $db = new PDO($dsn, $user, $pass, [
            PDO::ERRMODE_EXCEPTION,
        ]);
        $db->exec('SET NAMES ' . $encoding . ' COLLATE ' . $collation);

        $db_name = '`' . $db_name . '`';

        $tables = $db->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_COLUMN);

        if (!$tables) {
            throw new lcNotAvailableException($this->t('Database is empty'));
        }

        foreach ($tables as $table) {

            $this->display('Converting table: ' . $table);

            $descr = $db->query('DESCRIBE `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);

            if ($descr) {
                foreach ($descr as $row) {

                    $col_name = $row['Field'];
                    $type = strtolower($row['Type']);
                    $can_be_null = $row['Null'] == 'YES';
                    $default = $row['Default'];

                    if (false !== strpos($type, 'varchar(') || false !== strpos($type, 'char(') ||
                        false !== strpos($type, 'text') || false !== strpos($type, 'enum')
                    ) {
                        $this->display('Converting table: ' . $table . ':' . $col_name);

                        $sql = 'ALTER TABLE `' . $table . '` CHANGE `' . $col_name . '` `' . $col_name . '`
                        ' . $type .
                            ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' .
                            (!$can_be_null ? ' NOT NULL' : null) .
                            ($default ? ' DEFAULT \'' . $default . '\'' : null);

                        try {
                            $db->exec($sql);
                        } catch (Exception $e) {
                            $this->displayError('Col change error: ' . $sql . ': ' . $e->getMessage());
                        }
                    }

                    unset($row);
                }
            }

            $sql = 'ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

            try {
                $db->exec($sql);
            } catch (Exception $e) {
                $this->displayError('table change error: `' . $table . '`: ' . $sql . ': ' . $e->getMessage());
            }

            unset($table);
        }

        $this->display('Converting database: ' . $db_name);

        $db->exec('ALTER DATABASE ' . $db_name . ' CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci');

        return true;
    }

    public function getHelpInfo(): string
    {
        return lcConsolePainter::formatColoredConsoleText('Database operations', 'green') . "\n" .
            lcConsolePainter::formatColoredConsoleText('--------------------', 'green') . "\n\n" .
            lcConsolePainter::formatColoredConsoleText('Schema:', 'cyan') . "\n\n" .
            'schema:upgrade_encoding - Upgrade the database and all tables to another encoding/collation (for example: UTF8MB4 / UT8MB4_UNICODE_CI)
            --db - database name
            --hostname=localhost
            --user=root
            --pass
            --encoding=utf8m4
            --collation=utf8mb4_unicode_ci';
    }
}
