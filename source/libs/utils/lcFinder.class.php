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
 * @changed $Id: lcFinder.class.php 1455 2013-10-25 20:29:31Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1455 $
 */

/**
 * lcFinder
 *
 * $files = lcFinder::search('file')->set_filter('*.yml')->do_search_in('/folder/');
 * types (file, dir, any)
 *
 */
class lcFinder
{
    private $find_type = '';
    private $filter_rules = array();

    private $mindepth = 0;
    private $maxdepth = 10000;

    private $ignore_files = array('.svn', '.arch-params', '.monotone', '.bzr', '.git', '.hg', '.project');
    private $ignore_rules = array();

    private $sort_order = 1;

    public static function search($type)
    {
        $finder = new self();

        return $finder->set_type($type);
    }

    protected function set_type($type)
    {
        $type = strtolower($type);

        if ($type == 'dir') {
            $this->find_type = 'directory';
        } elseif ($type == 'directory') {
            $this->find_type = 'directory';
        } else {
            $this->find_type = 'file';
        }

        return $this;
    }

    public function setSortOrder($sort_order)
    {
        $this->sort_order = $sort_order;
    }

    public function set_ignore($ignore_rule)
    {
        $ignore_rule = sfGlobToRegex::glob_to_regex($ignore_rule);

        if (!in_array($ignore_rule, $this->ignore_rules)) {
            $this->ignore_rules[] = $ignore_rule;
        }

        return $this;
    }

    public function set_filter($filter_condition)
    {
        $filter_condition = sfGlobToRegex::glob_to_regex($filter_condition);

        if (!in_array($filter_condition, $this->filter_rules)) {
            $this->filter_rules[] = $filter_condition;
        }

        return $this;
    }

    public function do_search_in($start_directory)
    {
        if (!is_dir($start_directory)) {
            return array();
        }

        $files = $this->search_in($start_directory, 0);

        return array_unique($files);
    }

    public function search_in($dir, $depth)
    {
        $dir = realpath($dir);

        if ($depth >= $this->maxdepth) {
            return array();
        }

        if (is_link($dir)) {
            return array();
        }

        $files = array();
        $current_file = scandir($dir, $this->sort_order);

        foreach ($current_file as $entryname) {
            if ($entryname == '.' || $entryname == '..' || in_array($entryname, $this->ignore_files)) {
                continue;
            }

            $current_entry = $dir . DS . $entryname;

            if (is_link($current_entry)) {
                continue;
            }

            //dirs checkup
            if (is_dir($current_entry)) {

                if (($this->find_type === 'directory' || $this->find_type === 'any') && ($depth >= $this->mindepth) && $this->match_name($entryname)) {
                    $files[] = $current_entry;
                }

                $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
            } else {
                if (($this->find_type !== 'directory' || $this->find_type === 'any') && ($depth >= $this->mindepth) && $this->match_name($entryname)) {
                    $files[] = $current_entry;
                }
            }
        }

        return $files;
    }

    protected function match_name($entryname)
    {
        if ($this->ignore_rules) {
            foreach ($this->ignore_rules as $regex) {
                if (preg_match($regex, $entryname)) {
                    return false;
                }

                unset($regex);
            }
        }

        if (!$this->filter_rules) {
            return true;
        }

        foreach ($this->filter_rules as $regex) {
            if (preg_match($regex, $entryname)) {
                return true;
            }

            unset($regex);
        }

        return false;
    }

    public function depth($depth_lvl)
    {
        $this->maxdepth = (int)$depth_lvl;

        return $this;
    }
}