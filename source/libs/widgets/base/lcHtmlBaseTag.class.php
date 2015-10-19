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
 * @changed $Id: lcHtmlBaseTag.class.php 1529 2014-06-01 09:20:40Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1529 $
 */
abstract class lcHtmlBaseTag implements iAsHTML
{
    protected $parent_widget;

    protected $tagname;
    protected $content;
    protected $attributes;
    protected $is_closed;

    public function __construct($tagname, $is_closed = false)
    {
        $this->tagname = $tagname;
        $this->is_closed = $is_closed;

        $this->attributes = new lcHtmlAttributeCollection();
    }

    public static function getRequiredAttributes()
    {
        //
    }

    public static function getOptionalAttributes()
    {
        //
    }

    public function __destruct()
    {
        //
    }

    public function useWidget(lcHtmlBaseTag $widget)
    {
        $this->parent_widget = $widget;
        return $widget;
    }

    public function endUse()
    {
        return $this->parent_widget;
    }

    public function attr($name, $value = null)
    {
        return $this->setAttribute($name, $value);
    }

    public function setAttribute($name, $value = null)
    {
        if (!isset($value)) {
            $this->attributes->remove($name);
        } else {
            $this->attributes->set($name, $value);
        }
        return $this;
    }

    public function removeAttribute($name)
    {
        $this->attributes->remove($name);
        return $this;
    }

    public function removeAttributes()
    {
        $this->attributes->clear();
        return $this;
    }

    public function getAttribute($name)
    {
        return $this->attributes->get($name);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function hasAttribute($name)
    {
        return $this->attributes->get($name) ? true : false;
    }

    public function getName()
    {
        return $this->tagname;
    }

    public function __get($name)
    {
        return $this->attributes->get($name);
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        if (!$this->is_closed) {
            throw new lcInvalidArgumentException('Tag ' . $this->tagname . ' is an open tag. You cannot set content in it');
        }

        $this->content = $content;
        return $this;
    }

    public function isClosed()
    {
        return $this->is_closed;
    }

    public function __toString()
    {
        $ret = null;
        try {
            $ret = $this->asHtml();
        } catch (Exception $e) {
            $ret = 'error: ' . $e->getMessage();
        }
        return $ret;
    }

    public function asHtml()
    {
        return '<' . trim(implode(' ',
                array(
                    $this->tagname,
                    $this->attributes->asHtml()
                ))
        ) .
        ($this->is_closed ? '>' . $this->content . '</' . $this->tagname . '>' : ' />');
    }

    protected function setIsClosed($is_closed = true)
    {
        $this->is_closed = $is_closed;
        return $this;
    }

    public function toString()
    {
        return $this->asHtml();
    }
}
