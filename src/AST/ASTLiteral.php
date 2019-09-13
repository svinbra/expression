<?php declare(strict_types=1);

namespace Expression\AST;

class ASTLiteral extends ASTNode
{
    public function __construct(int $value)
    {
       $this->value = $value;
    }
}
