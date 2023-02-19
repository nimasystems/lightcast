<?php
declare(strict_types=1);

class lcPropelBaseQueryBuilder extends QueryBuilder
{
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
        $overriden_path = lcPropelBaseObjectBuilder::getOverridenClassFilePath($this->getGeneratorConfig()->getBuildProperty('namespaceOm'),
            $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path) {
            return $overriden_path;
        }

        return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
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

    /**
     * @param $builder
     * @return void
     */
    public function declareClassFromBuilder($builder)
    {
        //$this->declareClassNamespace('Base' . $builder->getClassname(), $builder->getNamespace());
    }

    protected function addClassBody(&$script)
    {
        parent::addClassBody($script);

        $this->addTranslate($script);
        $this->addDataTableJoin($script);
    }

    public function getPeerActualClassname()
    {
        return $this->getStubPeerBuilder()->getActualClassname();
    }

    public function getActualClassname()
    {
        return $this->getStubObjectBuilder()->getActualClassname();
    }

    public function getFullyQualifiedActualClassname()
    {
        return $this->getStubObjectBuilder()->getFullyQualifiedActualClassname();
    }

    /**
     * Adds the function body for the factory
     *
     * @param string &$script The script will be modified in this method.
     */
    protected function addFactoryBody(&$script)
    {
        $classname = $this->getFullyQualifiedActualClassname() . 'Query';
        $script .= "
        if (\$criteria instanceof " . $classname . ") {
            return \$criteria;
        }
        \$query = new " . $classname . "(null, null, \$modelAlias);

        if (\$criteria instanceof Criteria) {
            \$query->mergeWith(\$criteria);
        }

        return \$query;";
    }

    /**
     * Adds the comment for the factory
     *
     * @param string &$script The script will be modified in this method.
     **/
    protected function addFactoryComment(&$script)
    {
        $classname = $this->getFullyQualifiedActualClassname() . 'Query';
        $script .= "
    /**
     * Returns a new " . $classname . " object.
     *
     * @param     string \$modelAlias The alias of a model in the query
     * @param   $classname|Criteria \$criteria Optional Criteria to build the query from
     *
     * @return " . $classname . "
     */";
    }

