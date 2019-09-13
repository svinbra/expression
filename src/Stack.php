<?php declare(strict_types=1);

namespace Expression;

class Stack
{
    /** @var array */
    private $stack = [];

    public function size(): int
    {
        return count($this->stack);
    }

    public function clear()
    {
        $this->stack = [];
    }

    public function push($item)
    {
        array_push($this->stack, $item);
    }

    public function pop()
    {
        return array_pop($this->stack);
    }

    public function peek()
    {
        if ($this->size() === 0) return null;
        return $this->stack[count($this->stack) - 1];
    }

    public function list(): array
    {
        return $this->stack;
    }
}
