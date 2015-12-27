<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

use samsonframework\routing\exception\IdentifierDuplication;
use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;

class RouteCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function routeCallback()
    {

    }

    public function testAdd()
    {
        $route = new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'));
        $collection = new RouteCollection();
        $collection->add($route);
    }

    public function testAddWithDuplicateIdentifier()
    {
        $this->setExpectedException('\samsonframework\routing\exception\IdentifierDuplication');

        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), 'myRoute'));
        $collection->add(new Route('/user/{id:\d}/list/valid', array($this, 'routeCallback'), 'myRoute'));
    }

    public function testMerge()
    {
        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), 'myRoute'));

        $collection2 = new RouteCollection();
        $collection2->add(new Route('/user/{id:\d}/list/valid', array($this, 'routeCallback'), 'myRoute2'));

        $collection2 = $collection2->merge($collection);

        $this->assertArrayHasKey('myRoute', $collection2);
        $this->assertArrayHasKey('myRoute2', $collection2);
    }

    public function testHash()
    {
        $identifier = 'myRoute';
        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), $identifier));

        $this->assertEquals(md5(md5($identifier)), $collection->hash());
    }

    public function testIterator()
    {
        $identifier = 'myRoute';
        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), $identifier));

        $this->assertEquals($identifier, $collection->key());

        foreach ($collection as $route) {
            $this->assertEquals($identifier, $route->identifier);
        }

        $route = new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'));
        $collection['TestRoute'] = $route;
        $this->assertArrayHasKey('TestRoute', $collection);

        $this->assertEquals($route, $collection['TestRoute']);

        unset($collection[$identifier]);
        $this->assertEquals(false, isset($collection[$identifier]));

    }
}
