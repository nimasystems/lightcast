<?php

class lcPropelBasePeerBuilder extends PHP5PeerBuilder
{
    const LC_TITLE_ATTR = 'lcTitle';
    const LC_TITLE_PLURAL_ATTR = 'lcTitlePlural';
    const LC_DB_CONTEXT_TYPE_ATTR = 'lcContextType';
    const LC_DB_CONTEXT_NAME_ATTR = 'lcContextName';

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
        $overriden_path = lcPropelBaseObjectBuilder::getOverridenClassFilePath('om', $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path)
        {
            return $overriden_path;
        }
        else
        {
            return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
        }
    }

    /*
     * Overriden method to allow adding custom XML attributes for Lightcast
     */
    protected function addConstantsAndAttributes(&$script)
    {
        // if title is not set - fake it
        $lc_title = $this->getTable()->getAttribute(self::LC_TITLE_ATTR);
        $lc_title = $lc_title ? $lc_title : lcInflector::humanize($this->getTable()->getAttribute('phpName'));

        $php_name_title_pl = lcInflector::humanize($this->getTable()->getAttribute('phpName'));
        $last_char = substr($php_name_title_pl, strlen($php_name_title_pl) - 1, strlen($php_name_title_pl));
        $php_name_title_pl = ($last_char == 's' || $last_char == 'z') ? $php_name_title_pl . 'es' : $php_name_title_pl . 's';

        $lc_title_plural = $this->getTable()->getAttribute(self::LC_TITLE_PLURAL_ATTR);
        $lc_title_plural = $lc_title_plural ? $lc_title_plural : $php_name_title_pl;

        $script .= "
	/** the context type in which the model is located (Lightcast customization) */
	const LC_CONTEXT_TYPE = '" . addslashes($this->getDatabase()->getAttribute(self::LC_DB_CONTEXT_TYPE_ATTR)) . "';

	/** the context name in which the model is located (Lightcast customization) */
	const LC_CONTEXT_NAME = '" . addslashes($this->getDatabase()->getAttribute(self::LC_DB_CONTEXT_NAME_ATTR)) . "';

	/** the localized table name - singular (Lightcast customization) */
	const LC_TITLE = '" . addslashes($lc_title) . "';

	/** the localized table name - plural (Lightcast customization) */
	const LC_TITLE_PLURAL = '" . addslashes($lc_title_plural) . "';
	";

        parent::addConstantsAndAttributes($script);
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

        $script .= "
    /**
     * holds an array of field titles - singular (Lightcast customization)
     *
     * first dimension keys are the type constants
     * e.g. " . $this->getPeerClassname() . "::\$fieldNames[" . $this->getPeerClassname() . "::TYPE_PHPNAME][0] = 'Id'
     */
    protected static \$lcTitles = array (
        BasePeer::TYPE_PHPNAME => array (";
        foreach ($tableColumns as $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getPhpName() . "' => '" . $lc_title . "', ";
        }
        $script .= "),
        BasePeer::TYPE_STUDLYPHPNAME => array (";
        foreach ($tableColumns as $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getStudlyPhpName() . "' => '" . $lc_title . "', ";
        }
        $script .= "),
        BasePeer::TYPE_COLNAME => array (";
        foreach ($tableColumns as $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= $this->getColumnConstant($col, $this->getPeerClassname()) . " => '" . $lc_title . "', ";
        }
        $script .= "),
        BasePeer::TYPE_RAW_COLNAME => array (";
        foreach ($tableColumns as $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getConstantColumnName() . "' => '" . $lc_title . "', ";
        }
        $script .= "),
        BasePeer::TYPE_FIELDNAME => array (";
        foreach ($tableColumns as $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= "'" . $col->getName() . "' => '" . $lc_title . "', ";
        }
        $script .= "),
        BasePeer::TYPE_NUM => array (";
        foreach ($tableColumns as $num => $col)
        {
            // if title is not set - fake it
            $lc_title = $col->getAttribute(self::LC_TITLE_ATTR) ? $col->getAttribute(self::LC_TITLE_ATTR) : lcInflector::humanize($col->getAttribute($phpcol_name));
            $script .= $num . " => '" . $lc_title . "', ";
            ;
        }
        $script .= ")
    );
";

        parent::addFieldNamesAttribute($script);
    }

}
