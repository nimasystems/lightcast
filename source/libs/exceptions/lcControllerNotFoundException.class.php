<?php

class lcControllerNotFoundException extends lcException implements iHTTPException
{
    protected $severity = self::SEVERITY_LEVEL_WARNING;

    public function getStatusCode()
    {
        return '404';
    }
}
