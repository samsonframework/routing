<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:24
 */

namespace samsonframework\routing;

use samsonframework\generator\AbstractGenerator;
use samsonframework\generator\ClassGenerator;
use samsonframework\generator\ConditionGenerator;
use samsonframework\generator\FunctionGenerator;
use samsonframework\generator\IfGenerator;
use samsonframework\stringconditiontree\StringConditionTree;
use samsonframework\stringconditiontree\TreeNode;

/**
 * Routing function class builder.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class RouterBuilder
{
    /** @var array Collection of routes pattern => identifier */
    protected $routeIdentifiers = [];

    /** @var StringConditionTree Strings condition tree generator */
    protected $stringsTree;

    /** @var ClassGenerator PHP class code generator */
    protected $classGenerator;

    /**
     * Router builder constructor.
     *
     * @param StringConditionTree $stringsTree Strings condition tree generator
     * @param ClassGenerator      $classGenerator PHP class code generator
     */
    public function __construct(StringConditionTree $stringsTree, ClassGenerator $classGenerator)
    {
        $this->classGenerator = $classGenerator;
        $this->stringsTree = $stringsTree;
    }

    public function build(RouteCollection $routes)
    {
        $routeStrings = [];
        $this->routeIdentifiers = [];

        /** @var Route $route */
        foreach ($routes as $route) {
            // Store pattern in strings collection
            $routeStrings[$route->method][] = $route->pattern;

            // Store route identifier by its pattern
            $this->routeIdentifiers[$route->pattern] = $route->identifier;
        }

        /** @var TreeNode[] $treeNodes */
        $treeNodes = [];
        foreach ($routeStrings as $httpMethod => $strings) {
            // TODO: Fix this root node issue
            $temp = $this->stringsTree->process($strings);

            // Get inner nodes ignoring root node
            $treeNodes[$httpMethod] = $temp->children[StringConditionTree::ROOT_NAME];
        }

        // Generate class definition and logic method definition
        $logicMethod = $this->classGenerator
            ->defNamespace('test')
            ->defName('Router')
            ->defDescription(['PLD routing logic class'])
            ->defMethod('logic')
                ->defDescription(['Dispatch route using routing PLD.'])
                ->defArgument('path', 'string', 'Route path for dispatching')
                ->defArgument('httpMethod', 'string', 'HTTP request method')
                ->defComment()->defReturn('string|null', 'Dispatched route identifier')->end()
                ->defLine('$parameters = [];');

        // Define top level http method type conditions
        $httpMethodCondition = $logicMethod->defIf();
        foreach ($treeNodes as $httpMethod => $treeNode) {
            $ifCondition = $httpMethodCondition->defCondition('$httpMethod === \''.$httpMethod.'\'');

            // Recursively build string condition tree as PLD
            $this->buildLogicConditions($treeNode, $ifCondition);

            $ifCondition->end();
        }

        // Close logic method and class definition and generate PHP code
        return $httpMethodCondition->end()->end()->code();
    }

    /**
     * Build exact tree node route match condition.
     *
     * @param AbstractGenerator|ConditionGenerator|IfGenerator $generator  Code generator
     * @param TreeNode                                         $node       Tree node
     * @param string                                           $variable   Pattern variable name
     * @param string                                           $identifier Route identifier
     */
    protected function buildExactMatchCondition(AbstractGenerator $generator, TreeNode $node, string $variable, string $identifier)
    {
        $generator->defCondition($variable . ' === \'' . $node->value . '\'')
            ->defLine('return \'' . $identifier . '\';')
            ->end();
    }

    /**
     * Build partly tree node route match condition.
     *
     * @param IfGenerator $generator Code generator
     * @param string            $variable Pattern variable name
     * @param int               $startPosition Route starting character position
     * @param string            $value Route prefix
     *
     * @return ConditionGenerator New condition generator
     */
    public function buildPartMatchCondition(IfGenerator $generator, string $variable, int $startPosition, string $value): ConditionGenerator
    {
        // Create condition for matching prefix
        return $generator->defCondition(
            'substr(' . $variable . ', ' . $startPosition . ', ' . strlen($value) . ') === \'' . $value . '\''
        );
    }

    /**
     * Build new pattern variable definition.
     *
     * @param ConditionGenerator $generator Condition generator
     * @param string             $variable Pattern variable name
     * @param string             $value Route prefix
     * @param string             $variableName New variable name prefix
     *
     * @return string New built variable name
     */
    public function buildPatternVariable(ConditionGenerator $generator, string $variable, string $value, string $variableName = '$path'): string
    {
        // Create new variable
        $newVariable = $variableName . rand(1000, 100000);

        $generator->defLine($newVariable . ' = substr(' . $variable . ', ' . strlen($value) . ');');

        return $newVariable;
    }

    /**
     * @param TreeNode          $treeNode
     * @param AbstractGenerator|ConditionGenerator|IfGenerator $parentGenerator
     * @param int               $startPosition
     * @param string            $variable
     */
    public function buildLogicConditions(TreeNode $treeNode, AbstractGenerator $parentGenerator, $startPosition = 0, $variable = '$path')
    {
        // Start new condition group definition
        $newGenerator = $parentGenerator->defIf();

        /** @var TreeNode $child */
        foreach ($treeNode as $child) {
            // Generate condition for searching prefix
            if ($child->value !== StringConditionTree::SELF_NAME) {
                // If nested nodes has @self pointer render this condition in current condition
                if (array_key_exists(StringConditionTree::SELF_NAME, $child->children)) {
                    $this->buildExactMatchCondition(
                        $newGenerator,
                        $child,
                        $variable,
                        $this->routeIdentifiers[$child->fullValue]
                    );
                }

                // Check if nested nodes is not just @self node
                if (count($child->children) > 1 || key($child->children) !== StringConditionTree::SELF_NAME) {
                    $condition = $this->buildPartMatchCondition($newGenerator, $variable, $startPosition, $child->value);

                    // Go deeper into recursion
                    $this->buildLogicConditions(
                        $child,
                        $condition,
                        $startPosition + strlen($child->value),
                        $this->buildPatternVariable($condition, $variable, $child->fullValue)
                    );

                    // Close condition
                    $condition->end();
                }
            }
        }

        $newGenerator->end();
    }
}
