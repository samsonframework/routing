<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 08:36
 */
namespace samsonframework\routing;

/**
 * Route.
 *
 * @package samsonframework\routing
 */
class Route
{
    /** Route method identifiers */
    const METHOD_ANY = 'ANY';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_UPDATE = 'UPDATE';

    /** Route identifier key */
    const ROUTE_KEY = '_0';

    /** @var array Collection of all supported HTTP methods */
    public static $httpMethods = array(
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_UPDATE
    );

    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = self::METHOD_ANY;

    /** @var string Internal pattern for matching */
    public $pattern;

    /** @var array Parameters configuration */
    public $parameters = array();

    /** @var callable Route handler */
    public $callback;

    /**
     * Route constructor.
     *
     * @param string $pattern Route matching pattern
     * @param callable $callback Callback for route
     * @param string|null $identifier Route unique identifier, if empty - unique will be generated
     * @param string $method HTTP request method
     */
    public function __construct($pattern, $callback, $identifier = null, $method = self::METHOD_ANY)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->method = $method;
        // Every route should have an identifier otherwise create unique
        $this->identifier = isset($identifier) ? $identifier : uniqid('route');

        $this->analyzeParameters($callback);
    }

    /**
     * Analyze callback parameters and store their names.
     *
     * @param callback $callback Callback for analyzing
     */
    protected function analyzeParameters($callback)
    {
        // Parse callback signature and get parameters list
        if (is_callable($callback)) {
            $reflectionMethod = is_array($callback)
                ? new \ReflectionMethod($callback[0], $callback[1])
                : new \ReflectionFunction($callback);
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $this->parameters[] = $parameter->getName();
            }
        }
    }

    /**
     * Convert current route pattern into array definition string.
     *
     * @return string Array definition string
     */
    protected function arrayPattern()
    {
        /**
         * Split route pattern into parts clearing empty values and form multi-dimensional
         * array definition part.
         */
        $map = array();
        foreach (array_filter(explode('/', $this->pattern)) as $routePart) {
            $map[] = '["' . $routePart . '"]';
        }

        // Gather all found array definition parts or use whole pattern as it
        return sizeof($map) ? implode('', $map) : '["' . $this->pattern . '"]';
    }

    /**
     * Convert route pattern into PHP code array definition for building
     * route array tree.
     *
     * @param string $arrayName Generating PHP code array name
     * @return string Generated multidimensional array definition
     */
    public function toArrayDefinition($arrayName = '$routeTree')
    {
        $arrayDefinition = $this->arrayPattern();

        // Build dynamic array-tree structure for specific method
        $code = '';
        if (strpos($this->method, self::METHOD_ANY) === false) {
            $code .= $arrayName.'["' . $this->method . '"]' . $arrayDefinition . '["'.self::ROUTE_KEY.'"]= $route->identifier;'."\n";
        } else {// Build dynamic array-tree structure for all HTTP methods
            foreach (self::$httpMethods as $method) {
                $code .= $arrayName.'["' . $method . '"]' . $arrayDefinition . '["'.self::ROUTE_KEY.'"]= $route->identifier;'."\n";
            }
        }

        return $code;
    }
}
