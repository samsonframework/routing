<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 10:55
 */
namespace samsonframework\routing\generator;

use samsonframework\routing\Core;
use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;
use samsonphp\generator\Generator;

/**4000 160дол 5900 хирургия 3-5 коронка на имплатне 2700 23-12400 10/15
 * TODO:
 * We need to invent optimization for single-child branches
 * to make collection of nodes for them or similar and generate
 * one preg_match or simple string matching depending on it
 * nodes, this will simplify generated routing logic function
 * and increase performance.
 */

/**
 * TODO:
 * We need to add  support for optional parameters.
 */

/**
 * Routing logic structure.
 *
 * @package samsonframework\routing\generator
 */
class Structure
{
    /** @var Branch */
    protected $logic;

    /** @var Generator */
    protected $generator;

    /**
     * Structure constructor.
     *
     * @param RouteCollection $routes Collection of routes for routing logic creation
     * @param Generator $generator Code generation
     */
    public function __construct(RouteCollection $routes, Generator $generator)
    {
        $this->generator = $generator;

        // Add root branch object
        $this->logic = new Branch("");

        // Collect all HTTP method that this routes collection has
        $httpMethods = array();
        foreach ($routes as $route) {
            if (!isset($httpMethods[$route->method])) {
                $this->logic->add($route->method);
                $httpMethods[$route->method] = '';
            }
        }

        /** @var Route $route */
        foreach ($routes as $route) {
            // Set branch pointer to root HTTP method branch
            $currentBranch = $this->logic->find($route->method);

            // We should count "/" route here
            $routeParts = $route->pattern == '/' ? array('/')
                : array_values(array_filter(explode(Route::DELIMITER, $route->pattern)));

            // Split route pattern into parts by its delimiter
            for ($i = 0, $size = sizeof($routeParts); $i < $size; $i++) {
                $routePart = $routeParts[$i];
                // Try to find matching branch by its part
                $tempBranch = $currentBranch->find($routePart);

                // Define if this is last part so this branch should match route
                $matchedRoute = $i == $size - 1 ? $route->identifier : '';

                // We have not found this branch
                if (null === $tempBranch) {
                    // Create new inner branch and store pointer to it
                    $currentBranch = $currentBranch->add($routePart, $matchedRoute);
                } else { // Store pointer to found branch
                    $currentBranch = $tempBranch;

                    // If we have created this branch before but now we got route for it
                    if (isset($matchedRoute{1})) {
                        // Store route identifier
                        $currentBranch->identifier = $matchedRoute;
                    }
                }
            }
        }

        // Sort branches in correct order following routing logic rules
        $this->logic->sort();
    }

    /**
     * Generate routing logic function.
     *
     * @param string $functionName Function name
     * @return string Routing logic function PHP code
     */
    public function generate($functionName = Core::ROUTING_LOGIC_FUNCTION)
    {
        $this->generator
            ->defFunction($functionName, array('$path', '$method'))
            ->defVar('$matches', array())
            // Remove first slash and last slash, add method as first, remove GET parameters
            ->newLine('$path = strtok(rtrim($method.\'/\'.ltrim($path, \'/\'),\'/\'),\'?\');')
            ->defVar('$parameters', array())
        ;

        // Perform routing logic generation
        $this->innerGenerate2($this->logic);

        $this->generator->newLine('return null;')->endFunction();

        return $this->generator->flush();
    }

    /**
     * Generate routing conditions logic.
     *
     * @param Branch $parent Current branch in resursion
     * @param string $pathValue Current $path value in routing logic
     * @param bool $conditionStarted Flag that condition started
     */
    protected function innerGenerate2(Branch $parent, $pathValue = '$path', $conditionStarted = false)
    {
        // Iterate inner branches
        foreach ($parent->branches as $branch) {
            // First stage - open condition
            // If we have started condition branching but this branch has parameters
            if ($conditionStarted && $branch->isParametrized()) {
                $this->generator
                    // Close current condition branching
                    ->endIfCondition()
                    // Start new condition
                    ->defIfCondition($branch->toLogicConditionCode($pathValue));
            } elseif (!$conditionStarted) { // This is first inner branch
                // Start new condition
                $this->generator->defIfCondition($branch->toLogicConditionCode($pathValue));
                // Set flag that condition has started
                $conditionStarted = true;
            } else { // This is regular branching
                $this->generator->defElseIfCondition($branch->toLogicConditionCode($pathValue));
            }

            // Second stage receive parameters
            if ($branch->isParametrized()) {
                // Store parameter value received from condition
                $this->generator->newLine($branch->storeMatchedParameter());
            }

            /**
             * Optimization to remove nested string operations - we create temporary $path variables
             */
            $pathVariable = '$path' . rand(0, 99999);

            // Do not output new $path variable creation if this is logic end
            if (sizeof($branch->branches)) {
                $this->generator->newLine($pathVariable . ' = ' . $branch->removeMatchedPathCode($pathValue) . ';');
            }

            // We should subtract part of $path var to remove this parameter
            // Go deeper in recursion
            $this->innerGenerate2($branch, $pathVariable, false);
        }

        // Return route if branch has it
        if ($parent->hasRoute()) {
            // If we had other inner branch for this parent branch - we need to add else
            if (sizeof($parent->branches)) {
                $this->generator->defElseCondition();
            }

            $this->generator->newLine($parent->returnRouteCode());
        }

        // Close first condition
        if ($conditionStarted) {
            $this->generator->endIfCondition();
        }
    }
}
