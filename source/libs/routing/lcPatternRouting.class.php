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
 * @changed $Id: lcPatternRouting.class.php 1544 2014-06-21 06:14:47Z mkovachev $
 * @author $Author: mkovachev $
 * @version $Revision: 1544 $
 */
class lcPatternRouting extends lcRouting implements iRouteBasedRouting, iCacheable, iDebuggable
{
    protected $local_cache;
    protected $cache_key;
    protected $routes_are_cached;

    private $routes;

    private $current_route;
    private $current_internal_uri;
    private $current_params;

    public function initialize()
    {
        parent::initialize();

        // init local cache
        $this->local_cache = $this->configuration->getCache();
        $this->cache_key = $this->configuration->getUniqueId() . '_pattern_routing';

        // first - set the necessary routes
        if (!$this->routes_are_cached) {
            $this->setConfigRoutes();
        }

        // allow others to be notified when base routes have been loaded
        $this->event_dispatcher->notify(new lcEvent('router.load_configuration', $this, array(
            'routes' => $this->routes,
            'context' => $this->context)));

        // init detected params after all routes have been loaded
        $this->detectParameters();
    }

    public function shutdown()
    {
        $this->routes =
        $this->current_route =
        $this->current_internal_uri =
        $this->current_params =
            null;

        parent::shutdown();
    }

