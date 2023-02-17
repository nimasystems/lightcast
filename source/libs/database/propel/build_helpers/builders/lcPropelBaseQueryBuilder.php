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
        /** @var lcTableMap \$tblm */
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
