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

class lcHttpAcceptParser extends lcObj
{
    private $type;
    private $hasparsed;
    private $accepts = array();
    private $acceptstring;

    public function __construct($type, $parse_string)
    {
        parent::__construct();

        $this->type = $type;
        $this->acceptstring = $parse_string;
    }

    // checks if response can be sent with a specific mimetype
    public function check($accept_test)
    {
        $this->_parseInternal();

        if ($this->type == lcHttpAcceptType::ACCEPT_MIMETYPES) {
            return $this->_check_mimetype($accept_test);
        } elseif ($this->type == lcHttpAcceptType::ACCEPT_LANGUAGE) {
            return $this->_check_language($accept_test);
        } else {
            return $this->_check_general($accept_test);
        }
    }

    private function _parseInternal()
    {
        if ($this->hasparsed) {
            return;
        }

        if ($this->type == lcHttpAcceptType::ACCEPT_MIMETYPES) {
            $this->_parseMimetypes();
        } else {
            $this->_parseGeneral();
        }
    }

    private function _parseMimetypes()
    {
        if ((!$this->acceptstring) || (strlen($this->acceptstring) < 1)) {
            array_push($this->accepts, '*/*');
        }

        $e = explode(',', $this->acceptstring);

        if (count($e) > 0) {
            $narr = array();
            $narr2 = array();

            foreach ($e as $key => $val) {
                $ex1 = explode(';q=', $val);

                if (count($ex1) < 2) {
                    $narr[] = $val;
                } else {
                    $narr2[$ex1[0]] = $ex1[1];
                }
            }

            arsort($narr2, SORT_NUMERIC);
            $narr2 = array_flip($narr2);

            $this->accepts = array_values(array_merge($narr, $narr2));

            unset($key, $val);
        }

        $this->hasparsed = true;
    }

    private function _parseGeneral()
    {
        if ((!$this->acceptstring) || (strlen($this->acceptstring) < 1)) {
            array_push($this->accepts, '*');
        }

        $e = explode(',', $this->acceptstring);

        if (count($e) > 0) {
            $narr = array();
            $narr2 = array();

            foreach ($e as $key => $val) {
                $ex1 = explode(';q=', $val);

                if (count($ex1) < 2) {
                    $narr[] = $val;
                } else {
                    $narr2[$ex1[0]] = $ex1[1];
                }

                unset($key, $val);
            }

            arsort($narr2, SORT_NUMERIC);
            $narr2 = array_flip($narr2);

            $this->accepts = array_merge($narr, $narr2);
        }

        $this->hasparsed = true;
    }

    private function _check_mimetype($mimetype)
    {
        if (!lcMimetypeHelper::isMimetype($mimetype)) {
            return false;
        }

        if (in_array('*/*', $this->accepts)) {
            return true;
        }

        $m = explode('/', $mimetype);

        foreach ($this->accepts as $key => $accept) {
            if (strcasecmp($mimetype, $accept) == 0) {
                return true;
            }

            $a = explode('/', $accept);

            // check for subtype=ANY
            if (($m[0] == $a[0]) && ($a[1] == '*')) {
                return true;
            }

            // check for type=ANY
            if (($m[1] = $a[1]) && ($a[0] == '*')) {
                return true;
            }

            unset($key, $accept);
        }

        return false;
    }

    private function _check_language($locale)
    {
        if (in_array('*', $this->accepts)) {
            return true;
        }

        foreach ($this->accepts as $key => $accept) {
            if (strcasecmp($locale, $accept) == 0) {
                return true;
            }

            $e = @explode('-', $locale);
            $a = @explode('-', $accept);

            // TODO - not too sure if this is really valid!
            if ($e[0] == $a[0]) {
                return true;
            }

            unset($key, $accept);
        }

        return false;
    }

    private function _check_general($accept_test)
    {
        if (in_array('*', $this->accepts)) {
            return true;
        }

        foreach ($this->accepts as $key => $accept) {
            if (strcasecmp($accept_test, $accept) == 0) {
                return true;
            }

            unset($key, $accept);
        }

        return false;
    }

    public function getAsString()
    {
        return $this->acceptstring;
    }

    public function getPreferred()
    {
        $this->_parseInternal();

        return $this->accepts;
    }
}