    public function getDebugInfo()
    {
        $debug_parent = (array)parent::getDebugInfo();

        $debug = array(
            'routes_are_cached' => $this->routes_are_cached,
            'current_route' => ($this->current_route ? $this->current_route->getName() : null),
            'current_internal_uri' => (isset($this->current_internal_uri) ? $this->current_internal_uri[1] : null),
            'current_params' => $this->current_params
        );

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug_parent = (array)parent::getShortDebugInfo();

        $debug = array(
            'current_route' => ($this->current_route ? $this->current_route->getName() : null),
            'current_internal_uri' => (isset($this->current_internal_uri) ? $this->current_internal_uri[1] : null),
        );

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    private function detectParameters()
    {
        $url = isset($this->context['path_info']) ? (string)$this->context['path_info'] : null;

        // detect the params
        $info = $this->findMatchingRoute($url);

        if ($info !== false) {
            if (DO_DEBUG) {
                $this->debug('Matched ' . $info['name'] . ': ' . $info['pattern'] . ' => ' . $url .
                    ' (' . str_replace("\n", '', var_export($info['params'], true)));
            }
        } else {
            $this->warn('Cannot detect route for: ' . $url);
        }

        if ($info) {
            assert(isset($info['params']));
            assert(isset($info['route']));

            $params = isset($info['params']) ? (array)$info['params'] : null;

            $this->current_params = $params;
            $this->current_route = $info['route'];

            // store the current internal URI
            $this->updateCurrentInternalUri($info['name'], $params);
        }

        // send a notification
        $result = $info;
        $result['default_module'] = $this->default_module;
        $result['default_action'] = $this->default_action;

        $this->event_dispatcher->notify(new lcEvent('router.detect_parameters', $this, $result));
    }

    public function getParamsByCriteria($criteria)
    {
        $url = isset($criteria['url']) ? (string)$criteria['url'] : null;

        if (!$url) {
            throw new lcInvalidArgumentException('Missing URL in criteria');
        }

        $info = $this->getRouteThatMatchesUrl($url);

        if (!$info) {
            return false;
        }

        assert(isset($info['params']));

        return $info['params'];
    }

    public function getParams()
    {
        return $this->current_params;
    }

    public function getCurrentInternalUri($with_route_name = false)
    {
        return is_null($this->current_route) ? null :

            $this->current_internal_uri[$with_route_name ? 0 : 1];
    }

    public function getRoute()
    {
        return $this->current_route;
    }

    protected function setConfigRoutes()
    {
        assert(!$this->routes_are_cached);

        $this->routes = new lcRouteCollection;

        $routes = $this->configuration['routing.routes'];

        if ($routes) {
            foreach ($routes as $name => $route) {
                $requirements = isset($route['requirements']) ? (array)$route['requirements'] : null;
                $url = isset($route['url']) ? (string)$route['url'] : null;
                $params = isset($route['params']) ? (array)$route['params'] : null;
                $options = isset($route['options']) ? (array)$route['options'] : null;

                if (!$url) {
                    assert(false);
                    continue;
                }

                $route2 = new lcNamedRoute();
                $route2->setRequirements($requirements);
                $route2->setRoute($url);
                $route2->setName($name);
                $route2->setDefaultParams($params);
                $route2->setOptions($options);

                // if caching is enabled compile route right away
                if ($this->local_cache) {
                    $route2->reCompile();
                }

                $this->connect($route2);

                unset($name, $route, $route2, $requirements, $url, $params, $options);
            }

            unset($routes);
        }
    }

    public function generate(array $params = null, $absolute = false, $name = null)
    {
        fnothing($absolute);
        $ret = null;

        $params = array_filter($params);

        // if no params given - return the current request uri
        if (!count($params)) {
            $ret = $this->context['request_uri'];
        }

        // if a route name is given - load it
        if (isset($name)) {
            if (!isset($this->routes[$name])) {
                throw new lcRoutingException('Cannot generate URL - Route not found: ' . $name);
            }

            $route = $this->routes[$name];
        } // if not - find a route that matches the given params
        else {
            if (!$route = $this->getRouteThatMatchesParams($params)) {
                $ret = $this->context['request_uri'];
            }
        }

        // pass the url generation to the route
        if (count($params) && $route) {
            // route will generate the url
            if (!$ret = $route->generate($params)) {
                $ret = $this->context['request_uri'];
            }
        } // if not found - return the current url
        else {
            $ret = $this->context['request_uri'];
        }

        // append request prefix
        $prefix = isset($this->context['prefix']) ? $this->context['prefix'] : null;
        $ret = $prefix . $ret;

        return $ret;
    }

    public function connect(lcRoute $route)
    {
        // do not allow routes changing after class cache has loaded them
        if ($this->routes_are_cached) {
            return;
        }

        $ret = $this->routes->append($route);

        if (DO_DEBUG) {
            $this->debug(sprintf('Connect %-25s %s', $route->getName() . ':', $route->getRoute()));
        }

        return $ret;
    }

    public function prependRoute(lcRoute $route)
    {
        // do not allow routes changing after class cache has loaded them
        if ($this->routes_are_cached) {
            return;
        }

        // if caching is enabled compile route right away
        if ($this->local_cache) {
            $route->reCompile();
        }

        $prepend_route = $route;

        if (!$this->routes->count()) {
            return $this->appendRoute($route);
        }

        $newroutes = new lcRouteCollection();
        $newroutes->append($route);

        $all = $this->routes->getAll();

        foreach ($all as $route) {
            $newroutes->append($route);
            unset($route);
        }

        unset($all);

        $this->routes = $newroutes;
        unset($newroutes);

        if (DO_DEBUG) {
            $this->debug(sprintf('Prepend %-25s %s', $prepend_route->getName() . ':', $prepend_route->getRoute()));
        }

        return true;
    }

    public function appendRoute(lcRoute $route)
    {
        // do not allow routes changing after class cache has loaded them
        if ($this->routes_are_cached) {
            return;
        }

        // if caching is enabled compile route right away
        if ($this->local_cache) {
            $route->reCompile();
        }

        return $this->connect($route);
    }

    private function normalizeUrl($url)
    {
        $url = (string)$url;

        if (!$url) {
            $url = '/';
        }

        // strip out protocol / domain
        $path = preg_replace("/(http|https)\:\/\/([\w\d].*?)(\/|$)/iu", '', $url);

        // strip out the script filename if in the url
        $scr_filename = isset($_SERVER['SCRIPT_FILENAME']) ? ('/' . basename((string)$_SERVER['SCRIPT_FILENAME'])) : null;

        if (lcStrings::startsWith($path, $scr_filename)) {
            $l1 = strlen($scr_filename);
            $path = '/' . substr($path, $l1, strlen($path) - $l1);
        }

        // strip out query string
        $path = array_filter(explode('?', $path));

        if (!$path || !is_array($path)) {
            return false;
        }

        $path = $path[0];

        // strip last /
        $l = mb_strlen($path);

        if ($l > 1 && $path{$l - 1} == '/') {
            $path = mb_substr($path, 0, $l - 1);
        }

        $l = mb_strlen($path);

        // add first
        if ($l > 0 && $path{0} != '/') {
            $path = '/' . $path;
        } else if ($l == 0) {
            $path = '/';
        }

        // remove the url path prefix if it exists
        $context = $this->context;

        if (isset($context['prefix']) && $context['prefix'] && substr($path, 0, strlen($context['prefix'])) == $context['prefix']) {
            $path = substr($path, strlen($context['prefix']), strlen($path));
        }

        $path = $path ? $path : '/';

        return $path;
    }

    public function findMatchingRoute($url)
    {
        $res = $this->getRouteThatMatchesUrl($url);

        return $res;
    }

    public function getRouteThatMatchesParams(array $params)
    {
        if (!$this->routes) {
            return false;
        }

        $all = $this->routes->getAll();

        foreach ($all as $name => $route) {
            if ($route->matchesParams($params, $this->getContext())) {
                return $route;
            }

            unset($name, $route);
        }

        unset($all);

        return false;
    }

    protected function getRouteThatMatchesUrl($url)
    {
        $url = $this->normalizeUrl($url);

        if (!$url) {
            return;
        }

        $context = $this->getContext();
        $all = $this->routes->getAll();

        if (!$all) {
            return false;
        }

        foreach ($all as $route) {
            if (false === $params = $route->matchesUrl($url, $context)) {
                continue;
            }

            if (!isset($params['module']) || !isset($params['action'])) {
                throw new lcRoutingException('Invalid route detected. Missing module/action defaults and/or url match. Route: ' . $route->getName());
            }

            // check additional options provided by route if any
            $route_options = $route->getOptions();

            // ajax_request_only - should match only if request is ajax based (XML-HTTP)
            if ($route_options) {
                // ajax based requests only
                if (isset($route_options['ajax_request_only']) && (bool)$route_options['ajax_request_only']) {
                    if (!$this->request->isAjax()) {
                        // not an ajax request - skip this route
                        continue;
                    }
                }

                // method
                if (isset($route_options['method']) && (string)$route_options['method']) {
                    $checkfor = strtolower($route_options['method']);

                    if ($checkfor == 'get' && !$this->request->isGet()) {
                        // not a get - continue
                        continue;
                    } elseif ($checkfor == 'post' && !$this->request->isPost()) {
                        // not a post - continue
                        continue;
                    }
                }

                unset($route_options);
            }

            $route = array(
                'name' => $route->getName(),
                'pattern' => $route->getPattern(),
                'params' => $params,
                'options' => $route->getOptions(),
                'route' => $route
            );

            return $route;
        }

        // at this point no route matched
        // return the last available route as the default one (lowest priority one)
        $route = $all[count($all) - 1];

        $route = array(
            'name' => $route->getName(),
            'pattern' => $route->getPattern(),
            'params' => $route->getDefaultParams(),
            'options' => $route->getOptions(),
            'route' => $route
        );

        return $route;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function hasRoutes()
    {
        return $this->routes->count() ? true : false;
    }

    public function clearRoutes()
    {
        // do not allow adding routes when
        // caching is enabled and routes are cached
        if ($this->routes_are_cached) {
            return false;
        }

        return $this->routes->clear();
    }

    protected function updateCurrentInternalUri($name, array $params = null)
    {
        $p = array();

        assert(isset($this->current_route));

        if ($params) {
            $module = isset($params['module']) ? $params['module'] : null;
            $action = isset($params['action']) ? $params['action'] : null;

            $internal_uri = array(
                '@@' . $this->current_route->getName(),
            );

            if ($module && $action) {
                $internal_uri[] = $module . '/' . $action;
            }

            unset($params['module'], $params['action']);

            foreach ($params as $key => $val) {
                $p[] = $key . '=' . $val;
                unset($key, $val);
            }

            // make unique
            sort($p);
        } else {
            $internal_uri = array('@@' . $name, '');
        }

        $p = $p ? '?' . implode('&', $p) : '';

        $uri = array($internal_uri[0] . $p, $internal_uri[1] . $p);

        $this->current_internal_uri = $uri;
    }

    #pragma mark - Class Cache

    public function writeClassCache()
    {
        $cached_data = array(
            'routes' => $this->routes
        );

        return $cached_data;
    }

    public function readClassCache(array $cached_data)
    {
        $this->routes = isset($cached_data['routes']) ? $cached_data['routes'] : null;

        if ($this->routes) {
            $this->routes_are_cached = true;
        }
    }
}

?>