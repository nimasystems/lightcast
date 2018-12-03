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

abstract class lcRoute extends lcObj
{
    const PARAM_MATCH = ':';
    const DEFAULT_TOKENIZER = '/';
    const EXT_DELIMITER = '.';
    const ANY_TOKEN = '*';
    const REGEX_START = '`';
    const SEP_TYPE_TOKEN = 1;
    const SEP_TYPE_TEXT = 2;
    const SEP_TYPE_PARAM = 3;
    const SEP_TYPE_REGEX = 4;
    const SEP_TYPE_ANY = 5;
    /**
     * The route pattern
     * Example: /:application/:controller/:action
     */
    protected $route;
    protected $default_params;
    /**
     * Preg match requirements that check if a param
     * matches a condition
     * Example: [id: \d+]
     *
     * Predefined checks:
     *
     * int, float, minlen-x, maxlen-x, minvalue-x, maxvalue-x
     */
    protected $requirements;
    protected $options;
    /**
     * Predefined parameters that will be
     * set with the route with priority
     * Example: module: home or action: index
     */
    protected $params;
    private $context;
    private $live_params;
    private $fixed_route;
    private $compiled;
    private $tokens;
    private $ext;
    private $compare_regex;
    private $custom_param_validate = '\+';
    private $tokenizers = [
        '/',
        '.'
    ];

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function setRequirements(array $requirements = null)
    {
        $this->requirements = $requirements;
    }

