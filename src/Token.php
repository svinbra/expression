<?php declare(strict_types=1);

namespace Expression;

class Token
{
    const VARIABLE = 1;
    const END_OF_STATEMENT = 2;
    const LITERAL = 3;

    // OPERATORS

    // Precedence 1
    const OPEN_BRACKET = 4;
    const CLOSE_BRACKET = 5;

    // Precedence 3
    const MULTIPLICATION = 6;
    const DIVISION = 7;
    const MODULUS = 8;

    // Precedence 4
    const ADDITION = 9;
    const SUBTRACTION = 10;

    // Precedence 6
    const GREATER_THAN = 11;
    const LESS_THAN = 12;
    const GREATER_THAN_OR_EQUAL = 13;
    const LESS_THAN_OR_EQUAL = 14;

    // Precedence 11
    const LOGIC_AND = 15;

    // Precedence 12
    const LOGIC_OR = 16;

    // Precedence 13
    const TERNARY = 17;
    const TERNARY_SEPARATOR = 18;

    // Precedence 7
    const EQUAL = 19;
    const NOT_EQUAL = 20;

    // Precedence 14
    const ASSIGNMENT = 21;


    /** @var int */
    public $type;
    /** @var string */
    public $string = '';
    /** @var int */
    public $value = 0;
    public $operators = [];

    public function __construct()
    {
        $this->operatorInfo(self::OPEN_BRACKET, 1, false, false);
        $this->operatorInfo(self::CLOSE_BRACKET, 1, false, false);

        $this->operatorInfo(self::MULTIPLICATION, 3, false, false);
        $this->operatorInfo(self::DIVISION, 3, false, false);
        $this->operatorInfo(self::MODULUS, 3, false, false);

        $this->operatorInfo(self::ADDITION, 4, false, false);
        $this->operatorInfo(self::SUBTRACTION, 4, false, false);

        $this->operatorInfo(self::GREATER_THAN, 6, false, true);
        $this->operatorInfo(self::LESS_THAN, 6, false, true);
        $this->operatorInfo(self::GREATER_THAN_OR_EQUAL, 6, false, true);
        $this->operatorInfo(self::LESS_THAN_OR_EQUAL, 6, false, true);

        $this->operatorInfo(self::EQUAL, 7, false, true);
        $this->operatorInfo(self::NOT_EQUAL, 7, false, true);

        $this->operatorInfo(self::LOGIC_AND, 11, false, true);
        $this->operatorInfo(self::LOGIC_OR, 12, false, true);

        $this->operatorInfo(self::TERNARY, 13, true, false);
        $this->operatorInfo(self::TERNARY_SEPARATOR, 13, true, false);
    }

    private function operatorInfo(int $symbol, int $precedence, bool $rightAssociative, bool $comparator)
    {
        $this->operators[$symbol] = [
            'precedence' => $precedence,
            'rightAssociative' => $rightAssociative,
            'comparator' => $comparator
        ];
    }

    public function ofType(int $tokenType): bool
    {
        // Lets just use expect for now
        return $this->type === $tokenType;
    }

    public function isOperator(): bool
    {
        return isset($this->operators[$this->type]);
    }

    public function precedence(): int
    {
        return $this->operators[$this->type]['precedence'];
    }

    public function isRightAssociative(): bool
    {
        return $this->operators[$this->type]['rightAssociative'];
    }

    public function isBooleanOperator(): bool
    {
        return $this->operators[$this->type]['comparator'];
    }

    public function __toString(): string
    {
        switch ($this->type) {
            case self::VARIABLE:
                return "Identifier";
            case self::END_OF_STATEMENT:
                return "End of statement";
            case self::LITERAL:
                return "Literal";
            case self::OPEN_BRACKET:
                return "Open bracket";
            case self::CLOSE_BRACKET:
                return "Close bracket";
            case self::MULTIPLICATION:
                return "Multiplication";
            case self::DIVISION:
                return "Division";
            case self::MODULUS:
                return "Modulus";
            case self::ADDITION:
                return "Addition";
            case self::SUBTRACTION:
                return "Substraction";
            case self::GREATER_THAN:
                return "Greater than";
            case self::LESS_THAN:
                return "Less than";
            case self::GREATER_THAN_OR_EQUAL:
                return "Greater than or equal";
            case self::LESS_THAN_OR_EQUAL:
                return "Less than or equal";
            case self::LOGIC_AND:
                return "Logic and";
            case self::LOGIC_OR:
                return "Logic or";
            case self::TERNARY:
                return "Ternary";
            case self::TERNARY_SEPARATOR:
                return "Ternary then/else";
            case self::EQUAL:
                return "Equal";
            case self::NOT_EQUAL:
                return "Not equal";
            case self::ASSIGNMENT:
                return "Assignment";
            default:
                return "Unknown token";
        }
    }
}

