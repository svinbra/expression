<?php declare(strict_types=1);

namespace Expression;

class Token
{
    /** @var int */
    public $type;
    /** @var array */
    public $typeInfo = [];
    /** @var string */
    public $string = '';
    /** @var int */
    public $value = 0;

    public function ofType(int $tokenType): bool
    {
        // Lets just use expect for now
        return $this->type === $tokenType;
    }

    public function precedence(): int
    {
        return $this->typeInfo['precedence'];
    }

    public function isRightAssociative(): bool
    {
        return $this->typeInfo['rightAssociative'];
    }
}

