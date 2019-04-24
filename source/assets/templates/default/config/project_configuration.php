<?php

class ProjectConfiguration extends lcProjectConfiguration
{
    public function getRevisionVersion()
    {
        return 1;
    }

    public function getProjectName()
    {
        throw new lcConfigException('Set a proper project name');
    }

    public function getAutoloadClasses()
    {
        return null;
    }
}

