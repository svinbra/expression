<?php declare(strict_types=1);

namespace Expression;

class Token
{
    /** @var int */
    private $typeInfo;
    /** @var \Expression\Location */
    public $location;
    /** @var int|string|null */
    public $value;

    public function __construct(int $type, Location $location, $value = null)
    {
        $tokenType = new TokenType();
        $this->typeInfo = $tokenType->info($type);
        $this->location = $location;
        $this->value = $value;
    }

    public function type()
    {
        return $this->typeInfo['type'];
    }

    public function precedence(): int
    {
        return $this->typeInfo['precedence'];
    }
}
