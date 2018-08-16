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

class lcIterateParamHolder extends lcObj implements ArrayAccess
{
    const REPLACE_LEVEL = 1;
    const REPLACE_DEEP = 2;

    /**
     * @var lcIterateParamHolder[]
     */
    private $subnodes;

    /**
     * @var lcIterateParamHolder[]
     */
    private $node_repeats;

    private $node_name;

    /**
     * @var array
     */
    private $params;

    private $raw_text;
    private $replacement_policy;

    public function __construct($node_name = null, array $params = null, $replacement_policy =
    self::REPLACE_LEVEL)
    {
        parent::__construct();

        $this->node_name = $node_name;
        $this->params = $params;
        $this->node_repeats = [];
        $this->replacement_policy = $replacement_policy;
    }

    public function __destruct()
    {
        $this->clear();

        parent::__destruct();
    }

    public function clear()
    {
        $subnodes = $this->subnodes;

        if ($subnodes) {
            foreach ($subnodes as $name => $subnode) {
                unset($this->subnodes[$name]);
                unset($name, $subnode);
            }
        }

        $node_repeats = $this->node_repeats;

        if ($node_repeats) {
            foreach ($node_repeats as $idx => $node) {
                unset($this->node_repeats[$idx]);
                unset($idx, $node);
            }
        }

        $this->subnodes =
        $this->node_repeats =
        $this->params =
        $this->raw_text =
        $this->replacement_policy =
            null;
    }

    public function setReplaceLevel()
    {
        $this->replacement_policy = self::REPLACE_LEVEL;
    }

    public function setReplaceDeep()
    {
        $this->replacement_policy = self::REPLACE_DEEP;
    }

    public function setReplacementPolicyStr($policy_str)
    {
        switch ($policy_str) {
            case 'level':
                {
                    $this->setReplacementPolicy(self::REPLACE_LEVEL);
                    break;
                }
            case 'deep':
                {
                    $this->setReplacementPolicy(self::REPLACE_DEEP);
                    break;
                }
            default:
                {
                    $this->setReplacementPolicy(self::REPLACE_LEVEL);
                    break;
                }
        }
    }

    public function getReplacementPolicy()
    {
        return $this->replacement_policy;
    }

    public function setReplacementPolicy($policy)
    {
        $this->replacement_policy = $policy;
    }

    public function copyNode($name, lcIterateParamHolder $node)
    {
        $copy = clone $node;
        $copy->setNodeName($name);
        $this->insertNode($name, $copy);

        return $copy;
    }

    public function insertNode($name, lcIterateParamHolder $node)
    {
        $this->subnodes[$name] = $node;

        return $node;
    }

    public function getNode($name, $params = null)
    {
        assert(isset($name));

        if (isset($this->subnodes[$name])) {
            return $this->subnodes[$name];
        }

        $subnode = new lcIterateParamHolder($name, $params);

        $this->subnodes[$name] = $subnode;

        return $this->subnodes[$name];
    }

    public function repeat($name, $params = null)
    {
        $rep = count($this->node_repeats);

        $this->node_repeats[$rep] = new lcIterateParamHolder($name, $params);;

        return $this->node_repeats[$rep];
    }

    public function getDeepNode($node_deep_name)
    {
        if ($node_deep_name{0} == '/') {
            $node_deep_name = substr($node_deep_name, 1, strlen($node_deep_name));
        }

        if ($node_deep_name{strlen($node_deep_name) - 1} == '/') {
            $node_deep_name = substr($node_deep_name, 0, strlen($node_deep_name) - 1);
        }

        $tmp = explode('/', $node_deep_name);

        if (count($tmp) < 2) {
            return isset($this->subnodes[$tmp[0]]) ? $this->subnodes[$tmp[0]] : null;
        } else {
            return $this->findNode($this->subnodes, $tmp);
        }
    }

    /**
     * @param lcIterateParamHolder[] $nodes
     * @param array $node_path
     * @return null
     */
    protected function findNode(array $nodes, array $node_path)
    {
        if (!$node_path || !$nodes) {
            return null;
        }

        foreach ($nodes as $key => $subnode) {
            if ($key == $node_path[0]) {
                array_shift($node_path);

                if (!$node_path) {
                    return $subnode;
                }

                return $this->findNode($subnode->getNodes(), $node_path);
            }
        }

        return null;
    }

    public function getNodes()
    {
        return $this->subnodes;
    }

    public function rawText($text = null)
    {
        if (isset($text)) {
            $this->params = null;
            $this->node_repeats = null;
            $this->raw_text = $text;
            $this->subnodes = null;
        }

        return $this->raw_text;
    }

    public function getRepeats()
    {
        return $this->node_repeats;
    }

    public function getNodeName()
    {
        return $this->node_name;
    }

    public function setNodeName($name)
    {
        $this->node_name = $name;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params = null)
    {
        $this->params = $params;
    }

    public function clearParams()
    {
        $this->params = null;
    }

    public function removeParam($name)
    {
        if (!isset($this->params[$name])) {
            return false;
        }

        unset($this->params[$name]);

        return true;
    }

    // left here for compatibility
    public function __get($name)
    {
        return $this->get($name);
    }

    // left here for compatibility
    public function __set($name, $value = null)
    {
        $this->set($name, $value);
    }

    public function set($name, $value)
    {
        $this->setParam($name, $value);
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function get($name)
    {
        return $this->getParam($name);
    }

    public function getParam($name)
    {
        if (!isset($this->params[$name])) {
            return null;
        }

        return $this->params[$name];
    }

    public function offsetExists($name)
    {
        return array_key_exists($name, $this->params);
    }

    public function offsetGet($name)
    {
        if (!array_key_exists($name, $this->params)) {
            return null;
        }

        return $this->params[$name];
    }

    public function offsetSet($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function offsetUnset($name)
    {
        unset($this->params[$name]);
    }
}