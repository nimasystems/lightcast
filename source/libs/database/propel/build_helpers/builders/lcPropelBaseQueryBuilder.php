<?php

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
    public function getClassFilePath()
    {
        $overriden_path = lcPropelBaseObjectBuilder::getOverridenClassFilePath('om', $this->getGeneratorConfig(), $this->getClassname());

        if ($overriden_path) {
            return $overriden_path;
        } else {
            return ClassTools::createFilePath($this->getPackagePath(), $this->getClassname());
        }
    }

    protected function addClassBody(&$script)
    {
        parent::addClassBody($script);

        $this->addTranslate($script);
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
