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

// main app action dispatcher

/*
 As proposed from http://www.w3.org/Protocols/HTTP/Methods.html
*/

class lcHttpMethod
{
    const METHOD_ANY = 0;
    const METHOD_GET = 1;
    const METHOD_PUT = 2;
    const METHOD_POST = 3;
    const METHOD_HEAD = 4;
    const METHOD_DELETE = 5;

    const METHOD_CHECKOUT = 5;
    const METHOD_SHOWMETHOD = 6;
    const METHOD_LINK = 7;
    const METHOD_UNLINK = 8;
    const METHOD_CHECKIN = 9;
    const METHOD_TEXTSEARCH = 10;
    const METHOD_SPACEJUMP = 11;
    const METHOD_SEARCH = 12;

    public static function getType($string)
    {
        $string = strtolower($string);

        if ($string == 'any') {
            return self::METHOD_ANY;
        } elseif ($string == 'get') {
            return self::METHOD_GET;
        } elseif ($string == 'delete') {
            return self::METHOD_DELETE;
        } elseif ($string == 'put') {
            return self::METHOD_PUT;
        } elseif ($string == 'post') {
            return self::METHOD_POST;
        } elseif ($string == 'head') {
            return self::METHOD_HEAD;
        } elseif ($string == 'checkout') {
            return self::METHOD_CHECKOUT;
        } elseif ($string == 'showmethod') {
            return self::METHOD_SHOWMETHOD;
        } elseif ($string == 'link') {
            return self::METHOD_LINK;
        } elseif ($string == 'unlink') {
            return self::METHOD_UNLINK;
        } elseif ($string == 'checkin') {
            return self::METHOD_CHECKIN;
        } elseif ($string == 'textsearch') {
            return self::METHOD_TEXTSEARCH;
        } elseif ($string == 'spacejump') {
            return self::METHOD_SPACEJUMP;
        } elseif ($string == 'search') {
            return self::METHOD_SEARCH;
        } else {
            return false;
        }
    }
}