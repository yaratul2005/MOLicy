<?php

namespace Core;

class Router {
    private $routes = [];

    public function get($route, $handler) {
        $this->addRoute('GET', $route, $handler);
    }

    public function post($route, $handler) {
        $this->addRoute('POST', $route, $handler);
    }

    private function addRoute($method, $route, $handler) {
        // Convert route to regex, e.g. /thread/{id} to /thread/(?<id>[a-zA-Z0-9_-]+)
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '(?<$1>[a-zA-Z0-9_-]+)', $route);
        $routeRegex = '#^' . $routeRegex . '/?$#';
        $this->routes[] = [
            'method' => $method,
            'route' => $routeRegex,
            'handler' => $handler
        ];
    }

    public function dispatch($uri, $method) {
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Remove base directory if needed (for localhost subdirectories)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        if (empty($uri)) $uri = '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['route'], $uri, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_callable($route['handler'])) {
                    return call_user_func_array($route['handler'], $params);
                }

                if (is_string($route['handler'])) {
                    list($controllerClass, $methodName) = explode('@', $route['handler']);
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            return call_user_func_array([$controller, $methodName], $params);
                        }
                    }
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 Not Found";
    }
}
