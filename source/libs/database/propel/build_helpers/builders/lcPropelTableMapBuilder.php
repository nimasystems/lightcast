<?php

class lcPropelTableMapBuilder extends PHP5TableMapBuilder
{
    const LC_TABLE_MAP_CLASS_NAME = 'lcTableMap';

    /*
     * Override this method so we are able to define a new common location for
     * OM/MAP files
     * if enabled - so they are not written to the locations where the actual
     * model file is set.
     * This is necessary for plugins - so their package remains intact and
     * read-only.
     */
    public function getClassFilePath()
    {
        $overriden_path = lcPropelBaseObjectBuilder::getOverridenClassFilePath('map', $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path)
        {
            return $overriden_path;
        }
        else
        {
            return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
        }
    }

    /**
     * Overriden method - to allow setting a custom TableMap parent class
     * Adds class phpdoc comment and openning of class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $script .= "

/**
 * This class defines the structure of the '" . $table->getName() . "' table.
 *
 *";
        if ($this->getBuildProperty('addTimeStamp'))
        {
            $now = strftime('%c');
            $script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
	 *
	 * $now
	 *";
        }
        $script .= "
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 * @package    propel.generator." . $this->getPackage() . "
 */
class " . $this->getClassname() . " extends " . self::LC_TABLE_MAP_CLASS_NAME . "
{
";
    }

    /**
     * Overriden method - allows us to pass custom Lightcast based attributes
     * Adds any attributes needed for this TableMap class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addAttributes(&$script)
    {
        $script .= "
	/** the context type in which the model is located (Lightcast customization) */
	protected \$lc_context_type = '" . addslashes($this->getTable()->getAttribute(lcPropelBasePeerBuilder::LC_DB_CONTEXT_TYPE_ATTR)) . "';

	/** the context name in which the model is located (Lightcast customization) */
	protected \$lc_context_name = '" . addslashes($this->getTable()->getAttribute(lcPropelBasePeerBuilder::LC_DB_CONTEXT_NAME_ATTR)) . "';
	";

        parent::addAttributes($script);
    }

    protected function addBuildRelations(&$script)
    {
        $all_relations = $this->buildCustomRelations();

        $script .= "
    /**
     * All table relations
     */
    public static function getAllRelations()
    {
	" . ($all_relations ? " return array('" . implode('\', \'', $all_relations) . "');" : null) . "
	}
";

        parent::addBuildRelations($script);
    }

    protected function addClassBody(&$script)
    {
        parent::addClassBody($script);

        // define the foreign tables
        $fkt = $this->getTable()->getForeignTableNames();

        $script .= "
    /**
     * Foreign key related tables
     */
    public static function getForeignKeyRelations()
    {
	" . ($fkt ? " return array('" . implode('\', \'', $fkt) . "');" : null) . "
	}
