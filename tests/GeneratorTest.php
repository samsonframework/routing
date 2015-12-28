<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

use samsonframework\routing\Generator;
use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function baseCallback()
    {
        return __FUNCTION__;
    }

    public function baseWithPageCallback($page)
    {
        return __FUNCTION__;
    }

    public function userWithIDCallback($id)
    {
        return __FUNCTION__;
    }

    public function testGeneration()
    {
        $routes = new RouteCollection();
        $routes[] = new Route('/', array($this, 'baseCallback'), 'main-page', Route::METHOD_GET);
        $routes[] = new Route('/{page}', array($this, 'baseWithPageCallback'), 'inner-page', Route::METHOD_GET);
        $routes[] = new Route('/user/{id}/form', array($this, 'userWithIDCallback'), 'user-by-id', Route::METHOD_GET);
//
//        $routes[] = new Route('/user/', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/edit', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/save', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/remove', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{page:0-9}/{filter}', array($this, 'routeCallback'));

        $generator = new Generator();
        $routerLogicFunction = '__router'.rand(0, 1000);
        $routerLogic = $generator->generate($routes, $routerLogicFunction);

        eval($routerLogic);
        echo $routerLogic;

        $result = $routerLogicFunction('/', Route::METHOD_GET);
        $this->assertEquals('main-page', $result[0]);

        $result = $routerLogicFunction('/', Route::METHOD_POST);
        $this->assertEquals(null, $result[0]);

        $result = $routerLogicFunction('/123', Route::METHOD_GET);
        $this->assertEquals('inner-page', $result[0]);

        // BUG! We need to fix pattern matching with parameters
        $result = $routerLogicFunction('/123/23123', Route::METHOD_GET);
        $this->assertEquals('inner-page', $result[0]);

        $result = $routerLogicFunction('/user/123/form', Route::METHOD_GET);
        $this->assertEquals('user-by-id', $result[0]);
    }
}