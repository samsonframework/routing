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
        $routes[] = new Route('/', array($this, 'baseCallback'), 'main-page', Route::METHOD_GET);
        $routes[] = new Route('/{page}', array($this, 'baseWithPageCallback'), 'inner-page', Route::METHOD_GET);
        $routes[] = new Route('/user/{id}', array($this, 'userWithIDCallback'), 'user-by-id', Route::METHOD_GET);
        $routes[] = new Route('/user/{id}/form', array($this, 'userWithIDFormCallback'), 'user-by-id-form', Route::METHOD_GET);
        $routes[] = new Route('/{entity:[a-z]+}/{id}/form', array($this, 'entityWithIDFormCallback'), 'entity-by-id-form', Route::METHOD_GET);

        $generator = new Generator();
        $routerLogicFunction = '__router'.rand(0, 1000);
        $routerLogic = $generator->generate($routes, $routerLogicFunction);

        // Create real file for debugging
        file_put_contents(__DIR__.'/testLogic.php', '<?php '."\n".$routerLogic);
        require(__DIR__.'/testLogic.php');

        $result = $routerLogicFunction('/', Route::METHOD_GET);
        $this->assertEquals('main-page', $result[0]);

        $result = $routerLogicFunction('/', Route::METHOD_POST);
        $this->assertEquals(null, $result[0]);

        $result = $routerLogicFunction('/123', Route::METHOD_GET);
        $this->assertEquals('inner-page', $result[0]);
        $this->assertArrayHasKey('page', $result[1]);

        $result = $routerLogicFunction('/123/23123', Route::METHOD_GET);
        $this->assertEquals(null, $result[0]);

        $result = $routerLogicFunction('/user/123', Route::METHOD_GET);
        $this->assertEquals('user-by-id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/user/123/form', Route::METHOD_GET);
        $this->assertEquals('user-by-id-form', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $routerLogicFunction('/friend/123/form', Route::METHOD_GET);
        $this->assertEquals('entity-by-id-form', $result[0]);
        $this->assertArrayHasKey('entity', $result[1]);
        $this->assertArrayHasKey('id', $result[1]);
    }
}