";
    }

    /**
     * Overriden method - to add customizations to columns
     * Adds the addInitialize() method to the  table map class.
     * @param string &$script The script will be modified in this method.
     */
    protected function addInitialize(&$script)
    {
        $table = $this->getTable();
        $platform = $this->getPlatform();

        $script .= "
    /**
     * Initialize the table attributes, columns and validators
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        \$this->setName('" . $table->getName() . "');
        \$this->setPhpName('" . $table->getPhpName() . "');
        \$this->setClassname('" . addslashes($this->getStubObjectBuilder()->getFullyQualifiedClassname()) . "');
        \$this->setPackage('" . parent::getPackage() . "');";

        // if title is not set - fake it
        $lc_title = $this->getTable()->getAttribute(lcPropelBasePeerBuilder::LC_TITLE_ATTR);
        $lc_title = $lc_title ? $lc_title : lcInflector::humanize($this->getTable()->getAttribute('phpName'));

        $php_name_title_pl = lcInflector::humanize($this->getTable()->getAttribute('phpName'));
        $last_char = substr($php_name_title_pl, strlen($php_name_title_pl) - 1, strlen($php_name_title_pl));
        $php_name_title_pl = ($last_char == 's' || $last_char == 'z') ? $php_name_title_pl . 'es' : $php_name_title_pl . 's';

        $lc_title_plural = $this->getTable()->getAttribute(lcPropelBasePeerBuilder::LC_TITLE_PLURAL_ATTR);
        $lc_title_plural = $lc_title_plural ? $lc_title_plural : $php_name_title_pl;

        $script .= "
        \$this->setLcTitle(\$this->translate('" . $lc_title . "'));";
        $script .= "
        \$this->setLcTitlePlural(\$this->translate('" . $lc_title_plural . "'));";

        if ($table->getIdMethod() == "native")
        {
            $script .= "
        \$this->setUseIdGenerator(true);";
        }
        else
        {
            $script .= "
        \$this->setUseIdGenerator(false);";
        }

        if ($table->getIdMethodParameters())
        {
            $params = $table->getIdMethodParameters();
            $imp = $params[0];
            $script .= "
        \$this->setPrimaryKeyMethodInfo('" . $imp->getValue() . "');";
        }
        elseif ($table->getIdMethod() == IDMethod::NATIVE && ($platform->getNativeIdMethod() == PropelPlatformInterface::SEQUENCE || $platform->getNativeIdMethod() == PropelPlatformInterface::SERIAL))
        {
            $script .= "
        \$this->setPrimaryKeyMethodInfo('" . $platform->getSequenceName($table) . "');";
        }

        if ($this->getTable()->getChildrenColumn())
        {
            $script .= "
        \$this->setSingleTableInheritance(true);";
        }

        if ($this->getTable()->getIsCrossRef())
        {
            $script .= "
        \$this->setIsCrossRef(true);";
        }

        // Add columns to map
        $script .= "
        // columns";
        foreach ($table->getColumns() as $col)
        {
            $cup = $col->getName();
            $cfc = $col->getPhpName();
            if (!$col->getSize())
            {
                $size = "null";
            }
            else
            {
                $size = $col->getSize();
            }
            $default = $col->getDefaultValueString();
            if ($col->isPrimaryKey())
            {
                if ($col->isForeignKey())
                {
                    foreach ($col->getForeignKeys() as $fk)
                    {
                        $script .= "
        \$this->addForeignPrimaryKey('$cup', '$cfc', '" . $col->getType() . "' , '" . $fk->getForeignTableName() . "', '" . $fk->getMappedForeignColumn($col->getName()) . "', " . ($col->isNotNull() ? 'true' : 'false') . ", " . $size . ", $default);";
                    }
                }
                else
                {
                    $script .= "
        \$this->addPrimaryKey('$cup', '$cfc', '" . $col->getType() . "', " . var_export($col->isNotNull(), true) . ", " . $size . ", $default);";
                }
            }
            else
            {
                if ($col->isForeignKey())
                {
                    foreach ($col->getForeignKeys() as $fk)
                    {
                        $script .= "
        \$this->addForeignKey('$cup', '$cfc', '" . $col->getType() . "', '" . $fk->getForeignTableName() . "', '" . $fk->getMappedForeignColumn($col->getName()) . "', " . ($col->isNotNull() ? 'true' : 'false') . ", " . $size . ", $default);";
                    }
                }
                else
                {
                    $script .= "
        \$this->addColumn('$cup', '$cfc', '" . $col->getType() . "', " . var_export($col->isNotNull(), true) . ", " . $size . ", $default);";
                }
            }// if col-is prim key
            if ($col->getValueSet())
            {
                $script .= "
        \$this->getColumn('$cup', false)->setValueSet(" . var_export($col->getValueSet(), true) . ");";
            }
            if ($col->isPrimaryString())
            {
                $script .= "
        \$this->getColumn('$cup', false)->setPrimaryString(true);";
            }
            // lightcast customization - column human readable title
            // need to add 'translate' here so the i18n parser can see this as a
            // translation!
            // in effect this way we will be doing a 'double' translate but this
            // is the only way around the issue
            // why? because table map gets initialized upon the inclusion of the
            // class itself
            // by that time the i18n system may not be live and kicking yet!

            // if title is not set - fake it
            $lc_title = $col->getAttribute('lcTitle');
            $lc_title = $lc_title ? $lc_title : lcInflector::humanize($col->getAttribute('phpName'));

            $php_name_title_pl = lcInflector::humanize($col->getAttribute('phpName'));
            $last_char = substr($php_name_title_pl, strlen($php_name_title_pl) - 1, strlen($php_name_title_pl));
            $php_name_title_pl = ($last_char == 's' || $last_char == 'z') ? $php_name_title_pl . 'es' : $php_name_title_pl . 's';

            $lc_title_plural = $col->getAttribute('lcTitlePlural');
            $lc_title_plural = $lc_title_plural ? $lc_title_plural : $php_name_title_pl;

            $script .= "
       \$this->getColumn('$cup', false)->setLcTitle(\$this->translate('" . $lc_title . "'));";

        }// foreach

        // validators
        $script .= "
        // validators";
        foreach ($table->getValidators() as $val)
        {
            $col = $val->getColumn();
            $cup = $col->getName();
            foreach ($val->getRules() as $rule)
            {
                if ($val->getTranslate() !== Validator::TRANSLATE_NONE)
                {
                    $script .= "
        \$this->addValidator('$cup', '" . $rule->getName() . "', '" . $rule->getClass() . "', '" . str_replace("'", "\'", $rule->getValue()) . "', " . $val->getTranslate() . "('" . str_replace("'", "\'", $rule->getMessage()) . "'));";
                }
                else
                {
                    $script .= "
        \$this->addValidator('$cup', '" . $rule->getName() . "', '" . $rule->getClass() . "', '" . str_replace("'", "\'", $rule->getValue()) . "', '" . str_replace("'", "\'", $rule->getMessage()) . "');";
                } // if ($rule->getTranslation() ...
            } // foreach rule
        }// foreach validator

        $script .= "
    } // initialize()
";

    }

    protected function buildCustomRelations()
    {
        $relations = array();

        foreach ($this->getTable()->getForeignKeys() as $fkey)
        {
            $relations[] = addslashes($fkey->getTableName());
            unset($fkey);
        }

        foreach ($this->getTable()->getReferrers() as $fkey)
        {
            $relations[] = addslashes($fkey->getTableName());
            unset($fkey);
        }

        foreach ($this->getTable()->getCrossFks() as $fkList)
        {
            list($refFK, $crossFK) = $fkList;
            $relations[] = addslashes($crossFK->getTableName());
            unset($refFK, $crossFK, $fkList);
        }

        return array_unique($relations);
    }

}
?>