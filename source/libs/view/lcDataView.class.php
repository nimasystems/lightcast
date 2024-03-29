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

class lcDataView extends lcRawContentView
{
    public function getViewContent()
    {
        return $this->formatContent();
    }

    protected function formatContent()
    {
        $content = $this->content;

        if ($content === null) {
            return null;
        }

        // we currently support only json representation
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        } else {
            $content = json_encode($content);
        }

        // make the output pretty while debugging
        if (DO_DEBUG) {
            $content = lcVars::indentJson($content);
        }

        return $content;
    }
}
