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

class GeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function routeCallback()
    {

    }

    public function testRouteToArrayDefinition()
    {
        $route = new Route('/user/{id:\d}/form/valid', array($this, 'routeCallback'));

        $generator = new Generator();

    }
}