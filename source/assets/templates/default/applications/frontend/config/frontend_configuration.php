<?php

class FrontendConfiguration extends lcWebConfiguration
{
    public function getApplicationName()
    {
        return 'frontend';
    }

    public function getProjectName()
    {
        throw new lcConfigException('Set a proper project name');
    }
}

