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
    protected $routerLogicFunction;

    /** Routing function wrapper */
    public function routerLogic($path, $method)
    {
        $path = rtrim(strtok($method.'/'.ltrim($path, '/'), '?'), '/');
        return call_user_func($this->routerLogicFunction, $path, $method);
    }
    
    public function testGeneration()
    {
        // Create routes descriptions with identifiers
        $routeArray = array(
            'main-page' => array('GET', '/', '/'),
            'inner-page' => array('GET', '/{page}', '/text-page'),
            'user-home' => array('GET', '/user/', '/user/'),
            'user-home-without-slash' => array('GET', '/user'),
            'test-two-similar-fixed' => array('GET', '/userlist'),
            'user-winners-slash' => array('GET', '/user/winners/'),
            'user-by-id' => array('GET', '/user/{id}', '/user/123'),
            'user-by-gender-age' => array('GET', '/user/{gender:male|female}/{age}', '/user/male/19d'),
            'user-by-gender-age-filtered' => array('GET', '/user/{gender:male|female}/{age:[0-9]+}', '/user/female/8'),
            'user-by-id-form' => array('GET', '/user/{id}/form', '/user/123/form'),
            'user-by-id-friends' => array('GET', '/user/{id}/friends', '/user/123/friends'),
            'user-by-id-friends-with-id' => array('GET', '/user/{id}/friends/{groupid}', '/user/123/friends/1'),
            'entity-by-id-form' => array('GET', '/{entity}/{id}/form'),
            'entity-by-id-form-test' => array('GET', '/{id}/test/{page:\d+}'),
            'two-params' => array('GET', '/{num}/{page:\d+}'),
            'user-by-id-node' => array('GET', '/user/{id}/n"$ode'),
            'user-by-id-node-with-id' => array('GET', '/user/{id}/n"$ode/{param}'),
            'user-with-empty' => array('GET', '/user/{id}/get', '/user/123/get')
        );

        // Create routes collection
        $routes = new RouteCollection();
        foreach ($routeArray as $identifier => $routeData) {
            $routes->add(new Route($routeData[1], array($this, 'baseCallback'), $identifier, $routeData[0]));
        }

        $generator = new Structure($routes, new \samsonphp\generator\Generator());
        $this->routerLogicFunction = '__router'.rand(0, 9999);
        $routerLogic = $generator->generate($this->routerLogicFunction);

        // Create real file for debugging
        file_put_contents(__DIR__.'/testLogic.php', '<?php '."\n".$routerLogic);
        require(__DIR__.'/testLogic.php');

        $result = $this->routerLogic('/', Route::METHOD_GET);
        $this->assertEquals('main-page', $result[0]);

        $result = $this->routerLogic('/', Route::METHOD_POST);
        $this->assertEquals(null, $result[0]);

        $result = $this->routerLogic('/user/winners/', Route::METHOD_GET);
        $this->assertEquals('user-winners-slash', $result[0]);

        $result = $this->routerLogic('/123', Route::METHOD_GET);
        $this->assertEquals('inner-page', $result[0]);
        $this->assertArrayHasKey('page', $result[1]);

        $result = $this->routerLogic('/123/23123', Route::METHOD_GET);
        $this->assertEquals('two-params', $result[0]);

        $result = $this->routerLogic('/user/123', Route::METHOD_GET);
        $this->assertEquals('user-by-id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $this->routerLogic('/user/123/form', Route::METHOD_GET);
        $this->assertEquals('user-by-id-form', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $this->routerLogic('/user/123/friends', Route::METHOD_GET);
        $this->assertEquals('user-by-id-friends', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $this->routerLogic('/user/male/18+', Route::METHOD_GET);
        $this->assertEquals('user-by-gender-age', $result[0]);
        $this->assertArrayHasKey('gender', $result[1]);
        $this->assertArrayHasKey('age', $result[1]);

        $result = $this->routerLogic('/user/123/friends/321', Route::METHOD_GET);
        $this->assertEquals('user-by-id-friends-with-id', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertArrayHasKey('groupid', $result[1]);

        $result = $this->routerLogic('/friend/123/form', Route::METHOD_GET);
        $this->assertEquals('entity-by-id-form', $result[0]);
        $this->assertArrayHasKey('entity', $result[1]);
        $this->assertArrayHasKey('id', $result[1]);

        $result = $this->routerLogic('/123/test/1', Route::METHOD_GET);
        $this->assertEquals('entity-by-id-form-test', $result[0]);
        $this->assertArrayHasKey('id', $result[1]);
        $this->assertArrayHasKey('page', $result[1]);

        // We consider that all routes are having slash at the end
        $result = $this->routerLogic('/user/', Route::METHOD_GET);
        $this->assertEquals('user-home-without-slash', $result[0]);

        $result = $this->routerLogic('/user', Route::METHOD_GET);
        $this->assertEquals('user-home-without-slash', $result[0]);

        $result = $this->routerLogic('/userlist/', Route::METHOD_GET);
        $this->assertEquals('test-two-similar-fixed', $result[0]);

        $result = $this->routerLogic('/user/123/n"$ode', Route::METHOD_GET);
        $this->assertEquals('user-by-id-node', $result[0]);

        $result = $this->routerLogic('/user/123/n"$ode/321', Route::METHOD_GET);
        $this->assertEquals('user-by-id-node-with-id', $result[0]);

        // Empty parameters are resolved to null route
        $result = $this->routerLogic('/user//get', Route::METHOD_GET);
        $this->assertEquals(null, $result[0]);
    }
}
