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

class lcHTMLTemplateLayoutView extends lcHTMLTemplateView implements iSupportsLayoutDecoration
{
    const DEFAULT_CONTENT_TYPE = 'text/html';

    protected $replacement_string;
    protected $decorate_content;
    protected $decorate_content_type;

    public function shutdown()
    {
        $this->decorate_content =
            null;

        parent::shutdown();
    }

    public function getContentType()
    {
        return self::DEFAULT_CONTENT_TYPE;
    }

    public function getSupportedContentTypes()
    {
        return [self::DEFAULT_CONTENT_TYPE];
    }

    public function getReplacementString()
    {
        return $this->replacement_string;
    }

    public function setReplacementString($replacement_string)
    {
        $this->replacement_string = $replacement_string;
    }

    public function getDecorateContent()
    {
        return $this->decorate_content;
    }

    public function setDecorateContent($content, $content_type = null)
    {
        $this->decorate_content = $content;
        $this->decorate_content_type = $content_type;
    }

    public function getDecorateContentType()
    {
        return $this->decorate_content_type;
    }

    public function render()
    {
        if (!$this->replacement_string) {
            throw new lcInvalidArgumentException('Replacement string not set');
        }

        $decorator_content = parent::render();

        if (!$decorator_content) {
            return $this->decorate_content;
        }

        // decorate now
        $decorator_content = str_replace($this->replacement_string, $this->decorate_content, $decorator_content);

        return $decorator_content;
    }

    protected function getViewContent()
    {
        $c = parent::getViewContent();
        return $c;
    }
}
