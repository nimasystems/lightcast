<?php
declare(strict_types=1);

class lcPropelBasePeerBuilder extends PHP5PeerBuilder
{
    public const LC_TITLE_ATTR = 'lcTitle';
    public const LC_TITLE_PLURAL_ATTR = 'lcTitlePlural';
    public const LC_DB_CONTEXT_TYPE_ATTR = 'lcContextType';
    public const LC_DB_CONTEXT_NAME_ATTR = 'lcContextName';

    /*
     * Override this method so we are able to define a new common location for
     * OM/MAP files
     * if enabled - so they are not written to the locations where the actual
     * model file is set.
     * This is necessary for plugins - so their package remains intact and
     * read-only.
     */
    public function getClassFilePath(): string
    {
        $overriden_path = lcPropelBaseObjectBuilder::getOverridenClassFilePath(
            $this->getGeneratorConfig()->getBuildProperty('namespaceOm'),
            $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path) {
            return $overriden_path;
        } else {
            return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
        }
    }

    /**
     * Shortcut method to return the [stub] peer classname for current table.
     * This is the classname that is used whenever object or peer classes want
     * to invoke methods of the peer classes.
     *
     * @return string (e.g. 'BaseMyPeer')
     * @see        StubPeerBuilder::getClassname()
     */
    public function getPeerClassname()
    {
        return $this->getPeerBuilder()->getClassname();
    }

    public function getNamespace(): string
    {
        return 'Gen\\Propel\\Models\\Om';
    }

    public function getTableMapClass(): string
    {
        return $this->getStubObjectBuilder()->getTableMapNamespace() . '\\' . $this->getObjectClassname() . 'TableMap';

//        // Trim first backslash for php 5.3.{0,1,2} compatibility
//        $fullyQualifiedClassname = ltrim($this->getStubObjectBuilder()->getFullyQualifiedClassname(), '\\');
//
//        if (($pos = strrpos($fullyQualifiedClassname, '\\')) !== false) {
//            return substr_replace($fullyQualifiedClassname, '\\Map\\', $pos, 1) . 'TableMap';
//        } else {
//            return $fullyQualifiedClassname . 'TableMap';
//        }
    }

    /**
     * @param $builder
     * @return void
     */
    public function declareClassFromBuilder($builder)
    {
        //$this->declareClassNamespace('Base' . $builder->getClassname(), $builder->getNamespace());
    }

    /**
     * Adds the valueSet constants for ENUM columns.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addEnumColumnConstants(&$script)
    {
        foreach ($this->getTable()->getColumns() as $col) {
            if ($col->isEnumType() || $col->getValueSet()) {
                $script .= '
    /** The enumerated values for the ' . $col->getName() . ' field */';
                foreach ($col->getValueSet() as $value) {
                    $script .= '
    const ' . $this->getColumnName($col) . '_' . $this->getEnumValueConstant($value) . " = '" . $value . "';";
                }
                $script .= '
';
            }
        }
    }

    /**
     * Returns the object classname for current table.
     * This is the classname that is used whenever object or peer classes want
     * to invoke methods of the object classes.
     *
     * @return string (e.g. 'My')
     * @see        StubPeerBuilder::getClassname()
     */
    public function getObjectClassname()
    {
        return $this->getObjectBuilder()->getClassname();
    }

    /**
     * Shortcut method to return the [stub] query classname for current table.
     * This is the classname that is used whenever object or peer classes want
     * to invoke methods of the query classes.
     *
     * @return string (e.g. 'Myquery')
     * @see        StubQueryBuilder::getClassname()
     */
    public function getQueryClassname()
    {
        return $this->getQueryBuilder()->getClassname();
    }

    /**
     * Adds the getValueSetsFormatted() method.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetValueSets(&$script)
    {
        parent::addGetValueSets($script);

        $d = [];

        foreach ($this->getTable()->getColumns() as $col) {
            $tstr = null;

            if ($col->isEnumType() || $col->getValueSet()) {
                $tstr .= '
        self::' . $this->getColumnName($col) . ' => array(
        ';
                $arg = [];

                foreach ($col->getValueSet() as $value) {
                    $arg[] = '        self::' . $this->getColumnName($col) . '_' . $this->getEnumValueConstant($value) . ' => $tableMap->translate(\'' .
                        ucfirst(lcInflector::subcamelize($value)) . '\')';
                }
                $tstr .= implode(', ' . "\n", $arg) . '
        )';
                $d[] = $tstr;
            }
        }

        if ($d) {
            $script .= "
    /**
     * Gets the list of translated titles for all ENUM columns
     * @return array
     */
    public static function getValueSetsFormatted()
    {
        /** @var \lcTableMap \$tableMap */
        \$tableMap = self::getTableMap();

        return array(" . implode(",\n", $d) . ');
    }
';
        }

    }

    /**
     * Adds the getValueSet() method.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetValueSet(&$script)
    {
        parent::addGetValueSet($script);

        $script .= "
    /**
     * Gets the list of values for an ENUM column with their corresonding translated titles
     *
     * @param string \$colname The ENUM column name.
     *
     * @return array list of possible values and titles for the column
     */
    public static function getValueSetFormattedMap(\$colname)
    {
        \$valueSets = " . $this->getPeerClassname() . "::getValueSets();

        if (!isset(\$valueSets[\$colname])) {
            throw new PropelException(sprintf('Column \"%s\" has no ValueSet.', \$colname));
        }

        \$valueSetsFormatted = " . $this->getPeerClassname() . "::getValueSetsFormatted();

        return array_combine(\$valueSets[\$colname], \$valueSetsFormatted[\$colname]);
    }
