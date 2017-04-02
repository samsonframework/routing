<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:24
 */

namespace samsonframework\routing;

use samsonframework\generator\ClassGenerator;
use samsonframework\stringconditiontree\StringConditionTree;

/**
 * Routing function class generator.
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class Generator
{
    /** @var StringConditionTree Strings condition tree generator */
    protected $stringsTree;

    /** @var ClassGenerator PHP class code generator */
    protected $classGenerator;

    /**
     * Generator constructor.
     *
     * @param StringConditionTree $stringsTree Strings condition tree generator
     * @param ClassGenerator      $classGenerator PHP class code generator
     */
    public function __construct(StringConditionTree $stringsTree, ClassGenerator $classGenerator)
    {
        $this->classGenerator = $classGenerator;
        $this->stringsTree = $stringsTree;
    }
}
