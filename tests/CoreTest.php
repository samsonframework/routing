<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

use samsonframework\routing\Core;
use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function routeCallback($parameter, $optionalParameter = '1')
    {
        return true;
    }

    public function testMissingRouterLogic()
    {
        $this->setExpectedException('\samsonframework\routing\exception\FailedLogicCreation');

        $core = new Core(new RouteCollection());
    }

    public function testCreation()
    {
        require('DummyRouterLogic.php');

        $identifier = 'MyRoute';
        $collection = new RouteCollection();
        $collection->add(new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'), $identifier));

        $core = new Core($collection);
        /** @var Route $route */
        $route = null;
        $dispatchingResult = $core->dispatch(
            '/user/123/form/valid',
            Route::METHOD_GET,
            $route
        );

        $this->assertEquals(true, $dispatchingResult);
        $this->assertEquals($identifier, $route->identifier);
    }
}