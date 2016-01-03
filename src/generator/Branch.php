<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 11:00
 */
namespace samsonframework\routing\generator;

use samsonframework\routing\Route;

/**
 * Routing logic branch.
 *
 * @package samsonframework\routing\generator
 */
class Branch
{
    /** @var self[] */
    protected $branches = array();

    /** @var Node */
    protected $node;

    /** @var string Route pattern path */
    protected $path;

    /** @var self Pointer to parent element */
    protected $parent;

    /** @var int Logic branch depth */
    protected $depth = 0;

    /**
     * Branch constructor.
     *
     * @param string $routePattern Route that represent routing logic branch
     * @param int $depth Current branch logic depth
     * @param self $parent Pointer to parent branch
     */
    public function __construct($routePattern, $depth = 0, self $parent = null)
    {
        $this->path = $routePattern;
        $this->depth = $depth;
        $this->parent = $parent;

        // Define route part node type
        if (preg_match(Route::PARAMETERS_FILTER_PATTERN, $routePattern, $matches)) {
            $filter = &$matches['filter'];
            $this->node = new ParameterNode($matches['name'], $filter);
        } else {
            $this->node = new TextNode($routePattern);
        }
    }

    /**
     * Find branch by route part.
     *
     * @param string $routePart Route logic part
     * @return null|self Found branch or null
     */
    public function find($routePart)
    {
        foreach ($this->branches as $identifier => $branch) {
            if ($identifier === $routePart) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * Add new branch.
     *
     * @param string $routePart Route logic part
     * @return self New created branch
     */
    public function add($routePart)
    {
        return $this->branches[$routePart] = new self($routePart, $this->depth + 1, $this);
    }

    /** @return string Get full logic branch path */
    public function fullPath()
    {
        $pointer = $this;
        $result = array();
        do {
            // Add path part to the beginning of array
            array_unshift($result, $pointer->path);
            // Switch pointer to parent branch
            $pointer = $pointer->parent;
        } while (isset($pointer));

        return implode(Route::DELIMITER, $result);
    }
}
