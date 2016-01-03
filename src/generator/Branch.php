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

    /** @var int Current logic branch depth */
    protected $depth = 0;

    /** @var int Total branch length */
    protected $size = 0;

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
        $this->node = $this->getNodeFromRoutePart($routePattern);
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
        // Increase total branch size as we adding inner elements
        $this->size++;

        // Create ne branch
        $branch = new self($routePart, $this->depth + 1, $this);

        // Get node type of created branch
        if (!$branch->isParametrized()) {
            // Add new branch to the beginning of collection
            $this->branches = array_merge(array($routePart => $branch), $this->branches);
        } else { // Add new branch to the end of collection
            $this->branches[$routePart] = $branch;
        }

        return $branch;
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

    /**
     * Define which node type is this logic branch.
     *
     * @param string $routePart Route logic part
     * @return ParameterNode|TextNode Routing logic node instance
     */
    protected function getNodeFromRoutePart($routePart)
    {
        // Define route part node type
        if (preg_match(Route::PARAMETERS_FILTER_PATTERN, $routePart, $matches)) {
            $filter = &$matches['filter'];
            return new ParameterNode($matches['name'], $filter);
        } else {
            return new TextNode($routePart);
        }
    }

    /** @return bool True if branch has parameter */
    public function isParametrized()
    {
        return is_a($this->node, __NAMESPACE__ . '\ParameterNode');
    }

    /**
     * Compare two branch and define which has greater priority.
     *
     * @param Branch $aBranch
     * @param Branch $bBranch
     * @return int Comparison result
     */
    protected function sorter(Branch $aBranch, Branch $bBranch)
    {
        // Define if some of the branches if parametrized
        if (!$aBranch->isParametrized() && $bBranch->isParametrized()) {
            return -1;
        } elseif ($aBranch->isParametrized() && !$bBranch->isParametrized()) {
            return 1;
        }
    }

    /**
     * Perform routing logic branches sorting to implement needed rules.
     */
    public function sort()
    {
        // Sort this collection
        usort($this->branches, array($this, 'sorter'));

        // Iterate nested collections and sort them
        foreach ($this->branches as $branch) {
            $branch->sort();
        }
    }
}
