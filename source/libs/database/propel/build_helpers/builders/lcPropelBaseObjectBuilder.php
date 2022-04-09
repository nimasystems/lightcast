<?php

class lcPropelBaseObjectBuilder extends PHP5ObjectBuilder
{
    public static $disable_path_override;

    public function getClassFilePath()
    {
        $overriden_path = self::getOverridenClassFilePath('om', $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path) {
            return $overriden_path;
        } else {
            return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
        }
    }

    protected function addClassBody(&$script)
    {
        parent::addClassBody($script);

        $this->addInserOrUpdate($script);
    }

    /**
     * Adds the insertOrUpdate() method.
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addInserOrUpdate(&$script)
    {
        $this->addInserOrUpdateComment($script);
        $this->addInserOrUpdateOpen($script);
        $this->addInserOrUpdateBody($script);
        $this->addInserOrUpdateClose($script);
    }

    /**
     * Adds the comment for the insertOrUpdate method
     *
     * @param string &$script The script will be modified in this method.
     *
     **/
    protected function addInserOrUpdateComment(&$script)
    {
        $script .= "
    /**
     * Persists this object to the database (INSERT ON DUPLICATE KEY UPDATE)
     *
     * @param PropelPDO \$con
     * @return " . $this->getClassname() . "
     */";
    }

