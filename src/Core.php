<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonframework\routing;

use samsonframework\routing\exception\FailedLogicCreation;

/**
 * Main routing logic.
 *
 * @package samsonframework\routing
 */
class Core
{
    /** @var RouteCollection Collection of all application routes */
    protected $routes = array();

    /**
     * Core constructor.
     *
     * @param RouteCollection $routes Routes collection for dispatching
     */
    public function __construct(RouteCollection &$routes)
    {
        $this->routes = $routes;
    }

    /**
     * Parse route parameters received from ruter logic function.
     *
     * @param Route $route Route instance
     * @param array $receivedParameters Collection of parsed parameters
     * @return array Collection of route callback needed parameters
     */
    protected function parseParameters(Route $route, array $receivedParameters)
    {
        // Gather parsed route parameters in correct order
        $parameters = array();
        foreach ($route->parameters as $name) {
            $parameters[] = $receivedParameters[$name];
        }
        return $parameters;
    }

    /**
     * Dispatch HTTP request into callback.
     *
     * @param string $path HTTP request path
     * @param string $method HTTP request method
     * @param Route|null $route Found Route instance
     * @return bool|mixed
     * @throws FailedLogicCreation
     */
    protected function dispatch($path, $method, &$route = null)
    {
        //elapsed('Started dispatching routes');
        $result = false;

        // Remove GET parameters
        $path = strtok($path, '?');

        if (function_exists('__router')) {
            // Perform routing logic
            if (is_array($routeData = __router($path, $method))) {
                //elapsed('Found route');
                /** @var Route $route Retrieve found Route object */
                $route = $this->routes[$routeData[0]];

                // Perform route callback action
                $result = is_callable($route->callback)
                    ? call_user_func_array($route->callback, $this->parseParameters($route, $routeData[1]))
                    : false;
            }

            return isset($result) ? $result : true;
        } else {
            throw new FailedLogicCreation();
        }
    }
}
