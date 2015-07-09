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

/**
 * File Description
 * @package File Category
 * @subpackage File Subcategory
 * @changed $Id: lcTagLink.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
class lcTagLink extends lcHtmlTag
{
    public function __construct($href, $rel = null, $type = null, $media = null)
    {
        parent::__construct('link', false);

        $this->setHref($href);
        $this->setRel($rel);
        $this->setType($type);
        $this->setMedia($media);
    }

    public static function getRequiredAttributes()
    {
        return array('href');
    }

    public static function getOptionalAttributes()
    {
        return array('rel', 'type', 'media');
    }

    public function getHref()
    {
        return $this->getAttribute('href');
    }

    public function setHref($href)
    {
        $this->setAttribute('href', $href);
        return $this;
    }

    public function getRel()
    {
        return $this->getAttribute('rel');
    }

    public function setRel($rel = null)
    {
        $this->setAttribute('rel', $rel);
        return $this;
    }

    public function getType()
    {
        return $this->getAttribute('type');
    }

    public function setType($type = null)
    {
        $this->setAttribute('type', $type);
        return $this;
    }

    public function getMedia()
    {
        return $this->getAttribute('media');
    }

    public function setMedia($media = null)
    {
        $this->setAttribute('media', $media);
        return $this;
    }
}
