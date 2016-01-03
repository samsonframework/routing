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
                    $currentBranch = $currentBranch->add($routePart);
                } else { // Store pointer to found branch
                    $currentBranch = $tempBranch;
                }
            }
        }

        // Sort branches in correct order following routing logic rules
        $this->logic->sort();
    }
}
