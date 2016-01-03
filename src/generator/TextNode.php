<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 03.01.16
 * Time: 10:42
 */
namespace samsonframework\routing\generator;

/**
 * Routing logic textual structure node.
 *
 * @package samsonframework\routing\generator
 */
class TextNode extends Node
{
    /** @var string Node textual content */
    public $content;

    /**
     * TextNode constructor.
     *
     * @param string $content Node textual content
     */
    public function __construct($content)
    {
        $this->content = $content;
    }
}
