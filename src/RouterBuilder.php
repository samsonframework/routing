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

        /** @var string[] $parameters Collection of parameters placeholders */
        $parameters = [];

        /** @var Route $route */
        foreach ($routes as $route) {
            $pattern = $route->pattern;

            /**
             * Rewrite parameter patterns in routes with special symbols and
             * store references to it.
             */
            $matches = [];
            if (preg_match_all(Route::PARAMETERS_FILTER_PATTERN, $pattern, $matches)) {
                foreach ($matches[0] as $match) {
                    // Store unique parameters
                    if (in_array($match, $parameters, true)) {
                        $index = array_search($match, $parameters);
                    } else {
                        // Calculate parameter index
                        $index = count($parameters).'_parameter';
                        $parameters[$index] = $match;
                    }

                    // Rewrite pattern parameter
                    $pattern = str_replace($match, $index, $pattern);
                }
            }

            // Store pattern in strings collection
            $routeStrings[$route->method][] = $pattern;
        }



        /** @var TreeNode[] $treeNodes */
        $treeNodes = [];
        foreach ($routeStrings as $httpMethod => $strings) {
            $treeNodes[$httpMethod] = $this->stringsTree->process($strings);
        }

        $result = $treeNodes['GET']->toArray(function(string $value) use ($parameters) {
            $matches = [];
            if (preg_match_all('/(?<parameter>\d+_parameter)/', $value, $matches)) {
                foreach ($matches['parameter'] as $parameterMatch) {
                    // Check if node value is a parameter
                    if (array_key_exists($parameterMatch, $parameters)) {
                        $value = str_replace($parameterMatch, $parameters[$parameterMatch], $value);
                    }
                }
                // Returned processed node value
                return $value;
            } else { // Just return node value
                return $value;
            }
        });

        return $result;
    }
}
