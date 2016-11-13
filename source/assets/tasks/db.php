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

class tDb extends lcTaskController
{
    public function getHelpInfo()
    {
        $help =
            lcConsolePainter::formatColoredConsoleText('Database operations', 'green') . "\n" .
            lcConsolePainter::formatColoredConsoleText('--------------------', 'green') . "\n\n" .
            lcConsolePainter::formatColoredConsoleText('Schema:', 'cyan') . "\n\n" .
            'schema:upgrade_to_utf8mb4 - Upgrade the database and all tables to the UTF8MB4 / UT8MB4_UNICODE_CI collation
                --db - database name';

        return $help;
    }

    public function executeTask()
    {
        $action = $this->getRequest()->getParam('action');

        switch ($action) {
            /* schema:upgrade_to_utf8mb4 */
            case 'schema:upgrade_to_utf8mb4':
                return $this->upgradeEncodingToUTF8MB4();

            default:
                $this->display($this->getHelpInfo(), false);
                return true;
        }
    }

    private function upgradeEncodingToUTF8MB4()
    {
        $db_name = $this->getRequest()->getParam('db');

        if (!$db_name) {
            throw new lcInvalidArgumentException($this->t('Invalid database'));
        }

        $db_name = '`' . $db_name . '`';

        $db = $this->dbc;

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

                    if (strstr($type, 'varchar(') || strstr($type, 'char(') ||
                        strstr($type, 'text') || strstr($type, 'enum')
                    ) {
                        $this->display('Converting table: ' . $table . ':' . $col_name);

                        $sql = 'ALTER TABLE `' . $table . '` CHANGE `' . $col_name . '` `' . $col_name . '` 
                        ' . $type . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

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
}