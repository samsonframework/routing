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

/**
 * TODO: #3
 * We need to add  support for optional parameters.
 * Currently it can be implemented via creating separate routes for each
 * parameters set. We need to create supported syntax for this by adding
 * regular expression "?" in one pattern with multiple parameters this refeneces
 * previous improvement #0.
 */

/**
 * TODO:
 * Refactor to get 10 points %)
 */

/**
 * TODO: Create a separate limit handling of main page route, it should be first.
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

    /** @var array Collection of existing http methods */
    protected $httpMethods = array();

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
        $this->httpMethods = array();
        foreach ($routes as $route) {
            if (!isset($this->httpMethods[$route->method])) {
                $this->logic->add($route->method);
                $this->httpMethods[$route->method] = $route->method;
            }
        }

        /** @var Route $route Build routing logic branches */
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
                $matchedRoute = $i == $size - 1 ? $route : null;

                // We have not found this branch
                if (null === $tempBranch) {
                    // Create new inner branch and store pointer to it
                    $currentBranch = $currentBranch->add($routePart, $matchedRoute);
                } else { // Store pointer to found branch
                    $currentBranch = $tempBranch;

                    // TODO: this should be improved
                    // If we have created this branch before but now we got route for it
                    if (isset($matchedRoute)) {
                        // Store route identifier
                        $currentBranch->identifier = $matchedRoute->identifier;
                        $currentBranch->setCallback($matchedRoute->callback);
                    }
                }
            }
        }

        // Optimize each top level branch(method branch)
        foreach ($this->httpMethods as $method) {
            foreach ($this->logic->branches[$method]->branches as $branch) {
                $this->optimizeBranches($branch);
            }
        }

        $this->optimizeBranchesWithRoutes($this->logic);

        // Sort branches in correct order following routing logic rules
        $this->logic->sort();
    }

    /**
     * Branch optimization:
     * We take inner textual final branch one level higher to speed
     * up their matching.
     *
     * @param Branch $parent
     */
    protected function optimizeBranchesWithRoutes(Branch &$parent)
    {
        /** @var Branch $branch */
        foreach ($parent->branches as &$branch) {
            // If inner branch is final and has a route
            if ($branch->hasRoute() && !$branch->isParametrized() && sizeof($branch->branches)) {
                // Create a new one one level higher
                $parent->branches[$branch->patternPath.'$'] = new Branch($branch->patternPath, $parent);
                $parent->branches[$branch->patternPath.'$']->identifier = $branch->identifier;
                $parent->branches[$branch->patternPath.'$']->callback = $branch->callback;
                $parent->branches[$branch->patternPath.'$']->node = $branch->node;
                // Remove route from inner branch
                $branch->identifier = '';
                $branch->callback = '';
            }

            $this->optimizeBranchesWithRoutes($branch);
        }
    }

    /**
     * Branch optimization:
     * Method searches for branch only with one child and combine their
     * patterns, this decreases logic branches and path cutting in final
     * routing logic function.
     *
     * @param Branch $parent
     */
    protected function optimizeBranches(Branch &$parent)
    {
        if (!$parent->hasRoute() && sizeof($parent->branches) === 1) {
            /** @var Branch $branch */
            $branch = array_shift($parent->branches);

            // Go deeper in recursion for nested branches
            $this->optimizeBranches($branch);

            // Add inner branch node to current branch
            $parent->node = array_merge($parent->node, $branch->node);

            if (isset($branch->identifier{1})) {
                $parent->identifier = $branch->identifier;
                $parent->callback = $branch->callback;
            }

            // We are out from recursion - remove this branch
            unset($parent->branches[$branch->patternPath]);
        }
    }

    /**
     * HTTP method routing logic condition generator.
     *
     * @param string $method HTPP method name
     * @param string $conditionFunction PHP Code generator condition function name
     */
    protected function buildRoutesByMethod($method, $conditionFunction = 'defIfCondition')
    {
        $this->generator->$conditionFunction('$method === "'.$method.'"');
        // Perform routing logic generation
        $this->innerGenerate2($this->logic->find($method));
        // Add return found route
        $this->generator->newLine('return null;');
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
            ->defVar('$parameters', array())
        ;

        // Do not generate if we have no http methods supported
        if (sizeof($this->httpMethods)) {
            // Build routes for first method
            $this->buildRoutesByMethod(array_shift($this->httpMethods));

            // Build routes for other methods
            foreach ($this->httpMethods as $method) {
                // Build routes for first method
                $this->buildRoutesByMethod($method, 'defElseIfCondition');
            }

            // Add method not found
            $this->generator->endIfCondition();
        }

        // Add method not found
        $this->generator->newLine('return null;')->endFunction();

        return $this->generator->flush();
    }

    /**
     * Generate routing conditions logic.
     *
     * @param Branch $parent Current branch in recursion
     * @param string $pathValue Current $path value in routing logic
     * @param bool $conditionStarted Flag that condition started
     */
    protected function innerGenerate2(Branch $parent, $pathValue = '$path', $conditionStarted = false)
    {
        // If this branch has route
        if ($parent->hasRoute()) {
            // Generate condition if we have inner branches
            if (sizeof($parent->branches)) {
                $this->generator->defIfCondition('' . $pathValue . ' === false');
                $conditionStarted = true;
            }
            // Close logic branch if matches
            $this->generator->newLine($parent->returnRouteCode());
        }

        // Iterate inner branches
        foreach ($parent->branches as $branch) {
            $this->generatorBranchesLoop($branch, $conditionStarted, $pathValue);
        }

        // Close first condition
        if ($conditionStarted) {
            $this->generator->endIfCondition();
        }
    }

    /**
     * Generator inner branches loop handler.
     *
     * @param Branch $branch Branch for looping its inner branches
     * @param bool $conditionStarted Return variable showing if inner branching has been started
     * @param string $pathValue Current routing logic $path variable name
     */
    protected function generatorBranchesLoop(Branch $branch, &$conditionStarted, $pathValue)
    {
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
}
