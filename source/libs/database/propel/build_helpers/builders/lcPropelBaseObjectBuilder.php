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

}
