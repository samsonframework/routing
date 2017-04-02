<?php declare(strict_types = 1);
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 02.04.17 at 09:24
 */

namespace samsonframework\routing;

/**
 * Class Generator
 *
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
class Generator
{
    /** @var StringConditionTree Strings condition tree generator */
    protected $stringsTree;

    /** @var Generator PHP code generator */
    protected $phpGenerator;

    public function __construct()
    {
    }
}
