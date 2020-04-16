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

class lcPatternRouting extends lcRouting implements iRouteBasedRouting, iCacheable, iDebuggable
{
    protected $local_cache;
    protected $cache_key;
    protected $routes_are_cached;
    /** @var lcWebRequest */
    protected $request;
    protected $context;
    /** @var lcRouteCollection */
    private $routes;
    /** @var lcNamedRoute */
    private $current_route;

    private $current_internal_uri;

    /** @var array */
    private $current_params;

    public function initialize()
    {
        parent::initialize();

        $this->request = $this->event_dispatcher->provide('loader.request', $this)->getReturnValue();

        $this->context = $this->request->getRequestContext();
        $this->context['default_module'] = $this->default_module;
        $this->context['default_action'] = $this->default_action;

        // init local cache
        $this->local_cache = $this->configuration->getCache();
        $this->cache_key = $this->configuration->getUniqueId() . '_pattern_routing';

        // first - set the necessary routes
        if (!$this->routes_are_cached) {
            $this->setConfigRoutes();
        }

        // allow others to be notified when base routes have been loaded
        $this->event_dispatcher->notify(new lcEvent('router.load_configuration', $this, [
            'routes' => $this->routes,
            'context' => $this->context]));

        // init detected params after all routes have been loaded
        $this->detectParameters();
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

    public function connect(lcNamedRoute $route)
    {
        // do not allow routes changing after class cache has loaded them
        if ($this->routes_are_cached) {
            return;
        }

        $this->routes->append($route);

        if (DO_DEBUG) {
            $this->debug(sprintf('Connect %-25s %s', $route->getName() . ':', $route->getRoute()));
        }
    }

    private function detectParameters()
    {
        $url = isset($this->context['path_info']) ? (string)$this->context['path_info'] : null;

        // allow others to filter and provider params before the router
        $evn = $this->event_dispatcher->filter(new lcEvent('router.before_parse_url', $this, [
            'url' => $url,
            'context' => $this->context,
        ]), [
            'url' => $url,
            'context' => $this->context,
        ]);

        if ($evn->isProcessed()) {
            $rv = $evn->getReturnValue();
            $this->context = $rv['context'];
            $url = $rv['url'];

            if (isset($rv['resolved_params'])) {
                return $rv['resolved_params'];
            }
        }

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

        return null;
    }

    public function findMatchingRoute($url)
    {
        return $this->getRouteThatMatchesUrl($url);
    }

    protected function getRouteThatMatchesUrl($url)
    {
        $url = $this->normalizeUrl($url);

        if (!$url) {
            return null;
        }

        $context = $this->context;
        $all = $this->routes->getAll();

        if (!$all) {
            return null;
        }

        /** @var lcNamedRoute $route */
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
                $methods = isset($route_options['methods']) ? (array)$route_options['methods'] : [];

                if (isset($route_options['method']) && (string)$route_options['method']) {
                    $methods[] = $route_options['method'];
                }

                $found_match = false;

                foreach ($methods as $method) {
                    $method = strtolower($method);

                    $is_valid = !(($method == 'get' && !$this->request->isGet()) ||
                        ($method == 'post' && !$this->request->isPost()) ||
                        ($method == 'put' && !$this->request->isPut()) ||
                        ($method == 'delete' && !$this->request->isDelete()));

                    if (!$is_valid) {
                        continue;
                    }

                    $found_match = true;

                    unset($method);
                }

                if (!$found_match) {
                    continue;
                }

                unset($route_options);
            }

            $route = [
                'name' => $route->getName(),
                'pattern' => $route->getPattern(),
                'params' => $params,
                'options' => $route->getOptions(),
                'route' => $route,
            ];

            return $route;
        }

        // at this point no route matched
        // return the last available route as the default one (lowest priority one)
        $route = count($all) ? $all[count($all) - 1] : null;

        $route = $route ? [
            'name' => $route->getName(),
            'pattern' => $route->getPattern(),
            'params' => $route->getDefaultParams(),
            'options' => $route->getOptions(),
            'route' => $route,
        ] : null;

