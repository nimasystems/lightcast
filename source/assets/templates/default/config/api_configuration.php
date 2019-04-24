<?php

class ApiConfiguration extends lcWebServiceConfiguration
{
    const API_LEVEL = 1;

    public function getProjectName()
    {
        throw new lcConfigException('Set a proper project name');
    }

    public function getApiLevel()
    {
        return self::API_LEVEL;
    }
}