    /*
     * Options include:
    * - method - the allowed type of http request (get/post)
    * - ajax_request_only - allow only AJAX requests
    * - security: (none|default) - omit security checks for the request
    */

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options = null)
    {
        $this->options = $options;
    }

    public function setContext($context, array $live_params = null)
    {
        $this->context = $context;
        $this->live_params = $live_params;
    }

    public function reCompile()
    {
        $this->compiled = false;

        return $this->compile();
    }

    /**
     * Tokenizes the current route to separate
     * URL / PARAMS
     * Returns the found params
     * @return string
     * @throws lcInvalidArgumentException
     */
    protected function compile()
    {
        // if already compiled return
        if ($this->compiled) {
            return true;
        }
        //if (!$this->route) return false;

        $this->tokens = [];
        $this->params = [];
        $this->compare_regex = null;
        $this->ext = null;

        // fix route, trim and remove startin tokenizer if such
        $this->fixed_route = trim($this->route);

        // remove / from the start of the route
        if (mb_strlen($this->fixed_route) < 1) {
            $this->fixed_route = self::DEFAULT_TOKENIZER;
        } else if ($this->fixed_route{0} != self::DEFAULT_TOKENIZER) {
            $this->fixed_route = self::DEFAULT_TOKENIZER . $this->fixed_route;
        }

        // find the ereg replaces in the route and save them for later usage
        $ereg_pattern = "/\`(.*?)\`/u";
        $saved_eregs = [];

        if (preg_match_all($ereg_pattern, $this->fixed_route, $ereg_matches)) {
            if ($ereg_matches) {
                foreach ($ereg_matches[1] as $match) {
                    $saved_eregs[] = $match;
                    unset($match);
                }
            }
        }

        // replace found eregs in route with a ereg modifier
        $this->fixed_route = preg_replace($ereg_pattern, '@', $this->fixed_route);

        // find the extension if any
        /*if ($fextpos = strrpos($this->fixed_route, self::EXT_DELIMITER))
        {
        $route_tmp = substr($this->fixed_route, 0, $fextpos);
        $ext = substr($this->fixed_route, $fextpos+1, mb_strlen($this->fixed_route));

        // separate only if ext not parametrized
        if (!strstr($ext, ':')) {
        $this->ext = $ext;
        $this->fixed_route = $route_tmp;
        }
        }*/

        $last_ereg = 0;
        $compare_regex = [];
        $new_route = self::DEFAULT_TOKENIZER;
        $lastval = '';

        $fix_param_name_pattern = "/[\w\-]*|[:][\w]*|[*]|[\@]|\`[^`]+\`/iu";

        //$route = $this->fixed_route;

        // parse the route
        //$ex1 = array_filter(explode(self::TOKENIZER, $this->fixed_route));
        //$ex1 = array_filter(preg_split('/(' . implode('|', $this->tokenizers) . ')/', $this->fixed_route));

        $lpos = 1;
        $npos = 0;
        $tk = null;
        $is_last = false;
        $f = $this->fixed_route;
        $fs = strlen($f);
        $tkn = $this->tokenizers;
        $full = null;

        while (true) {
            foreach ($tkn as $_t) {
                $tk = $_t;

                $npos = lcUnicode::strpos($f, $_t, $lpos);

                if ($npos !== false) {
                    break;
                }

                unset($_t);
            }

            if ($npos === false) {
                $is_last = true;
                $npos = $fs;
            }

            $route_param = lcUnicode::substr($f, $lpos, $npos - $lpos);
            $lpos = $npos + 1;

            if ($route_param) {
                $full .= $route_param . ($is_last ? null : $tk);
            }

            // verify if valid
            if (!preg_match_all($fix_param_name_pattern, $route_param, $matches)) {
                throw new lcInvalidArgumentException('Cannot parse route: ' . $this->route);
            }

            $matches = array_filter($matches[0]);

            if ($matches) {
                $ak = array_keys($matches);
                $ak = $ak[0];

                $route_param = $matches[$ak];
            } else {
                $route_param = null;
            }

            // if it is a named param
            if ($route_param{0} == self::PARAM_MATCH) {
                $type = self::SEP_TYPE_PARAM;
                $route_param = lcUnicode::substr($route_param, 1, mb_strlen($route_param));

                if (!$route_param) {
                    continue;
                }

                // if a requirement is set for this param - apply it
                // otherwise apply the default checks - strings, numbers, white spaces

                if ($this->requirements) {
                    $reqy = null;

                    foreach ($this->requirements as $reqx => $reqw) {
                        if ($reqx != $route_param) {
                            continue;
                        }

                        $reqy = $reqw;

                        unset($reqx, $reqw);
                    }
                } else {
                    $reqy = null;
                }

                $reqy ?
                    $req = $reqy :
                    $req = "[" . $this->custom_param_validate . "\]\[\w\d\s_-]*";

                // add to params
                $this->params[] = $route_param;

                $compare_regex[] = '(?P<' . preg_quote($route_param) . '>' . $req . ')';

                $new_route .= self::PARAM_MATCH . $route_param;
            } elseif ($route_param == self::ANY_TOKEN) {
                // if it is a * any match token

                // check if this is a * next to a *
                if ($lastval == self::ANY_TOKEN) {
                    continue;
                }

                $type = self::SEP_TYPE_ANY;
                $route_param = self::ANY_TOKEN;

                $compare_regex[] = ".*";

                $new_route .= self::ANY_TOKEN;
            } elseif ($route_param{0} == '@') {
                // if it is a regular expression match

                $type = self::SEP_TYPE_REGEX;
                $route_param = $saved_eregs[$last_ereg];

                if (!$route_param) {
                    continue;
                }

                ++$last_ereg;

                $compare_regex[] = $route_param;

                $new_route .= self::REGEX_START . $route_param . self::REGEX_START;
            } // the rest is text
            else {
                $type = self::SEP_TYPE_TEXT;
                $new_route .= $route_param;

                $compare_regex[] = preg_quote($route_param);
            }

            // add the value to the current route token
            if ($route_param) {
                $this->tokens[] = ['type' => $type, 'value' => $route_param];
                $lastval = $route_param;
            }

            if (!$is_last) {
                $new_route .= $tk;
                $compare_regex[] = str_replace('/', '\/', preg_quote($tk));
            }

            unset($route_param);

            if ($is_last) {
                break;
            }
        }

        // append extension if available
        /*if ($this->ext)
        {
        $compare_regex[] .= "\." . preg_quote($this->ext);
        }
        else
        {
        $compare_regex[] .= "($|\/)";
        }*/

        // make a route check
        /*if (!$this->default_params)
        {
        assert(false);
        return false;
        }*/

        // check for homepage
        $this->compare_regex = "/^\/" . implode("", $compare_regex) . "/iu";

        // set fixed route to current one
        $this->fixed_route = $new_route;

        // set compiled state
        $this->compiled = true;

        // cleanup
        unset($lastval, $new_route, $compare_regex);

        return $this->compare_regex;
    }

    public function getPattern()
    {
        if (!$this->compiled) {
            $this->compile();
        }

        return (string)$this->fixed_route;
    }

    public function getCompareRegex()
    {
        if (!$this->compiled) {
            $this->compile();
        }

        return $this->compare_regex;
    }

    public function getTokens()
    {
        if (!$this->compiled) {
            $this->compile();
        }

        return $this->tokens;
    }

    public function matchesUrl($url, array $context)
    {
        $this->context = $context;

        if (!isset($this->default_params['module'])) {
            $this->default_params['module'] = isset($context['default_module']) ? $context['default_module'] : null;
        }

        if (!isset($this->default_params['action'])) {
            $this->default_params['action'] = isset($context['default_action']) ? $context['default_action'] : null;
        }

        if (!$this->compiled) {
            $this->compile();
        }

        if (!$this->compare_regex) {
            return false;
        }

        $matches = null;

        try {
            $m = preg_match($this->compare_regex, $url, $matches);
        } catch (Exception $e) {
            throw new lcRoutingException('Route failed: ' . $this->fixed_route . ' - ' . $e->getMessage(), null, $e);
        }

        if (!$m) {
            return false;
        }

        $params = $this->params;
        $newparams = [];

        foreach ($matches as $key => $val) {
            if (is_int($key) || !$val || !in_array($key, $params)) {
                continue;
            }

            $newparams[$key] = $val;

            unset($key, $val);
        }

        unset($matches);

        // merge current params and defaults
        // set to default value if param does not have such in the url
        if ($this->default_params) {
            foreach ($this->default_params as $name => $value) {
                if (!isset($newparams[$name]) || !$newparams[$name]) {
                    $newparams[$name] = $value;
                }

                unset($name, $value);
            }
        }

        return $newparams;
    }

    /*
     * Checks if the given URL matches the route
    * and returns the params found
    */

    public function matchesParams(array $params, array $context)
    {
        $this->context = $context;

        if (!isset($this->default_params['module'])) {
            $this->default_params['module'] = $context['default_module'];
        }

        if (!isset($this->default_params['action'])) {
            $this->default_params['action'] = $context['default_action'];
        }

        if (!$this->compiled) {
            $this->compile();
        }

        // check it the route has params
        // if it does not - check against the default params
        // both key, value
        if (!count($this->params)) {
            foreach ($params as $param => $value) {
                if (!isset($this->default_params[$param]) ||
                    $this->default_params[$param] != $value
                ) {
                    return false;
                }

                unset($param, $value);
            }
        }

        $keys = array_keys($params);

        // all parameters must exist (or they must have a default value)
        foreach ($this->params as $param) {
            // if not in the passed params
            if (!in_array($param, $keys)) {
                // check the route's default params
                if (!in_array($param, $this->default_params)) {
                    // failed - return false
                    return false;
                }
            }

            // remove the checked params
            unset($params[$param], $param);
        }

        // if there are default parameters in the passed params
        // it means the user wants to make sure that the route
        // matches them too
        // we must check them
        foreach ($params as $param => $value) {
            if (isset($this->default_params[$param])) {
                if ($this->default_params[$param] != $value) {
                    return false;
                }
            }

            unset($param, $value);
        }

        return true;
    }

    public function generate($params)
    {
        if (!$this->compiled) {
            $this->compile();
        }

        $route = $this->fixed_route;
        $route = str_replace('*', '', $route);
        $route = str_replace('//', '', $route);

        if (!$route) {
            return '/';
        }

        $merged = array_unique(array_merge(array_keys($params), array_keys($this->default_params)));

        foreach ($merged as $param) {
            $route = str_replace(':' . $param, isset($params[$param]) ?
                $params[$param] :
                $this->default_params[$param], $route);

            if (isset($params[$param])) {
                unset($params[$param]);
            }

            unset($param);
        }

        unset($merged);

        if ($route{mb_strlen($route) - 1} == '/') {
            $route = lcUnicode::substr($route, 0, mb_strlen($route) - 1);
        }

        if (!$route) {
            return '/';
        }

        $ret = ($route{0} != '/' ? '/' : null) . $route . ($params ? '?' . http_build_query($params) : null);

        return $ret;
    }

    public function getDefaultParams()
    {
        return $this->default_params;
    }

    public function setDefaultParams(array $default_params = null)
    {
        $this->default_params = $default_params;
    }

    public function __toString()
    {
        return '\'' . (string)$this->route . '\'';
    }
}
