<?php
namespace Drumon;

/**
* Router
*/
class Router
{
    private $request_method;
    private $request_path;
    private $format;

    private $current_route_data;

    public function __construct($request_method, $request_path)
    {
        $this->request_method = $request_method;
        $this->request_path   = $request_path;
    }

    public function match($routes)
    {
        if (isset($routes['MODULE']) && $module_routes = $this->getModuleRoutes($routes['MODULE'])) {
            $routes = $module_routes;
        }

        $route_list = array();

        // Get generic routes
        if (isset($routes['*'])) {
            $route_list = $routes['*'];
        }

        // Merge routes with HTTP request method routes
        if (isset($routes[$this->request_method])) {
            $route_list = array_merge($route_list, $routes[$this->request_method]);
        }

        return $this->searchRoute($route_list);
    }

    private function searchRoute($routes)
    {
        if (isset($routes[$this->request_path])) {
            $this->current_route_data = $routes[$this->request_path];

            return true;
        }

        if (isset($routes[$this->request_path . '/'])) {
            $this->current_route_data = $routes[$this->request_path . '/'];

            return true;
        }

        // Variables routes

        foreach ($routes as $route_path => $route_data) {
            // If dont have params then try next route
            if (strpos($route_path, '{') === false) {
                continue;
            }

            // Get route variables
            preg_match_all('/{([\w]+)}/', $route_path, $params_name, PREG_PATTERN_ORDER);

            $route_regex = str_replace('/','\/', $route_path);
            foreach ($params_name[0] as $name) {
                $regex = '[.a-zA-Z0-9_\+\-%]';

                if (isset($route_data['conditions'][$name])) {
                    $regex = $route_data['conditions'][$name];
                }

                $route_regex = str_replace($name, '('.$regex.'*)', $route_regex);
            }
            $route_regex .= '$';

            if (preg_match('/'.$route_regex.'/' , $this->request_path, $matches) === 1) {
                // Remove first value on match. (dont need this value)
                array_shift($matches);

                if (!isset($route_data['params'])) {
                    $route_data['params'] = array();
                }

                foreach ($params_name[1] as $key => $value) {
                    $route_data['params'][$value] = $matches[$key];
                }

                $this->current_route_data = $route_data;

                return true;
            }
        }

        return false;
    }

    public function getModuleRoutes($modules)
    {
        $paths = explode('/', $this->request_path);

        if (count($paths) == 1) {
            return array();
        }

        $first_path = '/' . $paths[1];

        if ( isset($modules[$first_path]) ) {
            $this->request_path = substr($this->request_path, strlen($first_path));

            return $this->includeModuleRoutes($modules[$first_path]);
        }

        if ( isset($modules[$first_path . '/']) ) {
            $this->request_path = substr($this->request_path, strlen($first_path));

            return $this->includeModuleRoutes($modules[$first_path . '/']);
        }

        return array();
    }

    protected function includeModuleRoutes($route_path)
    {
        return include $route_path;
    }

    public function getParams()
    {
        if (isset($this->current_route_data['params'])) {
            return $this->current_route_data['params'];
        }

        return array();
    }

    public function getData()
    {
        return $this->current_route_data;
    }

    public static function getNamedRoute($name, $routes)
    {
        $names = explode('/', $name);

        if (isset($names[1])) {
            $routes = include $routes['MODULE']['/'.$names[0]];
            $name = $names[1];
        } else {
            $name = $names[0];
        }

        if (isset($routes['NAME'][$name])) {
            return $routes['NAME'][$name];
        }

        return null;
    }
}
