<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../model/Table.php';
require_once dirname(__FILE__) . '/../model/Column.php';
require_once dirname(__FILE__) . '/PropelSQLParser.php';
require_once dirname(__FILE__) . '/../../../runtime/lib/Propel.php';

/**
 * Service class for preparing and executing migrations
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.util
 */
class PropelMigrationManager
{
    protected $connections;
    protected $pdoConnections = [];
    protected $migrationTable = 'propel_migration';
    protected $migrationDir;

    public static function getMigrationFileName($timestamp)
    {
        return sprintf('%s.php', self::getMigrationClassName($timestamp));
    }

    public function migrationTableExists($datasource)
    {
        $pdo = $this->getPdoConnection($datasource);
        $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute();

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getPdoConnection($datasource)
    {
        if (!isset($this->pdoConnections[$datasource])) {
            $buildConnection = $this->getConnection($datasource);
            $buildConnection['dsn'] = str_replace("@DB@", $datasource, $buildConnection['dsn']);

            $this->pdoConnections[$datasource] = Propel::initConnection($buildConnection, $datasource);
        }

        return $this->pdoConnections[$datasource];
    }

    public function getConnection($datasource)
    {
        if (!isset($this->connections[$datasource])) {
            throw new InvalidArgumentException(sprintf('Unknown datasource "%s"', $datasource));
        }

        return $this->connections[$datasource];
    }

    /**
     * get the migration table name
     *
     * @return string
     */
    public function getMigrationTable()
    {
        return $this->migrationTable;
    }

    /**
     * Set the migration table name
     *
     * @param string $migrationTable
     */
    public function setMigrationTable($migrationTable)
    {
        $this->migrationTable = $migrationTable;
    }

    public function updateLatestMigrationTimestamp($datasource, $timestamp)
    {
        $platform = $this->getPlatform($datasource);
        $pdo = $this->getPdoConnection($datasource);
        $sql = sprintf('DELETE FROM %s', $this->getMigrationTable());
        $pdo->beginTransaction();
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $sql = sprintf('INSERT INTO %s (%s) VALUES (?)',
            $this->getMigrationTable(),
            $platform->quoteIdentifier('version')
        );
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $timestamp, PDO::PARAM_INT);
        $stmt->execute();
        $pdo->commit();
    }

    public function getPlatform($datasource)
    {
        $params = $this->getConnection($datasource);
        $adapter = $params['adapter'];
        $adapterClass = ucfirst($adapter) . 'Platform';
        require_once sprintf('%s/../platform/%s.php',
            dirname(__FILE__),
            $adapterClass
        );

        return new $adapterClass();
    }

    public function hasPendingMigrations()
    {
        return [] !== $this->getValidMigrationTimestamps();
    }

    public function getValidMigrationTimestamps()
    {
        $oldestMigrationTimestamp = $this->getOldestDatabaseVersion();
        $migrationTimestamps = $this->getMigrationTimestamps();
        // removing already executed migrations
        foreach ($migrationTimestamps as $key => $timestamp) {
            if ($timestamp <= $oldestMigrationTimestamp) {
                unset($migrationTimestamps[$key]);
            }
        }
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    public function getOldestDatabaseVersion()
    {
        if (!$connections = $this->getConnections()) {
            throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
        }
        $oldestMigrationTimestamp = null;
        $migrationTimestamps = [];
        foreach ($connections as $name => $params) {
            $pdo = $this->getPdoConnection($name);
            $sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());

            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                if ($migrationTimestamp = $stmt->fetchColumn()) {
                    $migrationTimestamps[$name] = $migrationTimestamp;
                }
            } catch (PDOException $e) {
                $this->createMigrationTable($name);
                $oldestMigrationTimestamp = 0;
            }
        }
        if ($oldestMigrationTimestamp === null && $migrationTimestamps) {
            sort($migrationTimestamps);
            $oldestMigrationTimestamp = array_shift($migrationTimestamps);
        }

