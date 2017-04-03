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

        $logicMethod = $this->classGenerator
            ->defNamespace('test')
            ->defName('Router')
            ->defDescription(['PLD routing logic class'])
            ->defMethod('logic')
        ;

        $httpMethodCondition = $logicMethod->defIf();
        foreach ($treeNodes as $httpMethod => $treeNode) {
            $ifCondition = $httpMethodCondition
                ->defCondition('$httpMethod === \''.$httpMethod.'\'');

            $this->buildLogicConditions($treeNode, $ifCondition);

            $ifCondition->end();
        }

        $code = $httpMethodCondition->end()->end()->code();

        return $code;
    }

    /**
     * @param TreeNode          $treeNode
     * @param AbstractGenerator|ConditionGenerator|IfGenerator $parentGenerator
     * @param int               $startPosition
     * @param string            $variable
     */
    public function buildLogicConditions(TreeNode $treeNode, AbstractGenerator $parentGenerator, $startPosition = 0, $variable = '$path')
    {
        $newGenerator = $parentGenerator->defIf();

        /** @var TreeNode $child */
        foreach ($treeNode as $child) {
            // Generate condition for searching prefix
            if ($child->value !== StringConditionTree::SELF_NAME) {
                // If nested nodes has self pointer render this condition on this level
                if (array_key_exists(StringConditionTree::SELF_NAME, $child->children)) {
                    $newGenerator->defCondition($variable . ' === \'' . $child->value . '\'')
                        ->defLine('return \'' . $this->routeIdentifiers[$child->fullValue] . '\';')
                        ->end();
                }

                $condition = $newGenerator->defCondition('substr(' . $variable . ', ' . $startPosition . ', ' . strlen($child->value) . ') === \'' . $child->value . '\'');

                // Add return value on deepest node
                if ($child->value !== StringConditionTree::SELF_NAME) {
                    $newVariable = '$path' . rand(1000, 100000);
                    $condition->defLine($newVariable . ' = substr(' . $variable . ', ' . strlen($child->fullValue) . '));');
                    $this->buildLogicConditions($child, $condition, $startPosition, $newVariable);
                }

                $condition->end();
            }
        }

        $newGenerator->end();
    }
}
