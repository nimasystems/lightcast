<?php
declare(strict_types=1);

/**
 *
 */
class lcPropelObjectStubBuilder extends PHP5ExtensionObjectBuilder
{
    public function getNamespace(): string
    {
        return 'Gen\\Propel\\Models\\Om';
    }

    public function getTableMapNamespace(): string
    {
        return 'Gen\\Propel\\Models\\Map';
    }

    public function getClassname(): string
    {
        return 'Base' . $this->prefixClassname($this->getUnprefixedClassname());
    }

    public function getActualNamespace(): string
    {
        return '\\' . implode('\\', array_map(function ($el) {
                return lcInflector::camelize($el);
            }, explode('.', $this->getPackage())));
    }

    public function getActualClassname(): string
    {
        return $this->prefixClassname($this->getUnprefixedClassname());
    }

    public function getFullyQualifiedActualClassname(): string
    {
        if ($namespace = $this->getActualNamespace()) {
            return $namespace . '\\' . $this->getActualClassname();
        } else {
            return $this->getActualClassname();
        }
    }
}
