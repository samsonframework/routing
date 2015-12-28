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

    public function testGeneration()
    {
        $routes = new RouteCollection();
        $routes[] = new Route('/', array($this, 'baseCallback'));
        $routes[] = new Route('/{page}', array($this, 'baseWithPageCallback'));
//
//        $routes[] = new Route('/user/', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/edit', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/save', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{id:0-9}/remove', array($this, 'routeCallback'));
//        $routes[] = new Route('/user/{page:0-9}/{filter}', array($this, 'routeCallback'));

        $generator = new Generator();
        $routerLogic = $generator->generate($routes, '__router'.rand(0, 1000));

        eval($routerLogic);

        $result = __router('/', Route::METHOD_GET);

        var_dump($result);
        echo($routerLogic);
    }
}