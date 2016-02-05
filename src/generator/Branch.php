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

    /** @var Node[] Collection of route nodes */
    public $node;

    /** @var string Route identifier */
    public $identifier;

    /** @var string branch description */
    public $patternPath;

    /** @var Branch Pointer to parent element */
    public $parent;

    /** @var int Total branch length */
    public $size = 0;

    /** @var  string Branch callback */
    public $callback;

    /**
     * Branch constructor.
     *
     * @param string $patterPath Route pattern part that represent routing logic branch
     * @param Branch $parent Pointer to parent branch
     * @param Route $route Route instance
     */
    public function __construct($patterPath, Branch $parent = null, Route $route = null)
    {
        $this->node[] = new Node($patterPath);
        $this->parent = $parent;
        $this->patternPath = $patterPath;

        if ($route !== null) {
            $this->identifier = $route->identifier;
            $this->setCallback($route->callback);
        }
    }

    /**
     * Set branch callback value.
     *
     * @param mixed $callback
     */
    public function setCallback($callback)
    {
        // Convert callable to string if passed
        $this->callback = is_array($callback)
            ? get_class($callback[0]) . '#' . $callback[1]
            : $callback;
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
            if ($identifier == $routePart) {
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
        while ($pointer !== null) {
            $pointer->size++;
            $pointer = $pointer->parent;
        }

        // Create ne branch
        return $this->branches[$routePart] = new Branch($routePart, $this, $route);
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

    /** @return bool True if this branch has a route
     * @return bool
     */
    public function hasRoute()
    {
        return isset($this->identifier{1});
    }

    /**
     * Get current branch PHP code logic condition.
     *
     * @param string $currentString
     * @param int $offset
     * @return string
     */
    public function toLogicConditionCode($currentString = '$path', $offset = 0)
    {
        $nodeValue = $this->nodeRegExpValue('name');
        if ($this->isParametrized()) {
            $regularExpression = '';
            /** @var Node $node Iterate all nodes and gather them in "big" regular expression */
            foreach ($this->node as $node) {
                if ($node->parametrized) {
                    // Use default parameter filter
                    $filter = (isset($node->regexp{1}) ? $node->regexp : '[^\/]+');
                    // Add regular expression node
                    $regularExpression[] = '(?<' . $node->name . '>' . $filter . ')';
                } else {
                    $regularExpression[] = preg_quote($node->name);
                }
            }
            // If this is last parameter in logic force it to end with its pattern
            $regularExpression = count($this->branches) ? implode('\/', $regularExpression) : implode('\/', $regularExpression) . '$';

            // Generate regular expression matching condition
            return 'preg_match(\'/^' . $regularExpression . '/i\', ' . $currentString . ', $matches)';
        } elseif (count($this->branches)) {
            return 'substr(' . $currentString . ', ' . $offset . ', ' . strlen($nodeValue) . ') === \'' . $nodeValue . '\'';
        } else { // This is last condition in branch it should match
            $content = $nodeValue === '/' ? '\'\'' : '\'' . $nodeValue . '\'';
            return $currentString . ' === ' . $content;
        }
    }

    /** @return bool True if branch has parameterized node */
    public function isParametrized()
    {
        /** @var Node $node */
        foreach ($this->node as $node) {
            if ($node->parametrized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string Node string representation
     *
     * @param $valueName
     * @return string
     */
    public function nodeRegExpValue($valueName)
    {
        /** @var Node $node */
        $return = array();
        foreach ($this->node as $node) {
            $return[] = $node->{$valueName};
        }

        return implode('/', $return);
    }

    /**
     * Generate code for storing branch matched parameter.
     *
     * @param string $parametersVariable Name of variable for storing route parameters
     * @return string PHP code for storing matched parameter
     */
    public function storeMatchedParameter($parametersVariable = '$parameters')
    {
        $return = '';
        foreach ($this->node as $node) {
            if ($node->parametrized) {
                $return .= $parametersVariable . '[\'' . $node->name . '\'] = $matches[\'' . $node->name . '\'];';
            }
        }
        return $return;
    }

    /**
     * Generate PHP code for returning route if present.
     *
     * @param string $parametersVariable Name of variable for storing route parameters
     * @return string Generated PHP code for returning route if present
     */
    public function returnRouteCode($parametersVariable = '$parameters')
    {
        return 'return array(\'' . $this->identifier . '\', ' . $parametersVariable . ', \'' . $this->callback . '\');';
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
            return 'substr(' . $currentString . ', strlen($matches[0]) + 1)';
        } else {
            return 'substr(' . $currentString . ', ' . (strlen($this->nodeRegExpValue('name')) + 1) . ')';
        }
    }

    /**
     * Both branches are not parametrized
     * @param $i
     * @param $aBranch
     * @param $bBranch
     * @return int
     */
    public function rulesBothBranchesNotParametrized($i, $aBranch, $bBranch)
    {
        /** @var Node $aNode */
        $aNode = &$aBranch->node[$i];
        /** @var Node $bNode */
        $bNode = &$bBranch->node[$i];

        /**
         * Rule #3
         * If both branches are not parametrized then branch with shorter pattern string has higher priority.
         */
        if (strlen($aNode->name) > strlen($bNode->name)) {
            return -1;
        } elseif (strlen($aNode->name) < strlen($bNode->name)) {
            return 1;
        } else {
            /**
             * Rule #4
             * If both branches are not parametrized and they have two length-equal string patterns then not
             * "deeper" branch has priority.
             */
            return $aBranch->size > $bBranch->size ? 1 : -1;
        }
    }

    /**
     * Rules:
     * If both branches are parametrized then branch with set regexp filter has higher priority.
     * If both branches are parametrized and they have two length-equal string patterns then not
     *
     * @param $i
     * @param $aBranch
     * @param $bBranch
     * @return int
     */
    public function rulesBothBranchesParametrizedRegexp($i, $aBranch, $bBranch)
    {
        /** @var Node $aNode */
        $aNode = &$aBranch->node[$i];
        /** @var Node $bNode */
        $bNode = &$bBranch->node[$i];

        /**
         * Rule #2
         * If both branches are parametrized then branch with set regexp filter has higher priority.
         */
        $aRegExp = $aNode->regexp;
        $bRegExp = $bNode->regexp;

        if (isset($aRegExp{1}) && !isset($bRegExp{1})) {
            return -1;
        } elseif (!isset($aRegExp{1}) && isset($bRegExp{1})) {
            return 1;
        } else {
            /**
             * Rule #4
             * If both branches are parametrized and they have two length-equal string patterns then not
             * "deeper" branch has priority.
             */
            return $aBranch->size < $bBranch->size ? 1 : -1;
        }
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
        for ($i = 0, $size = max(count($aBranch->node), count($bBranch->node)); $i < $size; $i++) {
            /** @var Node $aNode */
            $aNode = &$aBranch->node[$i];
            /** @var Node $bNode */
            $bNode = &$bBranch->node[$i];

            /**
             * Rule #1
             * Parametrized branch always has lower priority then textual branch.
             */
            if (!$aNode->parametrized && $bNode->parametrized) {
                return -1;
            } elseif ($aNode->parametrized && !$bNode->parametrized) {
                return 1;
            } elseif ($aNode->parametrized && $bNode->parametrized) {
                return $this->rulesBothBranchesParametrizedRegexp($i, $aBranch, $bBranch);
                /** TODO: We need to invent a way to compare regexp filter to define who is "wider" */
            } else {
                return $this->rulesBothBranchesNotParametrized($i, $aBranch, $bBranch);
            }
        }
    }
}