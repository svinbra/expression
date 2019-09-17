<?php declare(strict_types=1);

namespace Expression;

class Location
{
    public $line;
    public $column;

    public function __construct(int $line, int $column)
    {
        $this->line = $line;
        $this->column = $column;
    }
}
