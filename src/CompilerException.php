<?php declare(strict_types=1);

namespace Expression;

class CompilerException extends \Exception {
    const ERROR = 1;

    /** @var \Expression\Location */
    private $location;
    /** @var int */
    private $type;

    public function __construct(int $type, string $message, Location $location)
    {
        $this->location = $location;
        $this->type = $type;
        parent::__construct($message);
    }
}
