<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 25.10.15
 * Time: 20:55
 */
namespace samsonframework\routing;

/**
 * Class generates routing logic function
 * @package samsonframework\routing
 */
class Generator
{
    /**
     * Generate routing logic function
     * @param RouteCollection $routesCollection Routes collection for generating routing logic function
     * @return string PHP code for routing logic
     */
    public function generate(RouteCollection &$routesCollection)
    {
        $routeTree = $this->createRoutesArray($routesCollection);

        $conditionStarted = false;

        /**
         * Iterate found route types and create appropriate router logic function
         * for each route type/method key using specific $routeTree branch
         */
        $routerCallerCode = 'function __router($path, &$routes, $method){' . "\n";
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
     * Create router logic function.
     * This method is recursive
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

        // Count left spacing to make code looks better
        $tabs = implode('', array_fill(0, $level, ' '));
        foreach ($dataPointer as $placeholder => $data) {
            // All routes should be finished with closing slash
            $newPath = rtrim($path . $placeholder, '/') . '/';

            // Add route description as a comment
            $code .= $tabs . '// ' . $newPath . "\n";

            // Count indexes
            $stLength = strlen($path);
            $stIndex = $stLength;
            $length = strlen($placeholder);

            // Check if placeholder is a route variable
            if (preg_match('/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i', $placeholder, $matches)) {
                // Define parameter filter or use generic
                $filter = isset($matches['filter']) ? $matches['filter'] : '[0-9a-z_]+';

                // Generate parameter route parsing, logic is that parameter can have any length so we
                // limit it either by closest brace(}) to the right or to the end of the string
                $code .= $tabs . 'if (preg_match("/(?<' . $matches['name'] . '>' . $filter . ')/i", substr($path, ' . $stIndex . ',  strpos($path, "/", ' . $stLength . ') ? strlen($path) - strpos($path, "/", ' . $stLength . ') : 0), $matches)) {' . "\n";

                // When we have route parameter we do not split logic tree as different parameters can match
                $conditionStarted = false;
            } else {
                // This is route end - call handler
                if (is_string($data)) {
                    $code .= $tabs . ($conditionStarted ? 'else' : '') . 'if ($path === "' . $newPath . '") {' . "\n";
                } else { // Generate route placeholder comparison
                    $code .= $tabs . ($conditionStarted ? 'else' : '') . 'if (substr($path, ' . $stIndex . ', ' . $length . ') === "' . $placeholder . '" ) {' . "\n";
                }
                // Flag that condition group has been started
                $conditionStarted = true;
            }

            // This is route end - call handler
            if (is_array($data) && sizeof($data) === 1) {
                // Finish route parsing
                $code .= $tabs . '     return array($routes["' . $data[0] . '"], $matches);' . "\n";
            } else { // Go deeper in recursion
                $this->recursiveGenerate($data, $newPath, $code, $level + 5);
            }

            // Close current route condition group
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
