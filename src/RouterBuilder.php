<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:24
 */

namespace samsonframework\routing;

use samsonframework\generator\ClassGenerator;
use samsonframework\stringconditiontree\StringConditionTree;
use samsonframework\stringconditiontree\TreeNode;

/**
 * Routing function class builder.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class RouterBuilder
{
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

        /** @var Route $route */
        foreach ($routes as $route) {
            $routeStrings[$route->method][] = $route->pattern;
        }

        /** @var TreeNode[] $treeNodes */
        $treeNodes = [];
        foreach ($routeStrings as $httpMethod => $strings) {
            $treeNodes[$httpMethod] = $this->stringsTree->process($strings);
        }

        return $treeNodes;
    }
}
