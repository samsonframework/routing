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
     * @throws FailedLogicCreation
     */
    public function __construct(RouteCollection &$routes)
    {
        if (!function_exists('__router')) {
            throw new FailedLogicCreation();
        }

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
            // Add to parameters collection
            $parameters[] = &$receivedParameters[$name];
        }
        return $parameters;
    }

    /**
     * Dispatch HTTP request into callback.
     *
     * @param string $path HTTP request path
     * @param string $method HTTP request method
     * @return null|array Dispatched route metadata
     */
    public function dispatch($path, $method)
    {
        // Perform routing logic
        return __router($path, $method);
    }
}
