<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 06.01.16
 * Time: 13:58
 */

namespace samsonframework\routing\tests;


use samsonframework\routing\generator\Structure;
use samsonframework\routing\Route;
use samsonframework\routing\RouteCollection;
use samsonphp\generator\Generator;

class FastRouteTest extends \PHPUnit_Framework_TestCase
{
    public function baseCallback()
    {
        return __FUNCTION__;
    }

    public function testPerformance()
    {
        // Create routes descriptions with identifiers
        $routeArray = [
            'main-page'                     => ['GET', '/', '/'],
            'inner-page'                    => ['GET','/{page}', '/text-page'],
            'user-home'                     => ['GET','/user/', '/user/'],
            //'user-home-without-slash'       => ['GET','/user'],
            //'user-winners-slash'            => ['GET','/user/winners/'],
            'user-by-id'                    => ['GET','/user/{id}', '/user/123'],
            'user-by-gender-age'            => ['GET','/user/{gender:male|female}/{age}', '/user/male/19d'],
            //'user-by-gender-age-filtered'   => ['GET','/user/{gender:male|female}/{age:[0-9]+}', '/user/female/8'],
            'user-by-id-form'               => ['GET','/user/{id}/form', '/user/123/form'],
            'user-by-id-friends'            => ['GET','/user/{id}/friends', '/user/123/friends'],
            'user-by-id-friends-with-id'    => ['GET','/user/{id}/friends/{groupid}', '/user/123/friends/1'],
            //'entity-by-id-form'             => ['GET','/{entity}/{id}/form'],
            //'entity-by-id-form-test'        => ['GET','/{id}/test/{page:\d+}'],
            //'two-params'                    => ['GET','/{num}/{page:\d+}'],
            //'user-by-id-node'               => ['GET','/user/{id}/n"$ode'],
            //'user-by-id-node-with-id'       => ['GET','/user/{id}/n"$ode/{param}'],
            'user-with-empty'               => ['GET','/user/{id}/get', '/user/123/get']
        ];

        // Create samsonframework\routing routes collection
        $routes = new RouteCollection();
        foreach ($routeArray as $identifier => $routeData) {
            $routes->add(new Route($routeData[1], array($this, 'baseCallback'), $identifier, $routeData[0]));
        }

        // Generate routing logic
        $generator = new Structure($routes, new Generator());
        $routerLogicFunction = '__router'.rand(0, 1000);
        $routerLogic = $generator->generate($routerLogicFunction);

        // Create real file for debugging
        file_put_contents(__DIR__.'/testLogic.php', '<?php '."\n".$routerLogic);
        require(__DIR__.'/testLogic.php');

        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routeArray) {
            foreach ($routeArray as $identifier => $routeData) {
                $r->addRoute($routeData[0], $routeData[1], $identifier);
            }
        }, ['cacheFile' => __DIR__ . '/route.cache']);


        $elapsedNikic = [];
        $elapsedSamson = [];

        $iterationsCount = 1000;
        for ($i=0; $i < $iterationsCount; $i++) {
            foreach ($routeArray as $identifier => $routeData) {
                if (isset($routeData[2])) {
                    $url = rawurldecode(parse_url($routeData[2], PHP_URL_PATH));
                    $timestamp = microtime(true);
                    $routeInfo = $dispatcher->dispatch($routeData[0], $url);
                    $elapsedNikic[$routeData[1]][] = microtime(true) - $timestamp;
                    $this->assertEquals($routeInfo[1], $identifier);
                }
            }

            foreach ($routeArray as $identifier => $routeData) {
                if (isset($routeData[2])) {
                    $timestamp = microtime(true);
                    $routeInfo = $routerLogicFunction($routeData[2], $routeData[0]);
                    $elapsedSamson[$routeData[1]][] = microtime(true) - $timestamp;
                    $this->assertEquals($routeInfo[0], $identifier);
                }
            }
        }

        $score = 0;
        $score2 = 0;

        foreach ($elapsedNikic as $step => $iterations) {
            for ($i = 0; $i < $iterationsCount; $i++) {
                // Compare if nikic elapsed more time on this iteration for this route
                if ($elapsedNikic[$step][$i] > $elapsedSamson[$step][$i]) {
                    $score++;
                } else {
                    $score2++;
                }
            }
        }

        echo 'On '.$iterationsCount.' iterations SamsonFramework\routing was faster '.$score.' times '.
            'opposite to nikic\FastRoute '.$score2.' times, which is '.number_format($score/$score2, 1).' times faster.';
    }
}
