<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 27.10.15
 * Time: 18:46
 */
namespace samsonframework\routing;

/**
 * Route generation Interface
 * @package samsonframework\routing
 */
interface RouteGeneratorInterface
{
    /**
     * @return RouteCollection Collection of generated routes
     */
    public function &generate();
}
