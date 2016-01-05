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
    /** @var Branch[] */
    public $branches = array();

    /** @var Node */
    public $node;

    /** @var string Route identifier */
    public $identifier;

    /** @var string Route full path */
    public $fullPath;

    /** @var string Route pattern path */
    protected $path;

    /** @var Branch Pointer to parent element */
    protected $parent;

    /** @var int Total branch length */
    protected $size = 0;

    /**
     * Branch constructor.
     *
     * @param string $pattern Route pattern part that represent routing logic branch
     * @param Branch $parent Pointer to parent branch
     * @param string $identifier Route identifier
     */
    public function __construct($pattern, Branch $parent = null, $identifier = '')
    {
        $this->path = $pattern;
        $this->parent = $parent;
        $this->node = $this->getNodeFromRoutePart($pattern);
        $this->identifier = $identifier;
        $this->identifier = $identifier;
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
     * @param null|string $routeIdentifier Route identifier
     * @return Branch New created branch
     */
    public function add($routePart, $routeIdentifier = null)
    {
        // Increase total branch size as we adding inner elements
        $pointer = $this;
        while (isset($pointer)) {
            $pointer->size++;
            $pointer = $pointeRemovedr->parent;
        }

        // Create ne branch
        $branch = new Branch($routePart, $this, $routeIdentifier);

        // Get node type of created branch
        if (!$branch->isParametrized()) {
            // Add new branch to the beginning of collection
            $this->branches = array_merge(array($routePart => $branch), $this->branches);
        } else { // Add new branch to the end of collection
            $this->branches[$routePart] = $branch;
        }

        return $branch;
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
        } elseif ($aBranch->isParametrized() && $bBranch->isParametrized()) {
            if (isset($aBranch->node->regexp{1}) && !isset($bBranch->node->regexp{1})) {
                return -1;
            } elseif (!isset($aBranch->node->regexp{1}) && isset($bBranch->node->regexp{1})) {
                return 1;
            }
        }

        // Return branch size comparison, longer branches should go on top
        return $aBranch->size < $bBranch->size ? 1 : -1;
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
            // Generate regular expression matching condition
            if (sizeof($this->branches)) {
                $condition = 'preg_match(\'/(?<' . $this->node->name . '>' . $filter . ')/i\', ' . $currentString . ', $matches)';
            } else {
                $condition = 'preg_match(\'/(?<' . $this->node->name . '>' . $filter . '$)/i\', ' . $currentString . ', $matches)';
            }
            return $condition;
        } elseif (sizeof($this->branches)) {
            return 'substr('.$currentString . ', '.$offset.', '.strlen($this->node->content).') === "' . $this->node->content .'"';
        } else { // This is last condition in branch it should match
            $content = $this->node->content == '/' ? 'false' : '\''.$this->node->content.'\'';
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
        if ($this->isParametrized()) {
            return $parametersVariable.'[\''.$this->node->name.'\'] = $matches[\''.$this->node->name.'\'];';
        }
    }

    /**
     * Generate PHP code for returning route if present.
     *
     * @param string $parametersVariable Name of variable for storing route parameters
     * @return string Generated PHP code for returning route if present
     */
    public function returnRouteCode($parametersVariable = '$parameters')
    {
        if ($this->hasRoute()) {
            return 'return array(\'' . $this->identifier . '\', '.$parametersVariable.');';
        }

        return '';
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
            return 'substr('.$currentString.', '.(strlen($this->node->content) + 1).')';
        }
    }
}
