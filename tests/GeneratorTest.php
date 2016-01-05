<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 28.10.15
 * Time: 11:28
 */
namespace samsonframework\routing\tests;

use samsonframework\routing\Generator;
use samsonframework\routing\generator\Structure;
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

    public function userWithIDFormCallback($id)
    {
        return array(__FUNCTION__, $id);
    }

    public function entityWithIDFormCallback($entity, $id)
    {
        return __FUNCTION__;
    }

    public function testGeneration()
    {
        $routes = new RouteCollection();
        $routes['main-page'] = new Route('/', array($this, 'baseCallback'));
        $routes['inner-page'] = new Route('/{page}', array($this, 'baseWithPageCallback'));
        // This one would be overridden by next route due to automatic slash addition to the end of the route
        $routes['user-home'] = new Route('/user/', array($this, 'baseCallback'));
        $routes['user-home-without-slash'] = new Route('/user', array($this, 'baseCallback'));
        $routes['user-winners-slash'] = new Route('/user/winners/', array($this, 'baseCallback'));
        $routes['user-by-id'] = new Route('/user/{id}', array($this, 'userWithIDCallback'));
        $routes['user-by-gender-age'] = new Route('/user/{gender:(male|female)}/{age}', array($this, 'userWithIDCallback'));
        $routes['user-by-gender-age-filtered'] = new Route('/user/{gender:(male|female)}/{age:[0-9]+}', array($this, 'userWithIDCallback'));
        $routes['user-by-id-form'] = new Route('/user/{id}/form', array($this, 'userWithIDFormCallback'));
        $routes['user-by-id-friends'] = new Route('/user/{id}/friends', array($this, 'userWithIDFormCallback'));
        $routes['user-by-id-friends-with-id'] = new Route('/user/{id}/friends/{groupid}', array($this, 'userWithIDFormCallback'));
        $routes['entity-by-id-form'] = new Route('/{entity}/{id}/form', array($this, 'entityWithIDFormCallback'));
        $routes['entity-by-id-form-test'] = new Route('/{id}/test/{page:\d+}', array($this, 'entityWithIDFormCallback'));
        $routes['two-params'] = new Route('/{num}/{page:\d+}', array($this, 'entityWithIDFormCallback'));
        $routes['user-by-id-node'] = new Route('/user/{id}/n"$ode', array($this, 'userWithIDFormCallback'));
        $routes['user-by-id-node-with-id'] = new Route('/user/{id}/n"$ode/{param}', array($this, 'userWithIDFormCallback'));
        $routes['user-with-empty'] = new Route('/user/{id}/get', array($this, 'userWithIDCallback'));

        $generator = new Structure($routes, new \samsonphp\generator\Generator());
        $routerLogicFunction = '__router'.rand(0, 1000);
        $routerLogic = $generator->generate($routerLogicFunction);

        // Create real file for debugging
        file_put_contents(__DIR__.'/testLogic.php', '<?php '."\n".$routerLogic);
        require(__DIR__.'/testLogic.php');

        $result = $routerLogicFunction('/', Route::METHOD_GET);
        $this->assertEquals('main-page', $result[0]);

        $result = $routerLogicFunction('/', Route::METHOD_POST);
        $this->assertEquals(null, $result[0]);

        $result = $routerLogicFunction('/user/winners/', Route::METHOD_GET);
        $this->assertEquals('user-winners-slash', $result[0]);

        $result = $routerLogicFunction('/123', Route::METHOD_GET);
        $this->assertEquals('inner-page', $result[0]);
        $this->assertArrayHasKey('page', $result[1]);

        $result = $routerLogicFunction('/123/23123', Route::METHOD_GET);
        $this->assertEquals('two-params', $result[0]);

        $result = $routerLogicFunction('/user/123', Route::METHOD_GET);
        $this->assertEquals('user-by-id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/user/123/form', Route::METHOD_GET);
        $this->assertEquals('user-by-id-form', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/user/123/friends', Route::METHOD_GET);
        $this->assertEquals('user-by-id-friends', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/user/male/18+', Route::METHOD_GET);
        $this->assertEquals('user-by-gender-age', $result[0]);
        $this->assertArrayHasKey('gender', $result[1]);
        $this->assertArrayHasKey('age', $result[1]);

        $result = $routerLogicFunction('/user/123/friends/321', Route::METHOD_GET);
        $this->assertEquals('user-by-id-friends-with-id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertArrayHasKey('groupid', $result[1]);

        $result = $routerLogicFunction('/friend/123/form', Route::METHOD_GET);
        $this->assertEquals('entity-by-id-form', $result[0]);
        $this->assertArrayHasKey('entity', $result[1]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/123/test/1', Route::METHOD_GET);
        $this->assertEquals('entity-by-id-form-test', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertArrayHasKey('page', $result[1]);

        // We consider that all routes are having slash at the end
        $result = $routerLogicFunction('/user/', Route::METHOD_GET);
        $this->assertEquals('user-home', $result[0]);

        $result = $routerLogicFunction('/user', Route::METHOD_GET);
        $this->assertEquals('user-home', $result[0]);

        $result = $routerLogicFunction('/user/123/n"$ode', Route::METHOD_GET);
        $this->assertEquals('user-by-id-node', $result[0]);

        $result = $routerLogicFunction('/user/123/n"$ode/321', Route::METHOD_GET);
        $this->assertEquals('user-by-id-node-with-id', $result[0]);

        // TODO Fixed this
        //$result = $routerLogicFunction('/user//get', Route::METHOD_GET);
        //$this->assertEquals('user-with-empty', $result[0]);
    }
}