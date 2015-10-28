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
    public function generate(RouteCollection & $routesCollection)
    {
        $routeTree = $this->createRoutesArray($routesCollection);

        /**
         * Iterate found route types and create appropriate router logic function
         * for each route type/method key using specific $routeTree branch
         */
        $routerCallerCode = 'function __router($path, & $routes, $method){' . "\n";
        $routerCallerCode .= '$matches = array();' . "\n";
        foreach ($routeTree as $routeMethod => $routes) {
            $routerCallerCode .= 'if ($method === "' . $routeMethod . '") {' . "\n";
            $routerCallerCode .= $this->recursiveGenerate($routeTree[$routeMethod], '') . "\n";
            $routerCallerCode .= '}' . "\n";
        }

        return $routerCallerCode . '}';
    }

    /**
     * Convert routes collection into multidimensional array.
     * @param RouteCollection $routesCollection Routes collection for conversion
     * @return array Multi-dimensional array
     */
    protected function & createRoutesArray(RouteCollection & $routesCollection)
    {
        // Build multi-dimensional route array-tree
        $arrayDefinition = '';
        foreach ($routesCollection as $route) {
            $arrayDefinition .= $route->toArrayDefinition('$routeTree');
            //elapsed($route->pattern.' -> $routeTree' . $route->toArrayDefinition('$routeTree') . '= $route->identifier;',1);
        }

        // Create array variable
        $routeTree = array();
        // Define multi-dimensional route array
        eval($arrayDefinition);

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
            // Concatenate path
            $newPath = $path . '/' . $placeholder;

            // Add route description as a comment
            $code .= $tabs . '// ' . $newPath . "\n";

            // Count indexes
            $stLength = strlen($path);
            $stIndex = $placeholder == '/' ? 0 : $stLength + 1;
            $length = strlen($placeholder);

            // Check if placeholder is a route variable
            if (preg_match('/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i', $placeholder, $matches)) {
                // Define parameter filter or use generic
                $filter = isset($matches['filter']) ? $matches['filter'] : '[0-9a-z_]+';

                // Generate parameter route parsing, logic is that parameter can have any length so we
                // limit it either by closest brace(}) to the right or to the end of the string
                $code .= $tabs . 'if (preg_match("/(?<' . $matches['name'] . '>' . $filter . ')/i", substr($path, ' . $stIndex . ',  strpos($path, "/", ' . $stLength . ') ? strlen($path) - strpos($path, "/", ' . $stLength . ') : 0), $matches)) {' . "\n";

                //$code .= $tabs . 'trace("I am at ' . $path . '", 1);';
                // When we have route parameter we do not split logic tree as different parameters can match
                $conditionStarted = false;
            } else { // Generate route placeholder comparison
                $code .= $tabs . ($conditionStarted ? 'else ' : '') . 'if (substr($path, ' . $stIndex . ', ' . $length . ') === "' . $placeholder . '" ) {' . "\n";
                //$code .= $tabs . 'trace("I am at ' . $path . '", 1);';
                // Flag that condition group has been started
                $conditionStarted = true;
            }

            // This is route end - call handler
            if (is_string($data)) {
                $code .= $tabs . '     return array($routes["' . $data . '"], $matches);' . "\n";
            } else { // Go deeper in recursion
                $this->recursiveGenerate($data, $newPath, $code, $level + 5);
            }

            // Close current route condition group
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
