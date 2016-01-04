<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 10:55
 */
namespace samsonframework\routing\generator;

use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;
use samsonphp\generator\Generator;

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
        // Add root branch object
        $this->logic = new Branch("");
        $this->generator = $generator;

        // Create routing logic branches
        foreach ($routes as $route) {
            // Set branch pointer to root branch
            $currentBranch = $this->logic;

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
    public function generate($functionName = '__router')
    {
        $this->generator
            ->defFunction($functionName, array('$path', '$method'))
            ->defVar('$matches', array())
            ->newLine('$path = ltrim($path, \'/\');') // Remove first slash
            ->newLine('$originalPath = $path;')

            ->defVar('$parameters', array())
        ;

        // Perform routing logic generation
        $this->innerGenerate($this->logic->branches);

        $this->generator->newLine('return null;')->endFunction();

        return $this->generator->flush();
    }

    /**
     * Generate routing conditions logic.
     *
     * @param Branch[] $branches Collection of branches for generation
     * @param string $currentString Recursion logic path string
     * @return void
     */
    protected function innerGenerate($branches, $currentString = '$path', Branch $parent = null)
    {
        /** @var Branch $branch */
        $firstBranch = array_shift($branches);
        // As this is first sub-branch always create if condition
        $this->generator->defIfCondition($firstBranch->toLogicConditionCode($currentString));

        // Store parameter to collection
        if ($firstBranch->isParametrized()) {
            $this->generator
                ->newLine('$parameters[\''.$firstBranch->node->name.'\'] = $matches[\''.$firstBranch->node->name.'\'];');
        }

        if (sizeof($firstBranch->branches)) {
            // Subtract part of path
            $this->generator->newLine('$path = '.$firstBranch->removeMatchedPathCode($currentString));
            // Generate conditions for this branch
            $this->innerGenerate($firstBranch->branches, $currentString, $firstBranch);
        } else {
            // Close first condition
            $this->generator->newLine('return array(\'' . $firstBranch->identifier . '\', $parameters);');
        }

        // Iterate all branches starting from second and not touching last one
        $branchKeys = array_keys($branches);
        for ($i = 0, $count = sizeof($branchKeys); $i < $count; $i++) {
            // Take next branch from the beginning
            $branch = $branches[$branchKeys[$i]];
            $prevBranch = &$branchKeys[$i-1];

            if (isset($prevBranch) && $branches[$prevBranch]->isParametrized() && $branch->isParametrized()) {
                $this->generator
                    // Close condition
                    ->endIfCondition()
                    // Restore $path variable
                    ->newLine('$path = $originalPath;')
                    // Start new parametrized condition as we cannot continue with param
                    ->defIfCondition($branch->toLogicConditionCode($currentString))
                    // Store parameter to collection
                    ->newLine('$parameters[\''.$branch->node->name.'\'] = $matches[\''.$branch->node->name.'\'];');
            } elseif ($branch->isParametrized()) {
                $this->generator
                    // Start new parametrized condition as we cannot continue with param
                    ->defElseIfCondition($branch->toLogicConditionCode($currentString))
                    // Store parameter to collection
                    ->newLine('$parameters[\'' . $branch->node->name . '\'] = $matches[\'' . $branch->node->name . '\'];');
            } else { // Continue condition branching
                $this->generator
                    ->defElseIfCondition($branch->toLogicConditionCode($currentString))
                    ;
            }

            if (sizeof($branch->branches)) {
                // Substract part of path
                $this->generator->newLine('$path = '.$branch->removeMatchedPathCode($currentString));
                // Generate conditions for this branch
                $this->innerGenerate($branch->branches, $currentString, $branch);
            } else {
                // Close current condition
                $this->generator->newLine('return array(\'' . $branch->identifier . '\', $parameters);');
            }
        }

        if (isset($parent) && isset($parent->identifier{0})) {
            // Close current condition
            $this->generator->defElseCondition()->newLine('return array(\'' . $parent->identifier . '\', $parameters);');
        }

        // Close condition
        $this->generator->endIfCondition();
    }
}