        return $oldestMigrationTimestamp;
    }

    /**
     * Get the database connection settings
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Set the database connection settings
     *
     * @param array $connections
     */
    public function setConnections($connections)
    {
        $this->connections = $connections;
    }

    public function createMigrationTable($datasource)
    {
        $platform = $this->getPlatform($datasource);
        // modelize the table
        $database = new Database($datasource);
        $database->setPlatform($platform);
        $table = new Table($this->getMigrationTable());
        $database->addTable($table);
        $column = new Column('version');
        $column->getDomain()->copy($platform->getDomainForType('INTEGER'));
        $column->setDefaultValue(0);
        $table->addColumn($column);
        // insert the table into the database
        $statements = $platform->getAddTableDDL($table);
        $pdo = $this->getPdoConnection($datasource);
        $res = PropelSQLParser::executeString($statements, $pdo);
        if (!$res) {
            throw new Exception(sprintf('Unable to create migration table in datasource "%s"', $datasource));
        }
    }

    public function getMigrationTimestamps()
    {
        $path = $this->getMigrationDir();
        $migrationTimestamps = [];

        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (preg_match('/^PropelMigration_(\d+)\.php$/', $file, $matches)) {
                    $migrationTimestamps[] = (integer)$matches[1];
                }
            }
        }

        return $migrationTimestamps;
    }

    /**
     * Get the path to the migration classes
     *
     * @return string
     */
    public function getMigrationDir()
    {
        return $this->migrationDir;
    }

    /**
     * Set the path to the migration classes
     *
     * @param string $migrationDir
     */
    public function setMigrationDir($migrationDir)
    {
        $this->migrationDir = $migrationDir;
    }

    public function getAlreadyExecutedMigrationTimestamps()
    {
        $oldestMigrationTimestamp = $this->getOldestDatabaseVersion();
        $migrationTimestamps = $this->getMigrationTimestamps();
        // removing already executed migrations
        foreach ($migrationTimestamps as $key => $timestamp) {
            if ($timestamp > $oldestMigrationTimestamp) {
                unset($migrationTimestamps[$key]);
            }
        }
        sort($migrationTimestamps);

        return $migrationTimestamps;
    }

    public function getFirstUpMigrationTimestamp()
    {
        $validTimestamps = $this->getValidMigrationTimestamps();

        return array_shift($validTimestamps);
    }

    public function getFirstDownMigrationTimestamp()
    {
        return $this->getOldestDatabaseVersion();
    }

    public function getMigrationObject($timestamp)
    {
        $className = $this->getMigrationClassName($timestamp);
        require_once sprintf('%s/%s.php',
            $this->getMigrationDir(),
            $className
        );

        return new $className();
    }

    public static function getMigrationClassName($timestamp)
    {
        return sprintf('PropelMigration_%d', $timestamp);
    }

    public function getMigrationClassBody($migrationsUp, $migrationsDown, $timestamp)
    {
        $timeInWords = date('Y-m-d H:i:s', $timestamp);
        $migrationAuthor = ($author = $this->getUser()) ? 'by ' . $author : '';
        $migrationClassName = $this->getMigrationClassName($timestamp);
        $migrationUpString = var_export($migrationsUp, true);
        $migrationDownString = var_export($migrationsDown, true);
        return <<<EOP
<?php

/**
 * Data object containing the SQL and PHP code to migrate the database
 * up to version $timestamp.
 * Generated on $timeInWords $migrationAuthor
 */
class $migrationClassName
{

    public function preUp(\$manager)
    {
        // add the pre-migration code here
    }

    public function postUp(\$manager)
    {
        // add the post-migration code here
    }

    public function preDown(\$manager)
    {
        // add the pre-migration code here
    }

    public function postDown(\$manager)
    {
        // add the post-migration code here
    }

    /**
     * Get the SQL statements for the Up migration
     *
     * @return array list of the SQL strings to execute for the Up migration
     *               the keys being the datasources
     */
    public function getUpSQL()
    {
        return $migrationUpString;
    }

    /**
     * Get the SQL statements for the Down migration
     *
     * @return array list of the SQL strings to execute for the Down migration
     *               the keys being the datasources
     */
    public function getDownSQL()
    {
        return $migrationDownString;
    }

}
EOP;
    }

    public static function getUser()
    {
        if (function_exists('posix_getuid')) {
            $currentUser = posix_getpwuid(posix_getuid());
            if (isset($currentUser['name'])) {
                return $currentUser['name'];
            }
        }

        return '';
    }
}
