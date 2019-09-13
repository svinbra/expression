<?php declare(strict_types=1);

namespace Expression\AST;

class ASTIdentifier extends ASTNode
{
    public function __construct(string $identifier)
    {
        $this->name = $identifier;
    }
}