";
    }

    /*
     * Overriden method to allow adding custom XML attributes for Lightcast
     */
    protected function addConstantsAndAttributes(&$script)
    {
        // if title is not set - fake it
        $lc_title = $this->getTable()->getAttribute(self::LC_TITLE_ATTR);
        $lc_title = $lc_title ?: lcInflector::humanize($this->getTable()->getAttribute('phpName'));

        $php_name_title_pl = lcInflector::humanize($this->getTable()->getAttribute('phpName'));
        $last_char = substr($php_name_title_pl, strlen($php_name_title_pl) - 1, strlen($php_name_title_pl));
        $php_name_title_pl = ($last_char == 's' || $last_char == 'z') ? $php_name_title_pl . 'es' : $php_name_title_pl . 's';

        $lc_title_plural = $this->getTable()->getAttribute(self::LC_TITLE_PLURAL_ATTR);
        $lc_title_plural = $lc_title_plural ?: $php_name_title_pl;

        $tablePhpActualName = addslashes($this->getStubObjectBuilder()->getFullyQualifiedActualClassname());

        $script .= "
	/** the context type in which the model is located (Lightcast customization) */
	const LC_CONTEXT_TYPE = '" . addslashes($this->getTable()->getAttribute(self::LC_DB_CONTEXT_TYPE_ATTR)) . "';

	/** the context name in which the model is located (Lightcast customization) */
	const LC_CONTEXT_NAME = '" . addslashes($this->getTable()->getAttribute(self::LC_DB_CONTEXT_NAME_ATTR)) . "';

	/** the localized table name - singular (Lightcast customization) */
	const LC_TITLE = '" . addslashes($lc_title) . "';

	/** the localized table name - plural (Lightcast customization) */
	const LC_TITLE_PLURAL = '" . addslashes($lc_title_plural) . "';

    /** the related Propel class for this table */
    const OM_ACTUAL_CLASS = '$tablePhpActualName';

	";

        parent::addConstantsAndAttributes($script);
    }

    /**
     * Adds the populateObject() method.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addPopulateObject(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param      array \$row PropelPDO resultset row.
     * @param      int \$startcol The 0-based offset for reading from the resultset row.
     * @throws PropelException Any exceptions caught during processing will be
     *		 rethrown wrapped into a PropelException.
     * @return array (" . $this->getStubObjectBuilder()->getFullyQualifiedActualClassname() . " object, last column rank)
     */
    public static function populateObject(\$row, \$startcol = 0)
    {
        \$key = " . $this->getPeerClassname() . "::getPrimaryKeyHashFromRow(\$row, \$startcol);
        if (null !== (\$obj = " . $this->getPeerClassname() . "::getInstanceFromPool(\$key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // \$obj->hydrate(\$row, \$startcol, true); // rehydrate
            \$col = \$startcol + " . $this->getPeerClassname() . "::NUM_HYDRATE_COLUMNS;";
        if ($table->isAbstract()) {
            $script .= "
        } elseif (null == \$key) {
            // empty resultset, probably from a left join
            // since this table is abstract, we can't hydrate an empty object
            \$obj = null;
            \$col = \$startcol + " . $this->getPeerClassname() . "::NUM_HYDRATE_COLUMNS;";
        }
        $script .= "
        } else {";
        if (!$table->getChildrenColumn()) {
            $script .= "
            \$cls = " . $this->getPeerClassname() . "::OM_ACTUAL_CLASS;";
        } else {
            $script .= "
            \$cls = " . $this->getPeerClassname() . "::getOMActualClass(\$row, \$startcol);";
        }
        $script .= "
            \$obj = new \$cls();
            \$col = \$obj->hydrate(\$row, \$startcol);
            " . $this->getPeerClassname() . "::addInstanceToPool(\$obj, \$key);
        }

        return array(\$obj, \$col);
    }
";
    }

    /**
     * Adds a getOMClass() for non-abstract tables that do note use inheritance.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addGetOMClass_NoInheritance(&$script)
    {
        $script .= "
    /**
     * The class that the Peer will make instances of.
     *
     *
     * @return string ClassName
     */
    public static function getOMClass(\$row = 0, \$colnum = 0)
    {
        return " . $this->getPeerClassname() . "::OM_ACTUAL_CLASS;
    }
";

        $script .= "
    /**
     * The class that the Peer will make instances of.
     *
     *
     * @return string ClassName
     */
    public static function getOMActualClass(\$row = 0, \$colnum = 0)
    {
        return " . $this->getPeerClassname() . "::OM_ACTUAL_CLASS;
    }
";
    }

    /**
     * Adds the populateObjects() method.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addPopulateObjects(&$script)
    {
        $table = $this->getTable();
        $script .= "
    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *		 rethrown wrapped into a PropelException.
     */
    public static function populateObjects(PDOStatement \$stmt)
    {
        \$results = array();
    ";
        if (!$table->getChildrenColumn()) {
            $script .= "
        // set the class once to avoid overhead in the loop
        \$cls = " . $this->getPeerClassname() . "::getOMActualClass();";
        }

        $script .= "
        // populate the object(s)
        while (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {
            \$key = " . $this->getPeerClassname() . "::getPrimaryKeyHashFromRow(\$row, 0);
            if (null !== (\$obj = " . $this->getPeerClassname() . "::getInstanceFromPool(\$key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // \$obj->hydrate(\$row, 0, true); // rehydrate
                \$results[] = \$obj;
            } else {";
        if ($table->getChildrenColumn()) {
            $script .= "
                // class must be set each time from the record row
                \$cls = " . $this->getPeerClassname() . "::getOMActualClass(\$row, 0);
                \$cls = substr('.'.\$cls, strrpos('.'.\$cls, '.') + 1);
                " . $this->buildObjectInstanceCreationCode('$obj', '$cls') . "
                \$obj->hydrate(\$row);
                \$results[] = \$obj;
                " . $this->getPeerClassname() . "::addInstanceToPool(\$obj, \$key);";
        } else {
            $script .= "
                " . $this->buildObjectInstanceCreationCode('$obj', '$cls') . "
                \$obj->hydrate(\$row);
                \$results[] = \$obj;
                " . $this->getPeerClassname() . "::addInstanceToPool(\$obj, \$key);";
        }
        $script .= "
            } // if key exists
        }
        \$stmt->closeCursor();

        return \$results;
    }";
    } // addGetPrimaryKeyFromRow

    public function getPeerActualClassname()
    {
        return $this->getStubPeerBuilder()->getActualClassname();
    }

    /*
     * Overriden method to allow adding custom XML attributes for Lightcast
     */
    protected function addFieldNamesAttribute(&$script)
    {
        $table = $this->getTable();
        $tableColumns = $table->getColumns();

        //$peer_class_name = $this->getPeerClassname();
        $phpcol_name = 'phpName';
        // $peer_class_name::TYPE_PHPNAME;

        $script .= '
    /**
     * holds an array of field titles - singular (Lightcast customization)
     *
     * first dimension keys are the type constants
     * e.g. ' . $this->getPeerClassname() . "::\$fieldNames[" . $this->getPeerClassname() . "::TYPE_PHPNAME][0] = 'Id'
     */
    protected static \$lcTitles = array (
        BasePeer::TYPE_PHPNAME => array (";
        foreach ($tableColumns as $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getPhpName() . "' => '" . $lc_title . "', ";
        }
        $script .= '),
        BasePeer::TYPE_STUDLYPHPNAME => array (';
        foreach ($tableColumns as $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getStudlyPhpName() . "' => '" . $lc_title . "', ";
        }
        $script .= '),
        BasePeer::TYPE_COLNAME => array (';
        foreach ($tableColumns as $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= $this->getColumnConstant($col, $this->getPeerClassname()) . " => '" . $lc_title . "', ";
        }
        $script .= '),
        BasePeer::TYPE_RAW_COLNAME => array (';
        foreach ($tableColumns as $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getConstantColumnName() . "' => '" . $lc_title . "', ";
        }
        $script .= '),
        BasePeer::TYPE_FIELDNAME => array (';
        foreach ($tableColumns as $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getName() . "' => '" . $lc_title . "', ";
        }
        $script .= '),
        BasePeer::TYPE_NUM => array (';
        foreach ($tableColumns as $num => $col) {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= $num . " => '" . $lc_title . "', ";
        }
        $script .= ')
    );
';

        parent::addFieldNamesAttribute($script);
    }

}