    /**
     * Adds the function declaration for the insertOrUpdate method
     *
     * @param string &$script The script will be modified in this method.
     *
     **/
    protected function addInserOrUpdateOpen(&$script)
    {
        $script .= "
    public function insertOrUpdate(PropelPDO \$con = null)
    {";
    }

    /**
     * Adds the function body for the insertOrUpdate method
     *
     * @param string &$script The script will be modified in this method.
     *
     **/
    protected function addInserOrUpdateBody(&$script)
    {
        $peer_name = $this->getPeerClassname();

        $script .= "
        if (\$this->isDeleted()) {
            throw new PropelException(\"You cannot save an object that has been deleted.\");
        }

        if (\$con === null) {
            \$con = Propel::getConnection(" . $peer_name . "::DATABASE_NAME, Propel::CONNECTION_WRITE);
        }";

        $script .= "
        
        \$modified_cols = \$this->getModifiedColumns();

        if (!\$modified_cols) {
            return \$this;
        }

        \$table_map = " . $peer_name . "::getTableMap();
        \$table_name = \$table_map->getName();

        /** @var lcColumnMap[] \$pks */
        \$pks = \$table_map->getPrimaryKeys();

        \$pks_names = [];
        \$pk_up_name = '';
        \$has_one_ok = count(\$pks) == 1;

        foreach (\$pks as \$pk) {
            \$col_name = \$pk->getName();

            if (!\$pk_up_name) {
                \$pk_up_name = \$col_name;
            }
            
            \$col_name = " . $peer_name . "::translateFieldName(\$col_name, BasePeer::TYPE_FIELDNAME, BasePeer::TYPE_PHPNAME);
            \$pks_names[] = \$col_name;

            unset(\$pk);
        }

        \$qcols = [];
        \$qparams = [];
        \$qup = [];
        \$col_types = [];
        \$qvals = [];

        \$param_prefix = ':p';
        
        \$i = 0;
        foreach (\$pks_names as \$col_name) {
            \$col_name1 = " . $peer_name . "::translateFieldName(\$col_name,
                BasePeer::TYPE_PHPNAME, BasePeer::TYPE_FIELDNAME);
            \$col = \$table_map->getColumn(\$col_name1, false);
            \$fqdn_name = \$col->getFullyQualifiedName();
            \$pdo_type = \$col->getPdoType();
            \$qcoln = '`' . \$col_name1 . '`';
            \$qcols[] = \$qcoln;
            \$qparams[] = \$param_prefix . \$i;
            \$qvals[] = \$this->getByName(\$col_name);
            \$col_types[] = \$pdo_type;

            \$i++;
            unset(\$col_name, \$fqdn_name, \$pdo_type, \$col);
        }

        \$vcols_added = false;
        foreach (\$modified_cols as \$original_col_name) {
            \$col_name2 = " . $peer_name . "::translateFieldName(\$original_col_name, BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME);

            \$col = \$table_map->getColumn(\$col_name2, false);
            \$pdo_type = \$col->getPdoType();

            \$qcoln = '`' . \$col_name2 . '`';
            
            \$col_name = " . $peer_name . "::translateFieldName(\$original_col_name, BasePeer::TYPE_COLNAME, BasePeer::TYPE_PHPNAME);
            
            if (in_array(\$col_name, \$pks_names)) {
                continue;
            }
            
            \$qcols[] = \$qcoln;

            \$qparams[] = \$param_prefix . \$i;
            \$qvals[] = \$this->getByName(\$col_name);
            \$col_types[] = \$pdo_type;

            \$qup[] = \$qcoln . ' = ' . \$param_prefix . \$i;
            \$qvals[] = \$this->getByName(\$col_name);
            \$col_types[] = \$pdo_type;
            \$vcols_added = true;

            unset(\$original_col_name, \$col_name2, \$col, \$pdo_type, \$qcoln, \$col_name);
            \$i++;
        }

        if (count(\$qcols) < 1) {
            return \$this;
        }
        
        // if \$vcols_added = false it means all columns are KEYS and we should do a REPLACE

        \$q = sprintf((\$vcols_added ? 'INSERT' : 'REPLACE') . ' INTO `%s` (%s) VALUES(%s)' .
            (\$vcols_added ? ' ON DUPLICATE KEY UPDATE ' : '').
            (\$has_one_ok ? '`' . \$pk_up_name . '` = LAST_INSERT_ID(`' . \$pk_up_name . '`), ' : '') . '%s',
            \$table_name,
            implode(',', \$qcols),
            implode(',', \$qparams),
            implode(',', \$qup)
        );
        \$stmt = \$con->prepare(\$q);

        for (\$i = 0; \$i <= count(\$qvals) - 1; \$i++) {
            \$stmt->bindValue(\$param_prefix . \$i, \$qvals[\$i], \$col_types[\$i]);
            unset(\$col_type);
        }

        \$stmt->execute();

        if (\$has_one_ok) {
            \$pk = \$this->getPrimaryKey();

            if (!\$pk) {
                \$pk = \$con->lastInsertId();

                if (\$pk) {
                    \$this->setPrimaryKey(\$pk);
                }
            }

            \$this->resetModified();
            \$this->setNew(false);
            \$this->reload();
        }

        if (Propel::isInstancePoolingEnabled()) {
            " . $peer_name . "::addInstanceToPool(\$this);
        }

        return \$this;";
    }

    /**
     * Adds the function close for the insertOrUpdate method
     *
     * @param string &$script The script will be modified in this method.
     *
     **/
    protected function addInserOrUpdateClose(&$script)
    {
        $script .= "
    }
";
    }

    /*
     * Override this method so we are able to define a new common location for
     * OM/MAP files
     * if enabled - so they are not written to the locations where the actual
     * model file is set.
     * This is necessary for plugins - so their package remains intact and
     * read-only.
     */

    public static function getOverridenClassFilePath($prefix, GeneratorConfig $generator_config, $class_name)
    {
        if (self::$disable_path_override) {
            return null;
        }

        $path = $generator_config->getBuildProperty('lightcastBuildPath');
        $enable_custom_path = $generator_config->getBuildProperty('lightcastOverrideBuildPath');

        if ($enable_custom_path && $path) {
            $new_path = $path;
            $new_path = lcStrings::endsWith($new_path, DS) ? $new_path : $new_path . DS;
            $new_path .= $prefix . DS . $class_name . '.php';
            return $new_path;
        }

        return null;
    }

    /**
     * Adds a setter method for date/time/timestamp columns.
     *
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     *
     * @see        parent::addColumnMutators()
     */
    protected function addTemporalMutator(&$script, Column $col)
    {
        //$cfc = $col->getPhpName();
        $clo = strtolower($col->getName());
        //$visibility = $col->getMutatorVisibility();

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = 'DateTime';
        }
        $this->declareClasses($dateTimeClass, 'DateTimeZone', 'PropelDateTime');

        $this->addTemporalMutatorComment($script, $col);
        $this->addMutatorOpenOpen($script, $col);
        $this->addMutatorOpenBody($script, $col);

        $fmt = var_export($this->getTemporalFormatter($col), true);

        $script .= "
        \$dt = lcPropelDateTime::newInstance(\$v, null, '$dateTimeClass');
        if (\$this->$clo !== null || \$dt !== null) {
            \$currentDateAsString = (\$this->$clo !== null && \$tmpDt = new $dateTimeClass(\$this->$clo)) ? \$tmpDt->format($fmt) : null;
            \$newDateAsString = \$dt ? \$dt->format($fmt) : null;";

        if (($def = $col->getDefaultValue()) !== null && !$def->isExpression()) {
            $defaultValue = $this->getDefaultValueString($col);
            $script .= "
            if ( (\$currentDateAsString !== \$newDateAsString) // normalized values don't match
                || (\$dt->format($fmt) === $defaultValue) // or the entered value matches the default
                 ) {";
        } else {
            $script .= "
            if (\$currentDateAsString !== \$newDateAsString) {";
        }

        $script .= "
                \$this->$clo = \$newDateAsString;
                \$this->modifiedColumns[] = " . $this->getColumnConstant($col) . ";
            }
        } // if either are not null
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds setter method for "normal" columns.
     *
     * This is a fix for converting decimals to string while on a locale different than C (floating point value gets lost)
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     *
     * @see        parent::addColumnMutators()
     */
    protected function addDefaultMutator(&$script, Column $col)
    {
        $clo = strtolower($col->getName());

        $this->addMutatorOpen($script, $col);

        // Perform type-casting to ensure that we can use type-sensitive
        // checking in mutators.
        if ($col->isPhpPrimitiveType()) {
            $script .= "
        if (\$v !== null && is_numeric(\$v)) {
            \$prev_locale = setlocale(LC_NUMERIC, 0);
            setlocale(LC_NUMERIC, 'C');
            \$v = (" . $col->getPhpType() . ") \$v;
            setlocale(LC_NUMERIC, \$prev_locale);
        }
";
        }

        $script .= "
        if (\$this->$clo !== \$v) {
            \$this->$clo = \$v;
            \$this->modifiedColumns[] = " . $this->getColumnConstant($col) . ";
        }
";
        $this->addMutatorClose($script, $col);
    }

    /**
     * Adds the comment for a temporal accessor
     *
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     *
     * @see        addTemporalAccessor
     **/
    public function addTemporalAccessorComment(&$script, Column $col)
    {
        $clo = strtolower($col->getName());
        $useDateTime = $this->getBuildProperty('useDateTimeClass');

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = 'DateTime';
        }
        $mysqlInvalidDateString = '';
        $handleMysqlDate = false;
        if ($this->getPlatform() instanceof MysqlPlatform) {
            if ($col->getType() === PropelTypes::TIMESTAMP) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00 00:00:00';
            } else if ($col->getType() === PropelTypes::DATE) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00';
            }
            // 00:00:00 is a valid time, so no need to check for that.
        }

