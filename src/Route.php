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

    /** Route parts delimiter */
    const DELIMITER = '/';

    /** @var array Collection of all supported HTTP methods */
    public static $httpMethods = array(
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_UPDATE
    );

    /** RegExp for parsing parameters in pattern placeholder */
    const PARAMETERS_FILTER_PATTERN = '/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i';

    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = self::METHOD_ANY;

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
    public function __construct($pattern, $callback, $identifier = null, $method = self::METHOD_GET)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->method = $method;
        // Every route should have an identifier otherwise create unique
        $this->identifier = isset($identifier) ? $identifier : uniqid('route');
    }
}
