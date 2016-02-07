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

class lcApiWebRequest extends lcWebRequest
{
    const CLIENT_API_LEVEL_HEADER = 'X-LC-Client-Api-Level';

    protected $client_api_level;

    public function initialize()
    {
        parent::initialize();

        $api_level = (int)$this->header(self::CLIENT_API_LEVEL_HEADER);
        $this->client_api_level = ($api_level ? $api_level : null);
    }

    public function shutdown()
    {
        parent::shutdown();
    }

    public function getClientApiLevel()
    {
        return $this->client_api_level;
    }
}
