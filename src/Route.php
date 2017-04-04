<?php declare(strict_types = 1);
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
    /** Route identifier key */
    const ROUTE_KEY = '_0';

    /** Route parts delimiter */
    const DELIMITER = '/';

    /** RegExp for parsing parameters in pattern placeholder */
    const PARAMETERS_FILTER_PATTERN = '/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i';

    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = HttpMethod::METHOD_ANY;

    /** @var string Internal pattern for matching */
    public $pattern;

    /** @var mixed Route handler */
    public $callback;

    /**
     * Route constructor.
     *
     * @param string $pattern Route matching pattern
     * @param callable $callback Callback for route
     * @param string|null $identifier Route unique identifier, if empty - unique will be generated
     * @param string $method HTTP request method
     */
    public function __construct($pattern, $callback, $identifier = null, $method = HttpMethod::METHOD_GET)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->method = $method;

        // Every route should have an identifier otherwise create unique
        $this->identifier = $identifier ?? uniqid('route', true);
    }
}
