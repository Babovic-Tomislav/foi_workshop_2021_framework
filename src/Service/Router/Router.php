<?php

namespace App\Service\Router;

use App\Service\Request\Request;

class Router
{
    /** @var array|string[]  */
    private array $supportedHttpMethods = array(
        "GET",
        "POST"
    );

    /** @var array  */
    private static $properties = [];

    public static function __callStatic($name, array $args)
    {
        list($route, $method) = $args;

        self::$properties[strtolower($name)][self::formatRoute($route)] = $method;
    }

    public function __construct()
    {
        foreach (self::$properties as $key => $value) {
            foreach ($value as $key2 => $val2) {
                $this->{strtolower($key)}[$key2] = $val2;
            }
        }
    }

    /**
     * Removes trailing forward slashes from the right of the route.
     * @param string (string)
     */
    private static function formatRoute(string $route)
    {
        $result = rtrim(explode('?', $route)[0], '/');
        if ($result === '')
        {
            return '/';
        }
        return $result;
    }

    private function invalidMethodHandler(Request $request)
    {
        header("{$request->serverProtocol} 405 Method Not Allowed");
    }

    private function defaultRequestHandler(Request $request)
    {
        header("{$request->serverProtocol} 404 Not Found");
    }

    function resolve(Request $request)
    {
        $methodDictionary = $this->{strtolower($request->requestMethod)};
        $formatedRoute = $this->formatRoute($request->requestUri);

        $method = $methodDictionary[$formatedRoute];

        if(is_null($method))
        {
            $this->defaultRequestHandler($request);
            return;
        } else if (!in_array($request->requestMethod, $this->supportedHttpMethods)) {
            $this->invalidMethodHandler($request);
            return;
        }

        if ($method instanceof \Closure) {
            echo call_user_func_array($method, array($request));
        } else {
            list($controller, $action) = explode('::', $method);

            return (new $controller)->$action($request);
        }
    }
}