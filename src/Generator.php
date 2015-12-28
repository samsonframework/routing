<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 25.10.15
 * Time: 20:55
 */
namespace samsonframework\routing;

/**
 * Generates routing logic function.
 *
 * @package samsonframework\routing
 */
class Generator
{
    /** RegExp for parsing parameters in pattern placeholder */
    const PARAMETERS_FILTER_PATTERN = '/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i';

    /**
     * Generate routing logic function.
     *
     * @param RouteCollection $routesCollection Routes collection for generating routing logic function
     * @param string $routerFunction Router logic function name
     * @return string PHP code for routing logic
     */
    public function generate(RouteCollection &$routesCollection, $routerFunction = '__router')
    {
        $routeTree = $this->createRoutesArray($routesCollection);

        // Flag for elseif
        $conditionStarted = false;

        /**
         * Iterate found route types and create appropriate router logic function
         * for each route type/method key using specific $routeTree branch
         */
        $routerCallerCode = 'function '.$routerFunction.'($path, $method){' . "\n";
        $routerCallerCode .= '$matches = array();' . "\n";
        foreach ($routeTree as $routeMethod => $routes) {
            $routerCallerCode .= ($conditionStarted? 'else' : '').'if ($method === "' . $routeMethod . '") {' . "\n";
            $routerCallerCode .= $this->recursiveGenerate($routeTree[$routeMethod], '/') . "\n";
            $routerCallerCode .= '}' . "\n";
            $conditionStarted = true;
        }

        return $routerCallerCode . '}';
    }

    /**
     * Convert routes collection into multidimensional array.
     *
     * @param RouteCollection $routesCollection Routes collection for conversion
     * @return array Multi-dimensional array
     */
    protected function &createRoutesArray(RouteCollection &$routesCollection)
    {
        // Create array variable
        $routeTree = array();

        // Build multi-dimensional route array-tree
        foreach ($routesCollection as $route) {
            // Define multi-dimensional route array
            eval($route->toArrayDefinition('$routeTree'));
            //elapsed($route->pattern.' -> ' . $route->toArrayDefinition('$routeTree') . '= $route->identifier;',1);
        }

        return $routeTree;
    }

    /**
     * Generate router logic condition.
     *
     * @param string $newPath
     * @param string $path
     * @param string $placeholder
     * @param mixed $data
     * @param bool $conditionStarted
     * @return string Router logic condition code
     */
    protected function createCondition($newPath, $path, $placeholder, $data, &$conditionStarted = false)
    {
        // Count indexes
        $stLength = strlen($path);
        $length = strlen($placeholder);

        // Check if placeholder is a route variable
        $matches = array();
        if (preg_match(self::PARAMETERS_FILTER_PATTERN, $placeholder, $matches)) {
            // Define parameter filter or use generic
            $filter = isset($matches['filter']) ? $matches['filter'] : '[0-9a-z_]+';

            // Generate parameter route parsing, logic is that parameter can have any length so we
            // limit it either by closest brace(}) to the right or to the end of the string
            $code =  ($conditionStarted ? 'else' : '') . 'if (preg_match("/(?<' . $matches['name'] . '>' . $filter . ')/i", substr($path, ' . $stLength . ',  strpos($path, "/", ' . $stLength . ') ? strlen($path) - strpos($path, "/", ' . $stLength . ') : strlen($path)), $matches)) {' . "\n";
        } else { // No parameters in place holder
            // This is route end - call handler
            if (sizeof($data) === 1 && isset($data[Route::ROUTE_KEY])) {
                $code = ($conditionStarted ? 'else' : '') . 'if ($path === "' . $newPath . '") {' . "\n";
            } else { // Generate route placeholder comparison
                $code = ($conditionStarted ? 'else' : '') . 'if (substr($path, ' . $stLength . ', ' . $length . ') === "' . $placeholder . '" ) {' . "\n";
            }
        }

        $conditionStarted = true;

        return $code;
    }

    /**
     * Create router logic function.
     * This method is recursive
     *
     * @param array $dataPointer Collection of routes or route identifier
     * @param string $path Current route tree path
     * @param string $code Final result
     * @param int $level Recursion level
     * @return string Router logic function
     */
    protected function recursiveGenerate(array &$dataPointer, $path, &$code = '', $level = 1)
    {
        /** @var bool $conditionStarted Flag for creating conditions */
        $conditionStarted = false;

        $foundKey = false;

        // Count left spacing to make code looks better
        $tabs = implode('', array_fill(0, $level, ' '));
        foreach ($dataPointer as $placeholder => $data) {
            // Ignore current logic branch in main loop
            if ($placeholder !== Route::ROUTE_KEY) {
                // All routes should be finished with closing slash
                $newPath = rtrim($path . $placeholder, '/') . '/';

                // Create route logic condition
                $code .= $tabs . $this->createCondition($newPath, $path, $placeholder, $data, $conditionStarted);

                // This is route end, because nested branch has only one key element
                if (sizeof($data) === 1 && isset($data[Route::ROUTE_KEY])) {
                    // Finish route parsing
                    $code .= $tabs . '     return array("' . $data[Route::ROUTE_KEY] . '", $matches);' . "\n";
                } else { // Go deeper in recursion
                    $this->recursiveGenerate($data, $newPath, $code, $level + 5);
                }

                // Close current route condition group
                $code .= $tabs . '}' . "\n";
            } else {
                $foundKey = true;
            }
        }

        // Always add last condition for parent branch if needed
        if ($foundKey) {
            $code .= $tabs . 'else {' . "\n";
            $code .= $tabs . '     return array("' . $dataPointer[Route::ROUTE_KEY] . '", $matches);' . "\n";
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
