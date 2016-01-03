<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 10:42
 */
namespace samsonframework\routing\generator;

/**
 * Routing logic parametrized structure node.
 *
 * @package samsonframework\routing\generator
 */
class ParameterNode extends Node
{
    /** @var string Node parameter name */
    public $name;

    /** @var string Node regular expression pattern */
    public $regexp;

    /**
     * ParameterNode constructor.
     *
     * @param string $name Parameter name
     * @param string|null $regexp Parameter regexp filter
     */
    public function __construct($name, $regexp = null)
    {
        $this->name = $name;
        $this->regexp = $regexp;
    }
}
