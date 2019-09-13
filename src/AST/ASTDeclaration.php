<?php declare(strict_types=1);


namespace Expression\AST;


class ASTDeclaration extends ASTNode
{
    public function __construct(ASTNode $identifier, ASTNode $expression)
    {
       $this->leftNode = $identifier;
       $this->rightNode = $expression;
    }
}
