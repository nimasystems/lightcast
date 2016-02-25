<?php

/*
 * Lightcast - A PHP MVC Framework
* Copyright (C) 2005 Nimasystems Ltd
*
* This program is NOT free software; you cannot redistribute and/or modify
* it's sources under any circumstances without the explicit knowledge and
* agreement of the rightful owner of the software - Nimasystems Ltd.
*
* This program is distributed WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
* PURPOSE.  See the LICENSE.txt file for more information.
*
* You should have received a copy of LICENSE.txt file along with this
* program; if not, write to:
* NIMASYSTEMS LTD
* Plovdiv, Bulgaria
* ZIP Code: 4000
* Address: 95 "Kapitan Raycho" Str.
* E-Mail: info@nimasystems.com


*/

class lcDbUtils
{
    public static function varToStream($var)
    {
        $var = serialize($var);

        $fp = fopen('php://temp/', 'r+');
        fputs($fp, $var);
        rewind($fp);
        return stream_get_contents($fp);
    }

    public static function streamToVar($stream)
    {
        return unserialize(stream_get_contents($stream));
    }

    public static function pdoExceptionToString(Exception $e)
    {
        $errstr = 'Error while working with the database: ';

        if (!$e instanceof PropelException) {
            return $errstr . $e->getMessage();
        }

        $errors = array(
            23000 => 'Duplicate Record Name'
        );

        if (!array_key_exists($e->getCause()->getCode(), $errors)) {
            return $errstr . $e->getMessage();
        }

        return $errstr . $errors[$e->getCause()->getCode()];
    }
}
