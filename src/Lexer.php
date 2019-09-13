<?php declare(strict_types=1);

namespace Expression;

class Lexer
{
    const UNKNOWN_TOKEN = 1;

    const VARIABLE = 100;
    const END_OF_STATEMENT = 101;
    const LITERAL = 102;

    // OPERATORS
    // Precedence 1
    const OPEN_BRACKET = 200;
    const CLOSE_BRACKET = 201;

    // Precedence 3
    const MULTIPLICATION = 202;
    const DIVISION = 203;
    const MODULUS = 204;

    // Precedence 4
    const ADDITION = 205;
    const SUBTRACTION = 206;

    // Precedence 6
    const GREATER_THAN = 207;
    const LESS_THAN = 208;
    const GREATER_THAN_OR_EQUAL = 209;
    const LESS_THAN_OR_EQUAL = 210;

    // Precedence 11
    const LOGIC_AND = 211;

    // Precedence 12
    const LOGIC_OR = 212;

    // Precedence 13
    const TERNARY = 213;
    const TERNARY_SEPARATOR = 214;

    // Precedence 7
    const EQUAL = 215;
    const NOT_EQUAL = 216;

    // Precedence 14
    const ASSIGNMENT = 217;

    /** @var array */
    private $operators = [];
    /** @var string */
    private $expression;
    /** @var int */
    private $expressionLength;
    /** @var int */
    private $column;

    public function __construct()
    {
        // Sorted in order of greediness, we want to "eat" as much of the string as possible when lexing.
        $this->operator(self::GREATER_THAN_OR_EQUAL, '>=', 6, true);
        $this->operator(self::LESS_THAN_OR_EQUAL, '<=', 6, true);
        $this->operator(self::EQUAL, '==', 7, true);
        $this->operator(self::NOT_EQUAL, '!=', 7, true);
        $this->operator(self::LOGIC_AND, '&&', 11, true);
        $this->operator(self::LOGIC_OR, '||', 12, true);

        $this->operator(self::OPEN_BRACKET, '(', 1, false);
        $this->operator(self::CLOSE_BRACKET, ')', 1, false);
        $this->operator(self::MULTIPLICATION, '*', 3, false);
        $this->operator(self::DIVISION, '/', 3, false);
        $this->operator(self::MODULUS, '%', 3, false);
        $this->operator(self::ADDITION, '+', 4, false);
        $this->operator(self::SUBTRACTION, '-', 4, false);
        $this->operator(self::GREATER_THAN, '>', 6, true);
        $this->operator(self::LESS_THAN, '<', 6, true);
        $this->operator(self::TERNARY, '?', 13, false);
        $this->operator(self::TERNARY_SEPARATOR, ':', 13, false);
    }

    private function operator(int $type, string $symbol, int $precedence, bool $rightAssociative)
    {
        $this->operators[$type] = [
            'symbol' => $symbol,
            'precedence' => $precedence,
            'rightAssociative' => $rightAssociative
        ];
    }

    /**
     * @return \Expression\Token|null
     */
    public function eatToken(): ?Token
    {
        while (substr($this->expression, $this->column, 1) === ' ') {
            $this->column += 1;
        }
        if ($this->column >= $this->expressionLength) return null;

        $result = new Token();
        foreach ($this->operators as $key => $info) {
            if ($this->equals($info['symbol'])) {
                $result->type = $key;
                return $result;
            }
        }

        if ($this->equals(';')) {
            $result->type = Lexer::END_OF_STATEMENT;
            return $result;
        } elseif (is_numeric(substr($this->expression, $this->column, 1))) {
            $start = $this->column;
            while (is_numeric(substr($this->expression, $this->column, 1))) {
                $this->column += 1;
            }
            $result->type = Lexer::LITERAL;
            $result->value = (int)substr($this->expression, $start, $this->column - $start);
            return $result;
        } elseif (ctype_alpha(substr($this->expression, $this->column, 1))) {
            $start = $this->column;
            while (ctype_alnum(substr($this->expression, $this->column, 1))) {
                $this->column += 1;
            }
            $result->type = Lexer::VARIABLE;
            $result->string = substr($this->expression, $start, $this->column - $start);
            return $result;
        } else {
            $result->type = Lexer::UNKNOWN_TOKEN;
            return $result;
        }
    }

    /**
     * @return \Expression\Token|null
     */
    public function peekToken(): ?Token
    {
        $storeColumn = $this->column;
        $token = $this->eatToken();
        $this->column = $storeColumn;
        return $token;
    }

    private function equals($needle)
    {
        $result = false;
        if (substr($this->expression, $this->column, strlen($needle)) === $needle) {
            $this->column += strlen($needle);
            $result = true;
        }

        return $result;
    }
}