        return $route;
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
        $path = (explode('?', $path));

        if (!$path || !is_array($path)) {
            return false;
        }

        $path = $path[0];

        // strip last /
        $l = mb_strlen($path);

        if ($l > 1 && mb_substr($path, $l - 1, $l) == '/') {
            $path = mb_substr($path, 0, $l - 1);
        }

        $l = mb_strlen($path);

        // add first
        if ($l > 0 && $path[0] != '/') {
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

    protected function updateCurrentInternalUri($name, array $params = null)
    {
        $p = [];

        assert(isset($this->current_route));

        if ($params) {
            $module = isset($params['module']) ? $params['module'] : null;
            $action = isset($params['action']) ? $params['action'] : null;

            $internal_uri = [
                '@@' . $this->current_route->getName(),
            ];

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
            $internal_uri = ['@@' . $name, ''];
        }

        $p = $p ? '?' . implode('&', $p) : '';

        $uri = [$internal_uri[0] . $p, $internal_uri[1] . $p];

        $this->current_internal_uri = $uri;
    }

    public function getContext()
    {
        return $this->context;
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

        $debug = [
            'routes_are_cached' => $this->routes_are_cached,
            'current_route' => ($this->current_route ? $this->current_route->getName() : null),
            'current_internal_uri' => (isset($this->current_internal_uri) ? $this->current_internal_uri[1] : null),
            'current_params' => $this->current_params,
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
    }

    public function getShortDebugInfo()
    {
        $debug_parent = (array)parent::getShortDebugInfo();

        $debug = [
            'current_route' => ($this->current_route ? $this->current_route->getName() : null),
            'current_internal_uri' => (isset($this->current_internal_uri) ? $this->current_internal_uri[1] : null),
        ];

        $debug = array_merge($debug_parent, $debug);

        return $debug;
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

    public function generate(array $params = null, $absolute = false, $name = null, $append_prefix = true)
    {
        $ret = null;

        $params = array_filter($params);

        // if no params given - return the current request uri
        if (!count($params)) {
            /** @noinspection PhpUnusedLocalVariableInspection */
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
                /** @noinspection PhpUnusedLocalVariableInspection */
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
        // TODO: Remove this prefix completely as it's causing lots of pain!
        $prefix = isset($this->context['prefix']) ? $this->context['prefix'] : null;

        if ($append_prefix) {
            $ret = $prefix . $ret;
        }

        $evn = $this->event_dispatcher->filter(new lcEvent('router.generate_url', $this, [
            'url' => $ret,
            'params' => $params,
            'absolute' => $absolute,
            'route_name' => $name,
            'prefix' => $prefix,
            'context' => $this->context,
        ]), $ret);

        if ($evn->isProcessed()) {
            $ret = $evn->getReturnValue();
        }

        return $ret;
    }

    public function getRouteThatMatchesParams(array $params)
    {
        if (!$this->routes) {
            return false;
        }

        $all = $this->routes->getAll();

        foreach ($all as $name => $route) {
            if ($route->matchesParams($params, $this->context)) {
                return $route;
            }

            unset($name, $route);
        }

        unset($all);

        return false;
    }

    public function prependRoute(lcNamedRoute $route)
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
            $this->appendRoute($route);
            return;
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
    }

    public function appendRoute(lcNamedRoute $route)
    {
        // do not allow routes changing after class cache has loaded them
        if ($this->routes_are_cached) {
            return;
        }

        // if caching is enabled compile route right away
        if ($this->local_cache) {
            $route->reCompile();
        }

        $this->connect($route);
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
            return;
        }

        $this->routes->clear();
    }

    #pragma mark - Class Cache

    public function writeClassCache()
    {
        return [
            'routes' => $this->routes,
        ];
    }

    public function readClassCache(array $cached_data)
    {
        $this->routes = isset($cached_data['routes']) ? $cached_data['routes'] : null;

        if ($this->routes) {
            $this->routes_are_cached = true;
        }
    }
}
