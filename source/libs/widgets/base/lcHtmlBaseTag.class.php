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

abstract class lcHtmlBaseTag extends lcObj implements iAsHTML
{
    protected $parent_widget;

    protected $tagname;
    protected $content;

    /** @var lcHtmlBaseTag[]|null */
    protected $children;

    protected $attributes;
    protected $is_closed;

    public function __construct($tagname, $is_closed = false)
    {
        parent::__construct();

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

    public function __get($property)
    {
        return $this->attr($property);
    }

    public function __set($property, $value = null)
    {
        $this->attr($property, $value);
    }

    /**
     * @param $name
     * @param null $value
     * @return $this
     */
    public function attr($name, $value = null)
    {
        return $this->setAttribute($name, $value);
    }

    /**
     * @param $name
     * @param null $value
     * @return $this
     */
    public function setAttribute($name, $value = null)
    {
        if (!isset($value)) {
            $this->attributes->remove($name);
        } else {
            $this->attributes->set($name, $value);
        }
        return $this;
    }

    public function __call($method, array $params = null)
    {
        if (lcStrings::startsWith($method, 'set') && (is_string($params) || is_numeric($params))) {
            $tmp = lcInflector::controllerize(substr($method, 3, strlen($method)));
            $this->attr($tmp, $params);
            return;
        }

        parent::__call($method, $params);
    }

    /**
     * @param lcHtmlBaseTag $widget
     * @return $this
     */
    public function useWidget(lcHtmlBaseTag $widget)
    {
        $this->parent_widget = $widget;
        return $widget;
    }

    /**
     * @return $this
     */
    public function endUse()
    {
        return $this->parent_widget;
    }

    /**
     * @param $tag
     * @return $this
     */
    public function removeChild($tag)
    {
        unset($this->children[$tag]);
        return $this;
    }

    /**
     * @return $this
     */
    public function removeChildren()
    {
        $this->children = null;
        return $this;
    }

    /**
     * @param $tag
     * @return lcHtmlBaseTag|null
     */
    public function getChild($tag)
    {
        return (isset($this->children[$tag]) ? $this->children[$tag] : null);
    }

    /**
     * @param $name
     * @return lcHtmlBaseTag
     */
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

    /**
     * @param array|string|lcHtmlBaseTag $fields
     * @return lcHtmlBaseTag
     * @throws lcInvalidArgumentException
     */
    public function append($fields)
    {
        if (is_array($fields)) {
            foreach ($fields as $data) {
                $this->append($data);
                unset($data);
            }
        } else if ($fields instanceof lcHtmlBaseTag) {
            $this->addChild($fields);
        } else if ($fields) {
            $this->setContent($this->getContent() . $fields);
        }

        return $this;
    }

    /**
     * @param lcHtmlBaseTag $child
     * @param null $tag
     * @return $this
     */
    public function addChild(lcHtmlBaseTag $child, $tag = null)
    {
        $tag = $tag ? $tag : 'gen_' . lcStrings::randomString(10, true);
        $this->children[$tag] = $child;
        return $this;
    }

    public function getContent()
    {
        return ($this->content ? $this->content : $this->getChildren(true));
    }

    public function setContent($content)
    {
        if (!$this->is_closed) {
            throw new lcInvalidArgumentException('Tag ' . $this->tagname . ' is an open tag. You cannot set content in it');
        }

        $this->content = $content;
        return $this;
    }

    /**
     * @param bool $compiled
     * @return lcHtmlBaseTag[]|null|string
     */
    public function getChildren($compiled = false)
    {
        if (!$compiled) {
            return $this->children;
        } else {
            $children = $this->children;
            $out = null;

            if ($children) {
                foreach ($children as $child) {
                    $out .= $child->toString();
                    unset($child);
                }
            }

            return $out;
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->asHtml();
    }

    public function asHtml()
    {
        return '<' . trim(implode(' ',
                    [
                        $this->tagname,
                        $this->attributes->asHtml(),
                    ])
            ) .
            ($this->getIsClosed() ? '>' . $this->getContent() . '</' . $this->tagname . '>' : ' />');
    }

    /**
     * @return bool
     */
    public function getIsClosed()
    {
        return $this->is_closed;
    }

    protected function setIsClosed($is_closed = true)
    {
        $this->is_closed = $is_closed;
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
}
