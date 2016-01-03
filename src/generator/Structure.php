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

            // Split route pattern into parts by its delimiter
            foreach (array_filter(explode(Route::DELIMITER, $route->pattern)) as $routePart) {
                // Try to find matching branch by its part
                $tempBranch = $currentBranch->find($routePart);

                // We have not found this branch
                if (null === $tempBranch) {
                    // Create new inner branch and store pointer to it
                    $currentBranch = $currentBranch->add($routePart, $route->identifier);
                } else { // Store pointer to found branch
                    $currentBranch = $tempBranch;
                }
            }
        }

        // Sort branches in correct order following routing logic rules
        $this->logic->sort();

        // Perform routing logic generation
        $this->generate($this->logic->branches);

        $code = $this->generator->flush();
    }

    protected function generate($branches, $currentString = '$path')
    {
        if (!sizeof($branches)) {
            return false;
        }

        /** @var Branch $branch */
        $branch = array_shift($branches);
        $this->generator->defIfCondition($branch->toLogicConditionCode($currentString));

        // Generate conditions for this branch
        $this->generate($branch->branches);

        // Iterate all branches starting from second and not touching last one
        for ($i = 1, $count = sizeof($branches); $i < $count - 1; $i++) {
            // Take next branch from the beginning
            $branch = array_shift($branches);
            // Create condition
            $this->generator->defElseIfCondition($branch->toLogicConditionCode($currentString));
            // Generate conditions for this branch
            $this->generate($branch->branches);
        }

        $this->generator->endIfCondition();
    }
}
