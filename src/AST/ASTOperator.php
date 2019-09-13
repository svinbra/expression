<?php declare(strict_types=1);

namespace Expression\AST;

use Expression\Token;

class ASTOperator extends ASTNode
{
    public function __construct(Token $operator, ASTNode $leftOperand, ASTNode $rightOperand)
    {
        $this->type = $operator->type;
        $this->leftNode = $leftOperand;
        $this->rightNode = $rightOperand;
    }
}
