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
 * Main routing logic
 * @package samsonframework\routing
 */
class Core
{
    /** @var RouteCollection Collection of all application routes */
    protected $routes = array();

    /**
     * Dispatch HTTP request
     * @param $path
     * @param $routes
     * @param $type
     * @param $method
     * @param null $route
     * @return bool|mixed
     * @throws FailedLogicCreation
     */
    protected function dispatch($path, $method, &$route = null)
    {
        //elapsed('Started dispatching routes');

        // Create routing logic generator
        $generator = new Generator();

        // Generate routing logic from routes
        $routerLogic = $generator->generate($this->routes);

        //file_put_contents(s()->path() . 'www/cache/routing.cache.php', '<?php ' . $routerLogic);
        //elapsed('Created routing logic');

        // Evaluate routing logic function
        eval($routerLogic);
        if (function_exists('__router')) {
            // Perform routing logic
            if (is_array($routeData = __router($path, $this->routes, $method))) {
                //elapsed('Found route');
                /** @var Route $route Retrieve found Route object */
                $route = $routeData[0];

                // Gather parsed route parameters in correct order
                $parameters = array();
                foreach ($route->parameters as $index => $name) {
                    $parameters[] = &$routeData[1][$name];
                }

                // Perform route callback action
                $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : false;

                return isset($result) ? $result : true;
            }
        }

        throw new FailedLogicCreation();
    }
}
