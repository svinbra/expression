<?php declare(strict_types=1);

namespace Expression\AST;

use Expression\Token2;

class ASTBinaryOperator extends ASTNode
{
    public function __construct(Token2 $operator, ASTNode $leftOperand, ASTNode $rightOperand)
    {
        $this->type = $operator->type;
        $this->leftNode = $leftOperand;
        $this->rightNode = $rightOperand;
    }
}
