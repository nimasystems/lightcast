<?php

class lcActionNotFoundException extends lcException implements iHTTPException
{
    public function getStatusCode()
    {
        return '404';
    }
}
