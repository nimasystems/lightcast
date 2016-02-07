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

class lcViewConfigHandler extends lcEnvConfigHandler
{
    public function getDefaultValues()
    {
        return array('view' => array(
            'filters' => array(),
            'content_type' => 'text/html',
            'charset' => 'utf-8',
            'metatags' => array('author' => 'Nimasystems Ltd (http://www.nimasystems.com)',),
            'base' => null,
            'dir' => 'ltr',
            'htmlver' => '4',
            'allow_javascripts' => true,
            'allow_stylesheets' => true,
            'allow_rss_feeds' => true,
            'allow_metatags' => true,
            'has_layout' => true,
            'decorator' => 'index',
            'extension' => 'htm',
            'google_analytics' => null,
            'replacement_policy' => 'level',
            'clientside_js' => false,
        ));
    }
}
