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
 * @changed $Id: lcHtmlTag.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */
abstract class lcHtmlTag extends lcHtmlBaseTag implements iCoreAttributes, iEventAttributes
{
    protected $classes = array();

    public function __construct($tagname, $is_closed = false)
    {
        parent::__construct($tagname, $is_closed);
    }

    public function asHtml()
    {
        return parent::asHtml();
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function addClass($class_name)
    {
        $this->classes[$class_name] = $class_name;
        $this->setAttribute('class', $this->getClass());
        return $this;
    }

    public function setAttribute($name, $value = null)
    {
        if ($name == 'class') {
            $this->setClass($value);
        }

        parent::setAttribute($name, $value);
        return $this;
    }

    public function setClass($value = null)
    {
        if ($value) {
            $classes = is_array($value) ? $value : array_filter(explode(' ', $value));

            foreach ($classes as $class) {
                $this->classes[$class] = $class;
                unset($class);
            }
        } else {
            $this->classes = array();
        }

        parent::setAttribute('class', $this->getClass());

        return $this;
    }

    public function getClass()
    {
        return implode(' ', (array)$this->classes);
    }

    public function removeClass($class_name)
    {
        if ($this->hasClass($class_name)) {
            unset($this->classes[$class_name]);
            $this->setAttribute('class', $this->getClass());
        }
        return $this;
    }

    public function hasClass($class_name)
    {
        return isset($this->classes[$class_name]);
    }

    public function getId()
    {
        return $this->attributes->get('id');
    }

    public function setId($value)
    {
        $this->setAttribute('id', $value);
        return $this;
    }

    public function getTitle()
    {
        return $this->attributes->get('title');
    }

    public function setTitle($value = null)
    {
        $this->setAttribute('title', $value);
        return $this;
    }

    public function getDisabled()
    {
        return $this->getAttribute('disabled');
    }

    public function setDisabled($disabled = true)
    {
        if ($disabled) {
            $this->setAttribute('disabled', 'disabled');
        } else {
            $this->removeAttribute('disabled');
        }
        return $this;
    }

    public function getStyle()
    {
        return $this->attributes->get('style');
    }

    public function setStyle($value = null)
    {
        $this->setAttribute('style', $value);
        return $this;
    }

    public function getOnClick()
    {
        return $this->attributes->get('onclick');
    }

    public function setOnClick($value = null)
    {
        $this->setAttribute('onclick', $value);
        return $this;
    }

    public function getOnChange()
    {
        return $this->attributes->get('onchange');
    }

    public function setOnChange($value = null)
    {
        $this->setAttribute('onchange', $value);
        return $this;
    }

    public function getOnDblClick()
    {
        return $this->attributes->get('ondblclick');
    }

    public function setOnDblClick($value = null)
    {
        $this->setAttribute('ondblclick', $value);
        return $this;
    }

    public function getOnMouseDown()
    {
        return $this->attributes->get('onmousedown');
    }

    public function setOnMouseDown($value = null)
    {
        $this->setAttribute('onmousedown', $value);
        return $this;
    }

    public function getOnMouseUp()
    {
        return $this->attributes->get('onmouseup');
    }

    public function setOnMouseUp($value = null)
    {
        $this->setAttribute('onmouseup', $value);
        return $this;
    }

    public function getOnMouseOver()
    {
        return $this->attributes->get('onmouseover');
    }

    public function setOnMouseOver($value = null)
    {
        $this->setAttribute('onmouseover', $value);
        return $this;
    }

    public function getOnMouseMove()
    {
        return $this->attributes->get('onmousemove');
    }

    public function setOnMouseMove($value = null)
    {
        $this->setAttribute('onmousemove', $value);
        return $this;
    }

    public function getOnMouseOut()
    {
        return $this->attributes->get('onmouseout');
    }

    public function setOnMouseOut($value = null)
    {
        $this->setAttribute('onmouseout', $value);
        return $this;
    }

    public function getOnKeyPress()
    {
        return $this->attributes->get('onkeypress');
    }

    public function setOnKeyPress($value = null)
    {
        $this->setAttribute('onkeypress', $value);
        return $this;
    }

    public function getOnKeyDown()
    {
        return $this->attributes->get('onkeydown');
    }

    public function setOnKeyDown($value = null)
    {
        $this->setAttribute('onkeydown', $value);
        return $this;
    }

    public function getOnKeyUp()
    {
        return $this->attributes->get('onkeydown');
    }

    public function setOnKeyUp($value = null)
    {
        $this->setAttribute('onkeyup', $value);
        return $this;
    }
}