    protected function addFindPkSimple(&$script)
    {
        $table = $this->getTable();
        $platform = $this->getPlatform();
        $peerClassname = $this->getPeerClassname();
        $ARClassname = $this->getFullyQualifiedActualClassname();
        $this->declareClassFromBuilder($this->getStubObjectBuilder());
        $this->declareClasses('PDO', 'PropelException', 'PropelObjectCollection');
        $selectColumns = [];
        foreach ($table->getColumns() as $column) {
            if (!$column->isLazyLoad()) {
                $selectColumns [] = $platform->quoteIdentifier($column->getName());
            }
        }
        $conditions = [];
        foreach ($table->getPrimaryKey() as $index => $column) {
            $conditions [] = sprintf('%s = :p%d', $platform->quoteIdentifier($column->getName()), $index);
        }
        $query = sprintf('SELECT %s FROM %s WHERE %s', implode(', ', $selectColumns), $platform->quoteIdentifier($table->getName()), implode(' AND ', $conditions));
        $pks = [];
        if ($table->hasCompositePrimaryKey()) {
            foreach ($table->getPrimaryKey() as $index => $column) {
                $pks [] = "\$key[$index]";
            }
        } else {
            $pks [] = "\$key";
        }
        $pkHashFromRow = $this->getPeerBuilder()->getInstancePoolKeySnippet($pks);
        $script .= "
    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed \$key Primary key to use for the query
     * @param     PropelPDO \$con A connection object
     *
     * @return                 $ARClassname A model object, or null if the key is not found
     * @throws PropelException
     */
    protected function findPkSimple(\$key, \$con)
    {
        \$sql = '$query';
        try {
            \$stmt = \$con->prepare(\$sql);";
        if ($table->hasCompositePrimaryKey()) {
            foreach ($table->getPrimaryKey() as $index => $column) {
                $script .= $platform->getColumnBindingPHP($column, "':p$index'", "\$key[$index]", '			');
            }
        } else {
            $pk = $table->getPrimaryKey();
            $column = $pk[0];
            $script .= $platform->getColumnBindingPHP($column, "':p0'", "\$key", '			');
        }
        $script .= "
            \$stmt->execute();
        } catch (\\Exception \$e) {
            Propel::log(\$e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', \$sql), \$e);
        }
        \$obj = null;
        if (\$row = \$stmt->fetch(PDO::FETCH_NUM)) {";

        if ($col = $table->getChildrenColumn()) {
            $script .= "
            \$cls = {$peerClassname}::getOMClass(\$row, 0);
            \$obj = new \$cls();";
        } else {
            $script .= "
            \$obj = new $ARClassname();";
        }
        $script .= "
            \$obj->hydrate(\$row);
            {$peerClassname}::addInstanceToPool(\$obj, $pkHashFromRow);
        }
        \$stmt->closeCursor();

        return \$obj;
    }
";
    }

    protected function addDataTableJoin(&$script)
    {
        $script .= "/**
     * Fetches data from PRIMARY and DATA tables with 1 query
     *
     * @param string|null \$scope The scope to fetch the data from the VIEW (global or view)
     * @param int|null \$website_id The website_id if a view scope is requested
     * @param int|null $app_id The app_id if a view scope is requested
     * @param array|null \$columns Custom columns in the form of:
     *
     * array(
     *  array('column' => 'title', 'alias' => 'title_col')
     * )
     *
     * @return " . $this->getClassname() . "
     */
    public function joinDataView(\$scope = 'global', \$website_id = null,
                                \$app_id = null,
                                 array \$columns = null)
    {
        \$tblmap = \$this->getTableMap();
        \$data_table_name = \$tblmap->getPhpName() . 'Data';
        \$data_query = \$data_table_name . 'Query';
        /** @var ModelCriteria \$data_query_instance */
        \$data_query_instance = new \$data_query();
        /** @var TableMap \$data_query_tblmap */
        \$data_query_tblmap = \$data_query_instance->getTableMap();

        \$pks = array_keys(\$tblmap->getPrimaryKeys());
        \$data_pks = array_keys(\$data_query_tblmap->getPrimaryKeys());

        if (!\$columns) {
            \$columns_it = \$data_query_tblmap->getColumns();
            \$columns = array();

            foreach ((array)\$columns_it as \$column) {

                \$column_name = \$column->getName();

                if (\$column_name == 'scope' ||
                    \$column_name == 'app_id' ||
                    \$column_name == 'website_id' ||
                    in_array(\$column_name, \$data_pks)
                ) {
                    continue;
                }

                \$columns[] = array(
                    'column' => \$column_name,
                    'alias' => lcInflector::camelize(\$column_name)
                );

                unset(\$column);
            }
        }

        CoreHelper::addWebsitePropelJoinCriteria(\$this, \$tblmap->getName() . '_data',
            ['primary_keys' => \$pks, 'data_keys' => \$data_pks],
            \$columns,
            \$website_id,
            \$app_id
        );

        return \$this;
    }";
    }

    protected function addTranslate(&$script)
    {
        $script .= "
    /**
     * Translate a string in the context of the model
     * @param     string \$value String to translate
     *
     * @return string the translated string. Falls back to original string if cannot be translated
     */
    public function translate(\$value)
    {
        /** @var \lcTableMap \$tblm */
        \$tblm = \$this->getTableMap();
        return \$tblm->translate(\$value);
    }

    /**
     * Translate a string in the context of the model
     * @param     string \$value String to translate
     *
     * @return string the translated string. Falls back to original string if cannot be translated
     */
    public function t(\$value)
    {
        return \$this->translate(\$value);
    }
";
    }
}
