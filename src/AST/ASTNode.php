<?php

namespace Expression\AST;

use Expression\Token2;

class ASTNode
{
    /** @var \Expression\AST\ASTNode */
    public $leftNode;
    /** @var \Expression\AST\ASTNode */
    public $rightNode;
    /** @var Token2 */
    public $type;
    /** @var int */
    public $value;
    /** @var string */
    public $name;

    public function ofType(int $type)
    {
        return $this->type === $type;
    }
}
