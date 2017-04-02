<?php declare(strict_types = 1);
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
    /** Generic name of routing logic function */
    const ROUTING_LOGIC_FUNCTION = '__router';

    /** @var RouteCollection Collection of all application routes */
    protected $routes;

    /**
     * Core constructor.
     *
     * @param RouteCollection $routes Routes collection for dispatching
     * @throws FailedLogicCreation
     */
    public function __construct(RouteCollection $routes)
    {
        if (!function_exists(self::ROUTING_LOGIC_FUNCTION)) {
            throw new FailedLogicCreation();
        }

        $this->routes = $routes;
    }

    /**
     * Dispatch HTTP request into callback.
     *
     * @param string $path HTTP request path
     * @param string $method HTTP request method
     * @return array Dispatched route metadata
     */
    public function dispatch($path, $method): array
    {
        // Perform routing logic
        return call_user_func(self::ROUTING_LOGIC_FUNCTION, $path, $method);
    }
}
