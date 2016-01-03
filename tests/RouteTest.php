<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

use samsonframework\routing\Route;

function globalRouteCallback()
{

}

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function routeCallback($parameter, $optionalParameter = '1')
    {
        //dummy
    }

    public function testBaseToArrayDefinition()
    {
        $route = new Route(
            '/',
            'globalRouteCallback',
            'test-route',
            Route::METHOD_GET
        );

        $definition = $route->toArrayDefinition('$routeTree');

        $this->assertEquals(
            '$routeTree["GET"]["/"]["'.Route::ROUTE_KEY.'"]= $route->identifier;'."\n",
            $definition
        );
    }

    public function testToArrayDefinition()
    {
        $route = new Route(
            '/user/{id:\d}/form/valid',
            array($this, 'routeCallback'),
            'test-route',
            Route::METHOD_GET
        );

        $definition = $route->toArrayDefinition('$routeTree');

        $this->assertEquals(
            '$routeTree["GET"]["user"]["{id:\d}"]["form"]["valid"]["'.Route::ROUTE_KEY.'"]= $route->identifier;'."\n",
            $definition
        );
    }

    public function testAnyToArrayDefinition()
    {
        $route = new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), 'testRoute', Route::METHOD_ANY);
        $definition = $route->toArrayDefinition('$routeTree');

        $expected = '';
        foreach (Route::$httpMethods as $method) {
            $expected .= '$routeTree["'.$method.'"]["user"]["{id:\d}"]["form"]["valid"]["'.Route::ROUTE_KEY.'"]= $route->identifier;'."\n";
        }

        $this->assertEquals($expected, $definition);
    }
}
