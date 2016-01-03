<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 25.10.15
 * Time: 20:55
 */
namespace samsonframework\routing;

use samsonframework\routing\generator\Structure;

/**
 * Generates routing logic function.
 *
 * @package samsonframework\routing
 */
class Generator
{
    /**
     * Generate routing logic function.
     *
     * @param RouteCollection $routesCollection Routes collection for generating routing logic function
     * @param string $routerFunction Router logic function name
     * @return string PHP code for routing logic
     */
    public function generate(RouteCollection &$routesCollection, $routerFunction = '__router')
    {
        $structure = new Structure($routesCollection, new \samsonphp\generator\Generator());

        $routeTree = $this->createRoutesArray($routesCollection);

        // Flag for elseif
        $conditionStarted = false;

        /**
         * Iterate found route types and create appropriate router logic function
         * for each route type/method key using specific $routeTree branch
         */
        $routerCallerCode = 'function '.$routerFunction.'($path, $method){' . "\n";
        $routerCallerCode .= '$matches = array();' . "\n";
        $routerCallerCode .= '$parameters = array();' . "\n";
        $routerCallerCode .= '$originalPath = $path;' . "\n";
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

        $routesCollection->sort();

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
     * @param string $placeholder
     * @param mixed $data
     * @param bool $conditionStarted
     * @return string Router logic condition code
     */
    protected function createCondition($newPath, $tabs, $placeholder, $data, &$conditionStarted = false, &$parameterOffset = null, $level = 1)
    {
        $code = '';

        // This limit condition for first iteration - needs to be fixed
        $stLength = $placeholder !== '/' ? strlen($newPath) : 0;
        // If we have cut $path on previous iteration
        $stLength = !isset($parameterOffset) ? $stLength : $parameterOffset;
        $length = strlen($placeholder);

        // Flag showing if this is a last condition in logic tree branch
        $lastCondition = sizeof($data) === 1 && isset($data[Route::ROUTE_KEY]);

        // Function call for getting current path in router logic
        $currentString = $stLength > 0 ? 'substr($path, ' . $stLength . ')' : '$path';

        // Check if placeholder is a route variable
        $matches = array();
        if (preg_match(Route::PARAMETERS_FILTER_PATTERN, $placeholder, $matches)) {
            // Define parameter filter or use generic
            $filter = isset($matches['filter']) ? $matches['filter'] : '[^\/]+';

            // If we have returned to top level - restore path
            if ($level === 1) {
                // Restore path before top level parameter parsing
                $code .= '$path = $originalPath;'."\n";
            }

            // Generate parameter route parsing, logic is that parameter can have any length so we
            // limit it either by closest brace(}) to the right or to the end of the string
            $code .= $tabs . 'if (preg_match("/(?<' . $matches['name'] . '>' . $filter .')/i", '.$currentString.', $matches)) {'. "\n";

            // Define parsed parameter value
            $code .= $tabs . '     $parameters["'.$matches['name'].'"] = $matches["'.$matches['name'].'"];'."\n";

            // As we have parameters and we need to change $path for possible inner conditions
            $code .= $tabs . '     $path = substr($path, '.$stLength.' + strlen($matches["' . $matches['name'] . '"]) + 1);' . "\n";

            // Check last condition for routes ending with parameters
            if ($lastCondition) {
                // Check if nothing left in path as we reached logic end and route should end on this
                // branch as nothing should be more
                $code .= $tabs . '     if (strlen($path) !== 0 || $path !== false) { return null; }'."\n";
            } else {
                // Set new offset value for path as we now removed dynamic parameter from it
                $parameterOffset = 0;
            }

            // End condition upon parameters
            $conditionStarted = false;

        } else { // No parameters in place holder
            // This is route end - call handler
            if ($lastCondition) {
                $code = $tabs . ($conditionStarted ? 'else' : '') . 'if ($path  === "' . $placeholder . '") {' . "\n";
            } else { // Generate route placeholder comparison
                $code = $tabs . ($conditionStarted ? 'else' : '') . 'if (substr($path, ' . $stLength . ', ' . $length . ') === "' . $placeholder . '") {' . "\n";
                $code .= $tabs . '     $path = substr($path, ' . ($stLength+$length+1) . ');'."\n";
                // Set new offset value for path as we now removed dynamic parameter from it
                $parameterOffset = 0;
            }

            $conditionStarted = true;
        }

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
    protected function recursiveGenerate(array &$dataPointer, $path, &$code = '', $level = 1, $parameterOffset = null)
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
                $code .= $this->createCondition($path, $tabs, $placeholder, $data, $conditionStarted, $parameterOffset, $level);

                // This is route end, because nested branch has only one key element
                if (sizeof($data) === 1 && isset($data[Route::ROUTE_KEY])) {
                    // Finish route parsing
                    $code .= $tabs . '     return array("' . $data[Route::ROUTE_KEY] . '", $parameters);' . "\n";
                } else { // Go deeper in recursion
                    $this->recursiveGenerate($data, $newPath, $code, $level + 5, $parameterOffset);
                }

                // Clear parameter offset
                $parameterOffset = null;
                // Close current route condition group
                $code .= $tabs . '}' ."\n";
            } else {
                $foundKey = true;
            }
        }

        // Always add last condition for parent branch if needed
        if ($foundKey) {
            $code .= $tabs . 'else {' . "\n";
            $code .= $tabs . '     return array("' . $dataPointer[Route::ROUTE_KEY] . '", $parameters);' . "\n";
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
