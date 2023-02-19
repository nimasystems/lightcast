<?php
declare(strict_types=1);

/**
 *
 */
class lcPropelBaseExtensionQueryBuilder extends \ExtensionQueryBuilder
{
    /**
     * Returns the prefixed classname that is being built by the current class.
     *
     * @return string
     * @see        DataModelBuilder#prefixClassname()
     */
    public function getClassname(): string
    {
        return 'Base' . $this->prefixClassname($this->getUnprefixedClassname());
    }
}
