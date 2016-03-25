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
}
