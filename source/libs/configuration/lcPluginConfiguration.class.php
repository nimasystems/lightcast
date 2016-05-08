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

class lcPluginConfiguration extends lcConfiguration implements iSupportsVersions, iSupportsDatabaseMigrations
{
    const STARTUP_TYPE_AUTOMATIC = 'auto';
    const STARTUP_TYPE_MANUAL = 'manual';
    const STARTUP_TYPE_EVENT_BASED = 'event_based';

    const DB_MIGRATIONS_FILENAME = 'db_migrations.php';

    protected $name;
    protected $root_dir;
    protected $web_path;

    public function initialize()
    {
        if (!$this->root_dir) {
            throw new lcSystemException('Plugin directory not valid');
        }

        parent::initialize();
    }

    /**
     * @return string
     */
    public function getPackageName()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getPluginDir()
    {
        return $this->root_dir;
    }

    /**
     * @return string
     */
    public function getWebPath()
    {
        return $this->web_path;
    }

    public function setWebPath($web_path)
    {
        $this->web_path = $web_path;
    }

    /**
     * @return array|null
     */
    public function getRoutes()
    {
        return $this['routes'];
    }

    /**
     * @return string
     */
    public function getStartupType()
    {
        // subclassers may override this method and ask to start the plugin
        // automatically or manually
        // automatically will boot the plugin at app init if:
        // - it provides loaders
        // - there are no automatic startup events
        // otherwise if automatic and there are startup events defined - the
        // plugin will be initialized
        // at the event sending time
        return self::STARTUP_TYPE_MANUAL;
    }

    /**
     * @return array|null
     */
    public function getAutomaticStartupEvents()
    {
        // subclassers may override this method and return an array of event
        // dispatcher notifications
        // which - when observed in the system - will automatically boot the
        // plugin prior the event!
        return null;
    }

    /**
     * @return array|null
     */
    public function getPluginAuthor()
    {
        // subclassers may override this method to return informationa bout the
        // author of the plugin
        return array(
            'company' => array(
                'url' => 'http://www.nimasystems.com',
                'email' => 'info@nimasystems.com',
                'name' => 'Nimasystems Ltd'
            ),
            'license' => array(
                'url' => 'http://www.nimasystems.com/lightcast',
                'type' => 'private'
            ),
            'copyright' => 'Nimasystems Ltd 2007-2013 (&copy;) All Rights Reserved.',
            'developers' => array(array(
                'email' => 'miracle@nimasystems.com',
                'team' => 'PHP Development',
                'role' => 'PHP Developer',
                'name' => 'Martin Kovachev'
            ))
        );
    }

    public function getPluginDescription()
    {
        // subclassers may override this method to return additional short
        // introduction of the plugin
        return array(
            'description' => 'No additional description provided',
            'urls' => array(
                array(
                    'url' => 'http://lightcast.nimasystems.com/plugins/sample_plugin',
                    'title' => 'Homepage'
                ),
                array(
                    'url' => 'http://lightcast.nimasystems.com/plugins/sample_plugin/download',
                    'title' => 'Download'
                ),
                array(
                    'url' => 'http://lightcast.nimasystems.com/plugins/sample_plugin/readme',
                    'title' => 'README'
                ),
                array(
                    'url' => 'http://lightcast.nimasystems.com/plugins/sample_plugin/install',
                    'title' => 'INSTALL'
                ),
            )
        );
    }

    /**
     * @return string
     */
    public function getPluginCategory()
    {
        // subclassers may override this method to return the plugin's logical
        // category
        return null;
    }

    /**
     * @return string
     * @deprecated use getIdentifier()
     */
    public function getPluginIdentifier()
    {
        // subclassers may override this method to return the GUID identifier of
        // the plugin
        return $this->getIdentifier();
    }

    public function getIdentifier()
    {
        throw new lcNotImplemented($this->t('Plugin must define a correct unique identifier'));
    }

    private $is_lc15_targeting;
    private $_is_lc15_targeting_checked;

    public function isTargetingLC15()
    {
        if (!$this->_is_lc15_targeting_checked) {
            $target_version = $this->getTargetFrameworkVersion();

            if ($target_version) {
                $this->is_lc15_targeting = version_compare($target_version, '1.5', '>=');
            }

            $this->_is_lc15_targeting_checked = true;
        }

        return $this->is_lc15_targeting;
    }