        $script .= "
    /**
     * Get the [optionally formatted] temporal [$clo] column value.
     * " . $col->getDescription();
        if (!$useDateTime) {
            $script .= "
     * This accessor only only work with unix epoch dates.  Consider enabling the propel.useDateTimeClass
     * option in order to avoid conversions to integers (which are limited in the dates they can express).";
        }
        $script .= "
     *
     * @param string \$format The date/time format string (either date()-style or strftime()-style).
     *				 If format is null, then the raw " . ($useDateTime ? 'DateTime object' : 'unix timestamp integer') . " will be returned.
     * @param DateTimeZone \$timezone Optional timezone.";
        if ($useDateTime) {
            $script .= "
     * @return mixed Formatted date/time value as string or $dateTimeClass object (if format is null), null if column is null" . ($handleMysqlDate ? ', and 0 if column value is ' . $mysqlInvalidDateString : '');
        } else {
            $script .= "
     * @return mixed Formatted date/time value as string or (integer) unix timestamp (if format is null), null if column is null" . ($handleMysqlDate ? ', and 0 if column value is ' . $mysqlInvalidDateString : '');
        }
        $script .= "
     * @throws PropelException - if unable to parse/validate the date/time value.
     */";
    }

    /**
     * Adds the function declaration for a temporal accessor
     *
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     *
     * @see        addTemporalAccessor
     **/
    public function addTemporalAccessorOpen(&$script, Column $col)
    {
        $cfc = $col->getPhpName();

        $defaultfmt = null;
        $visibility = $col->getAccessorVisibility();

        // Default date/time formatter strings are specified in build.properties
        if ($col->getType() === PropelTypes::DATE) {
            $defaultfmt = $this->getBuildProperty('defaultDateFormat');
        } else if ($col->getType() === PropelTypes::TIME) {
            $defaultfmt = $this->getBuildProperty('defaultTimeFormat');
        } else if ($col->getType() === PropelTypes::TIMESTAMP) {
            $defaultfmt = $this->getBuildProperty('defaultTimeStampFormat');
        }

        if (empty($defaultfmt)) {
            $defaultfmt = 'null';
        } else {
            $defaultfmt = var_export($defaultfmt, true);
        }

        $script .= "
    " . $visibility . " function get$cfc(\$format = " . $defaultfmt . "";
        if ($col->isLazyLoad()) {
            $script .= ", \$con = null";
        }
        $script .= ", DateTimeZone \$timezone = null)
    {";
    } // addPKRefFKGet()

    /**
     * Adds the body of the temporal accessor
     *
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     *
     * @see        addTemporalAccessor
     **/
    protected function addTemporalAccessorBody(&$script, Column $col)
    {
        $clo = strtolower($col->getName());

        $useDateTime = $this->getBuildProperty('useDateTimeClass');

        $dateTimeClass = $this->getBuildProperty('dateTimeClass');
        if (!$dateTimeClass) {
            $dateTimeClass = 'DateTime';
        }
        $this->declareClasses($dateTimeClass);
        $defaultfmt = null;

        // Default date/time formatter strings are specified in build.properties
        if ($col->getType() === PropelTypes::DATE) {
            $defaultfmt = $this->getBuildProperty('defaultDateFormat');
        } else if ($col->getType() === PropelTypes::TIME) {
            $defaultfmt = $this->getBuildProperty('defaultTimeFormat');
        } else if ($col->getType() === PropelTypes::TIMESTAMP) {
            $defaultfmt = $this->getBuildProperty('defaultTimeStampFormat');
        }

        if (empty($defaultfmt)) {
            $defaultfmt = null;
        }

        $mysqlInvalidDateString = '';
        $handleMysqlDate = false;
        if ($this->getPlatform() instanceof MysqlPlatform) {
            if ($col->getType() === PropelTypes::TIMESTAMP) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00 00:00:00';
            } else if ($col->getType() === PropelTypes::DATE) {
                $handleMysqlDate = true;
                $mysqlInvalidDateString = '0000-00-00';
            }
            // 00:00:00 is a valid time, so no need to check for that.
        }

        if ($col->isLazyLoad()) {
            $script .= $this->getAccessorLazyLoadSnippet($col);
        }

        $script .= "
        if (\$this->$clo === null) {
            return null;
        }
";
        if ($handleMysqlDate) {
            $script .= "
        if (\$this->$clo === '$mysqlInvalidDateString') {
            // while technically this is not a default value of null,
            // this seems to be closest in meaning.
            return null;
        }

        try {
            \$dt = \$this->getDateTimeWithClientTimezone(new $dateTimeClass(\$this->$clo));
            
            if (\$timezone) {
                \$dt->setTimezone(\$timezone);
            }
            
        } catch (Exception \$x) {
            throw new PropelException(\"Internally stored date/time/timestamp value could not be converted to $dateTimeClass: \" . var_export(\$this->$clo, true), \$x);
        }
";
        } else {
            $script .= "

        try {
            \$dt = \$this->getDateTimeWithClientTimezone(new $dateTimeClass(\$this->$clo));
        } catch (Exception \$x) {
            throw new PropelException(\"Internally stored date/time/timestamp value could not be converted to $dateTimeClass: \" . var_export(\$this->$clo, true), \$x);
        }
";
        } // if handleMyqlDate

        $script .= "
        if (\$format === null) {";
        if ($useDateTime) {
            $script .= "
            // Because propel.useDateTimeClass is true, we return a $dateTimeClass object.
            return \$dt;";
        } else {
            $script .= "
            // We cast here to maintain BC in API; obviously we will lose data if we're dealing with pre-/post-epoch dates.
            return (int) \$dt->format('U');";
        }
        $script .= "
        }

        if (strpos(\$format, '%') !== false) {
            return strftime(\$format, \$dt->format('U'));
        }

        return \$dt->format(\$format);
        ";
    } // addPKRefFKSet
}
