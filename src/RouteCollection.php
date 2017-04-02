<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 08:35
 */
namespace samsonframework\routing;

use samsonframework\routing\exception\IdentifierDuplication;

/**
 * Routes collection
 * @package samsonframework\routing
 */
class RouteCollection implements \ArrayAccess, \Iterator
{
    /** @var Route[] */
    protected $routes = array();

    /**
     * Merger two routes collections.
     *
     * @param RouteCollection $collection
     * @throws IdentifierDuplication
     * @returns RouteCollection New route collection instance
     */
    public function merge(RouteCollection $collection)
    {
        $newCollection = $this;

        /** @var Route $route */
        foreach ($collection as $route) {
            $newCollection->add($route);
        }

        return $newCollection;
    }

    /**
     * Add route.
     *
     * @param Route $route Route instance for addition
     * @throws IdentifierDuplication
     * @return self Chaining
     */
    public function add(Route $route)
    {
        if (!isset($this->routes[$route->identifier])) {
            $this->routes[$route->identifier] = $route;
        } else {
            throw new IdentifierDuplication('Identifier['.$route->identifier.'] already exists');
        }

        return $this;
    }

    /**
     * Generate a hash representing current routes collection state.
     *
     * @param string $salt
     * @return string Hash string representing routes collection
     */
    public function hash($salt = '')
    {
        $hash = '';
        foreach ($this->routes as $identifier => $route) {
            $hash = md5($identifier.$hash);
        }
        return md5($hash.$salt);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->routes[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->routes[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // If offset is passed, meaning $routes['routename'] = ....
        if (isset($offset)) {
            // Change route identifier to passed
            $value->identifier = $offset;
        }

        // Add Route to collection
        $this->add($value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->routes[$offset]);
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return current($this->routes);
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->routes);
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->routes);
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        $key = key($this->routes);
        return ($key !== null && $key !== false);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->routes);
    }
}
