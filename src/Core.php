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

        if (function_exists('__router')) {
            // Perform routing logic
            if (is_array($routeData = __router($path, $this->routes, $method))) {
                //elapsed('Found route');
                /** @var Route $route Retrieve found Route object */
                $route = $routeData[0];

                // Gather parsed route parameters in correct order
                $parameters = array();
                foreach ($route->parameters as $name) {
                    $parameters[] = &$routeData[1][$name];
                }

                // Perform route callback action
                $result = is_callable($route->callback)
                    ? call_user_func_array($route->callback, $parameters)
                    : false;

                return isset($result) ? $result : true;
            }
        }

        throw new FailedLogicCreation();
    }
}
