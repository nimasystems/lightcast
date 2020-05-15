<?php

class UtcDateTime extends DateTime
{
    public function __construct($time = 'now', DateTimeZone $timezone = null)
    {
        parent::__construct($time, new DateTimeZone('UTC'));
    }
}