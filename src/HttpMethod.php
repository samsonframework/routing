<?php declare(strict_types=1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:11
 */

namespace samsonframework\routing;

/**
 * HTTP method identifiers.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class HttpMethod
{
    const METHOD_ANY = 'ANY';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_UPDATE = 'UPDATE';

    /** @var array Collection of all supported HTTP methods */
    public static $values = array(
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_UPDATE
    );
}
