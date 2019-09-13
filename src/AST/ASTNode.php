<?php

namespace Expression\AST;

use Expression\Token;

class ASTNode
{
    /** @var \Expression\AST\ASTNode */
    public $leftNode;
    /** @var \Expression\AST\ASTNode */
    public $rightNode;
    /** @var Token */
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
