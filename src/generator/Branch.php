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
    /** @var Branch[string] */
    public $branches = array();

    /** @var Node */
    public $node;

    /** @var string Route identifier */
    public $identifier;

    /** @var string Route full path */
    public $fullPath;

    /** @var Branch Pointer to parent element */
    protected $parent;

    /** @var int Total branch length */
    protected $size = 0;

    /**
     * Branch constructor.
     *
     * @param string $patterPath Route pattern part that represent routing logic branch
     * @param Branch $parent Pointer to parent branch
     * @param Route $route Route instance
     */
    public function __construct($patterPath, Branch $parent = null, Route $route = null)
    {
        $this->node = new Node($patterPath);
        $this->parent = $parent;

        if (isset($route)) {
            $this->identifier = $route->identifier;
            $this->fullPath = $route->pattern;
        }
    }

    /**
     * Find branch by route part.
     *
     * @param string $routePart Route logic part
     * @return null|Branch Found branch or null
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
     * @param Route $route
     * @return Branch New created branch
     */
    public function add($routePart, Route $route = null)
    {
        // Increase total branch size as we adding inner elements
        $pointer = $this;
        while (isset($pointer)) {
            $pointer->size++;
            $pointer = $pointer->parent;
        }

        // Create ne branch
        $branch = new Branch($routePart, $this, $route);

        // Get node type of created branch
        if (!$branch->isParametrized()) {
            // Add new branch to the beginning of collection
            $this->branches = array_merge(array($routePart => $branch), $this->branches);
        } else { // Add new branch to the end of collection
            $this->branches[$routePart] = $branch;
        }

        return $branch;
    }


    /** @return bool True if branch has parameter */
    public function isParametrized()
    {
        return $this->node->parametrized;
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
        // Give priority to branch that is not parametrized
        if (!$aBranch->isParametrized() && $bBranch->isParametrized()) {
            return -1;
        } elseif ($aBranch->isParametrized() && !$bBranch->isParametrized()) {
            return 1;
        } elseif ($aBranch->isParametrized() && $bBranch->isParametrized()) {
            // Both branches have parameters
            // Give priority to parametrized branch which has regular expression
            if (isset($aBranch->node->regexp{1}) && !isset($bBranch->node->regexp{1})) {
                return -1;
            } elseif (!isset($aBranch->node->regexp{1}) && isset($bBranch->node->regexp{1})) {
                return 1;
            }
        } else { // Both branches are not parametrized
            /**
             * Rule #10
             * Not parametrized routes with longer pattern has higher priority.
             */
            if (strlen($aBranch->node->name) > strlen($bBranch->node->name)) {
                return -1;
            } elseif (strlen($aBranch->node->name) < strlen($bBranch->node->name)) {
                return 1;
            } else {
                /**
                 * Rule #11
                 * When we have two length-equal not parametrized branches "deeper" branch has priority
                 */
                return $aBranch->size < $bBranch->size ? 1 : -1;
            }
        }
    }

    /**
     * Perform routing logic branches sorting to implement needed rules.
     */
    public function sort()
    {
        // Sort this collection
        uasort($this->branches, array($this, 'sorter'));

        // Iterate nested collections and sort them
        foreach ($this->branches as $branch) {
            $branch->sort();
        }
    }

    /** @return bool True if this branch has a route */
    public function hasRoute()
    {
        return isset($this->identifier{1});
    }

    /**
     * Get current branch PHP code logic condition.
     *
     * @param string $currentString Current routing logic path variable
     * @return string Logic condition PHP code
     */
    public function toLogicConditionCode($currentString = '$path', $offset = 0)
    {
        if ($this->isParametrized()) {
            // Use default parameter filter
            $filter = '^'.(isset($this->node->regexp{1}) ? $this->node->regexp : '[^\/]+');
            // If this is last parameter in logic force it to end with its pattern
            $filter = sizeof($this->branches) ? $filter : $filter.'$';
            // Generate regular expression matching condition
            return 'preg_match(\'/(?<' . $this->node->name . '>' . $filter . ')/i\', ' . $currentString . ', $matches)';
        } elseif (sizeof($this->branches)) {
            return 'substr('.$currentString . ', '.$offset.', '.strlen($this->node->name).') === \'' . $this->node->name .'\'';
        } else { // This is last condition in branch it should match
            $content = $this->node->name == '/' ? 'false' : '\''.$this->node->name.'\'';
            return $currentString . ' === '.$content;
        }
    }

    /**
     * Generate code for storing branch matched parameter.
     *
     * @param string $parametersVariable Name of variable for storing route parameters
     * @return string PHP code for storing matched parameter
     */
    public function storeMatchedParameter($parametersVariable = '$parameters')
    {
        return $parametersVariable.'[\''.$this->node->name.'\'] = $matches[\''.$this->node->name.'\'];';
    }

    /**
     * Generate PHP code for returning route if present.
     *
     * @param string $parametersVariable Name of variable for storing route parameters
     * @return string Generated PHP code for returning route if present
     */
    public function returnRouteCode($parametersVariable = '$parameters')
    {

        return 'return array(\'' . $this->identifier . '\', '.$parametersVariable.');';
    }

    /**
     * Routing logic path cutter.
     *
     * @param string $currentString Current routing logic path variable
     * @return string Path cutting PHP code
     */
    public function removeMatchedPathCode($currentString = '$path')
    {
        if ($this->isParametrized()) {
            // Just remove matched from the string
            return 'substr('.$currentString.', strlen($parameters[\''.$this->node->name.'\']) + 1)';
        } else {
            return 'substr('.$currentString.', '.(strlen($this->node->name) + 1).')';
        }
    }
}