    public function getTargetFrameworkVersion()
    {
        return null;
    }

    public function getMinimumFrameworkVersion()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->getMajorVersion() . '.' . $this->getMinorVersion() . '.' . $this->getBuildVersion();
    }

    /**
     * @return int
     */
    public function getMajorVersion()
    {
        // subclassers may override this method to return the major version of
        // the plugin
        return 1;
    }

    /**
     * @return int
     */
    public function getMinorVersion()
    {
        // subclassers may override this method to return the minor version of
        // the plugin
        return 0;
    }

    /**
     * @return int
     */
    public function getBuildVersion()
    {
        // subclassers may override this method
        return 0;
    }

    /**
     * @return string
     */
    public function getStabilityCode()
    {
        // subclassers may override this method
        return iSupportsVersions::STABILITY_CODE_PRODUCTION;
    }

    /**
     * @return bool|null
     */
    public function getImplements()
    {
        // subclassers may override this method to return custom class names which the plugin implements
    }

    /**
     * @param array|string $interface_name
     * @return bool
     */
    public function testIfImplements($interface_name)
    {
        if (!is_array($interface_name)) {
            return ($this instanceof $interface_name || in_array($interface_name, (array)$this->getImplements()));
        } else {
            $implements_all = true;

            foreach ($interface_name as $class_name) {
                $implements_all = ($this instanceof $class_name || in_array($class_name, (array)$this->getImplements()));

                if (!$implements_all) {
                    break;
                }

                unset($class_name);
            }

            return $implements_all;
        }
    }

    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->getRootDir() . DS . 'config';
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->root_dir;
    }

    public function setRootDir($root_dir)
    {
        $this->root_dir = $root_dir;
        return $this;
    }

    /**
     * @return string
     */
    public function getProjectConfigDir()
    {
        $plugin_name = $this->getName();

        if (!$plugin_name) {
            return null;
        }

        return 'plugins' . DS . $plugin_name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getConfigHandleMap()
    {
        // maps the configuration values to handlers
        $config_map = array(
            array(
                'handler' => 'plugin',
                'config_key' => 'settings',
                'defaults' => $this->getDefaultConfiguration()
            ),
            array(
                'handler' => 'plugin_routing',
                'config_key' => 'routing',
                'defaults' => $this->getDefaultRoutingConfiguration()
            ),
            array(
                'handler' => 'plugin_view',
                'config_key' => 'view',
                'defaults' => $this->getDefaultViewConfiguration()
            ),
        );

        return $config_map;
    }

    /**
     * @return array|null
     */
    public function getDefaultConfiguration()
    {
        // subclassers may override this method to return a default configuration
        // which
        // should be applied upon initialization
        return null;
    }

    /**
     * @return array|null
     */
    public function getDefaultRoutingConfiguration()
    {
        // subclassers may override this method to return a different routing
        // configuration
        return null;
    }

    /**
     * @return array|null
     */
    public function getDefaultViewConfiguration()
    {
        // subclassers may override this method to return a different view
        // configuration
        return null;
    }

    public function getSupportedLocales()
    {
        return array('en_US');
    }

    /**
     * @return iDatabaseMigrationsSchema
     */
    public function getDatabaseMigrationsSchema()
    {
        return $this->getDefaultDbMigrationsSchemaInstance();
    }

    private function getDefaultDbMigrationsSchemaInstance()
    {
        $class_name = lcInflector::camelize($this->name . '_plugin_database_migrations_schema');

        $obj = null;

        if (!class_exists($class_name)) {
            $filename = $this->getConfigDir() . DS . self::DB_MIGRATIONS_FILENAME;

            if (file_exists($filename)) {
                include_once($filename);
            }
        }

        if (class_exists($class_name)) {
            $obj = new $class_name();

            if ($obj instanceof iDatabaseMigrationsSchema) {

                if ($obj instanceof lcSysObj) {
                    $obj->setContextName($this->getName());
                    $obj->setContextType(lcSysObj::CONTEXT_PLUGIN);
                }

                return $obj;
            }
        }

        return null;
    }
}