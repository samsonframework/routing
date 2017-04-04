<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:24
 */

namespace samsonframework\routing;

use samsonframework\generator\AbstractGenerator;
use samsonframework\generator\ClassGenerator;
use samsonframework\generator\ConditionGenerator;
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
     * @param StringConditionTree $stringsTree    Strings condition tree generator
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

        /** @var TreeNode[] $treeNodes Group tree nodes under http method type */
        $treeNodes = [];
        foreach ($routeStrings as $httpMethod => $strings) {
            // TODO: Fix this root node issue
            $temp = $this->stringsTree->process($strings);

            // Get inner nodes ignoring root node
            $treeNodes[$httpMethod] = $temp->children[StringConditionTree::ROOT_NAME];
        }

        // Generate class definition
        $classGenerator = $this->classGenerator
            ->defNamespace('test')
            ->defName('Router')
            ->defDescription(['PLD routing logic class']);

        // Generate logic method definition
        $logicMethod = $classGenerator->defMethod('logic')
            ->defDescription(['Dispatch route using routing PLD.'])
            ->defArgument('path', 'string', 'Route path for dispatching')
            ->defArgument('httpMethod', 'string', 'HTTP request method')
            ->defReturnType('string|null', 'Dispatched route identifier');

        /** @var IfGenerator $httpMethodCondition Define top level http method type conditions */
        $httpMethodCondition = $logicMethod->defIf();
        foreach ($treeNodes as $httpMethod => $treeNode) {
            $httpMethodName = 'logic' . ucfirst(strtolower($httpMethod));
            $httpMethodFunction = $classGenerator->defProtectedMethod($httpMethodName)
                ->defDescription(['Dispatch routes for ' . $httpMethod . ' HTTP method.'])
                ->defArgument('path', 'string', 'Route path for dispatching')
                ->defLine('$parameters = [];')
                ->defLine('$matches = [];')
                ->defReturnType('string|null', 'Dispatched route identifier');

            // Recursively build string condition tree as PLD
            $this->buildLogicConditions($treeNode, $httpMethodFunction);

            $httpMethodFunction->defLine('return null;')->end();

            $httpMethodCondition->defCondition('$httpMethod === \'' . $httpMethod . '\'')
                ->defLine('return $this->' . $httpMethodName . '($path, $httpMethod);')
                ->end();
        }

        // Close logic method and class definition and generate PHP code
        return $httpMethodCondition->end()->end()->code();
    }

    /**
     * Recursive logic function generator.
     *
     * @param TreeNode                                         $treeNode
     * @param AbstractGenerator|ConditionGenerator|IfGenerator $parentGenerator
     * @param int                                              $startPosition
     * @param string                                           $variable
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
                        $child->value,
                        $variable,
                        $this->routeIdentifiers[$child->fullValue]
                    );
                }

                // Check if nested nodes is not just @self node
                if (count($child->children) > 1 || key($child->children) !== StringConditionTree::SELF_NAME) {
                    $condition = $this->buildPartMatchCondition(
                        $newGenerator,
                        $variable,
                        $child->value
                    );

                    // Go deeper into recursion
                    $this->buildLogicConditions(
                        $child,
                        $condition,
                        $startPosition,
                        $this->buildPatternVariable($condition, $variable, $child->value)
                    );

                    // Close condition
                    $condition->end();
                }
            }
        }

        $newGenerator->end();
    }

    /**
     * Build exact tree node route match condition.
     *
     * @param AbstractGenerator|ConditionGenerator|IfGenerator $generator  Code generator
     * @param string                                           $prefix     Route prefix
     * @param string                                           $variable   Pattern variable name
     * @param string                                           $identifier Route identifier
     *
     * @return AbstractGenerator|ConditionGenerator Condition Generator
     */
    protected function buildExactMatchCondition(AbstractGenerator $generator, string $prefix, string $variable, string $identifier): AbstractGenerator
    {
        // Get parametrized condition statement
        $parameters = [];
        $statement = $this->getParametrizedConditionExpression($prefix, $variable, $parameters);

        // No parameters - get part matching condition statement
        if (!count($parameters)) {
            $statement = $variable . ' === \'' . $prefix . '\'';
        }

        $condition = $generator->defCondition($statement);

        // Define parameters definition to matched route arguments
        foreach ($parameters as $parameter) {
            $condition->defLine('$parameters[\'' . $parameter . '\'] = $matches[\'' . $parameter . '\'];');
        }

        return $condition->defLine('return [\'' . $identifier . '\', $parameters];')->end();
    }

    /**
     * Build parametrized condition statement. Method fills  parameters array.
     *
     * @param string $pattern    Route pattern
     * @param string $variable   Route variable name
     * @param array  $parameters Return parameter names array
     *
     * @return string Parametrized condition statement
     */
    protected function getParametrizedConditionExpression(string $pattern, string $variable, array &$parameters = [])
    {
        $regularExpression = '';

        // Find all parameters
        $matches = [];
        if (preg_match_all(Route::PARAMETERS_FILTER_PATTERN, $pattern, $matches)) {
            // Start building regular expression
            $regularExpression = 'preg_match(\'/';

            $pattern = str_replace('/', '\/', $pattern);

            // Iterate matched parameters
            for ($i = 0, $count = count($matches['name']); $i < $count; $i++) {
                // Define parameter filter
                $filter = $matches['filter'][$i] !== '' ? $matches['filter'][$i] : '[^\/]+';

                // Build regular expression
                $parameterExpression = '(?<' . $matches['name'][$i] . '>' . $filter . ')';

                // Rewrite pattern parameter
                $pattern = str_replace($matches[0][$i], $parameterExpression, $pattern);

                // Gather found parameter names
                $parameters[] = $matches['name'][$i];
            }

            // Finish building regular expression
            $regularExpression .= $pattern . '/\', ' . $variable . ', $matches)';
        }

        return $regularExpression;
    }

    /**
     * Build partly tree node route match condition.
     *
     * @param IfGenerator $generator     Condition generator
     * @param string      $variable      Pattern variable name
     * @param string      $value         Route prefix
     *
     * @return ConditionGenerator Condition generator
     */
    public function buildPartMatchCondition(IfGenerator $generator, string $variable, string $value): ConditionGenerator
    {
        $parameters = [];
        // Get parametrized condition statement
        $statement = $this->getParametrizedConditionExpression($value, $variable, $parameters);

        // No parameters - get part matching condition statement
        if (!count($parameters)) {
            $valueLength = strlen($value);
            if ($valueLength > 1) {
                $statement = 'strpos(' . $variable . ', \'' . $value . '\') === 0';
            } else { // Optimisation for one character
                $statement = $variable . '{0} === \'' . $value . '\'';
            }
        }

        $condition = $generator->defCondition($statement);

        // Define parameters definition to matched route arguments
        foreach ($parameters as $parameter) {
            $condition->defLine('$parameters[\'' . $parameter . '\'] = $matches[\'' . $parameter . '\'];');
        }

        return $condition;
    }

    /**
     * Build new pattern variable definition.
     *
     * @param ConditionGenerator $generator    Condition generator
     * @param string             $variable     Pattern variable name
     * @param string             $value        Route prefix
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
}
