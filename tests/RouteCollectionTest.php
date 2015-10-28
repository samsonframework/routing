<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

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

    /**
     * @throws \samsonframework\routing\exception\IdentifierDuplication
     */
    public function testAddWithDuplicateIdentifier()
    {
        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), 'myRoute'));
        $collection->add(new Route('/user/{id:\d}/list/valid', array($this, 'routeCallback'), 'myRoute2'));
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
}
