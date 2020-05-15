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
