<?php

class lcControllerNotFoundException extends lcException implements iHTTPException
{
    public function getStatusCode()
    {
        return '404';
    }
}
