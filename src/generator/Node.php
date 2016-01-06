<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 10:42
 */
namespace samsonframework\routing\generator;
use samsonframework\routing\Route;

/**
 * Routing logic structure node.
 *
 * @package samsonframework\routing\generator
 */
class Node
{
    /** @var string Node parameter name */
    public $name;

    /** @var string Node regular expression pattern */
    public $regexp;

    /** @var bool Flag that route has parameter */
    public $parametrized = false;

    /**
     * Node constructor.
     *
     * @param string $pattern Route pattern part
     */
    public function __construct($pattern)
    {
        // Search regular expression if present
        if (preg_match(Route::PARAMETERS_FILTER_PATTERN, $pattern, $matches)) {
            $this->regexp = &$matches['filter'];
            $this->name = $matches['name'];
            $this->parametrized = true;
        } else { // Textual node
            $this->name = $pattern;
        }
    }
}
