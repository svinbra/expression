<?php declare(strict_types=1);

namespace Expression;

class Lexer
{
    const UNKNOWN_TOKEN = 1;

    const VARIABLE = 100;
    const END_OF_STATEMENT = 101;
    const LITERAL = 102;

    // OPERATORS
    // Precedence 1 n/a
    const OPEN_BRACKET = 200;
    const CLOSE_BRACKET = 201;

    // Precedence 2 right associative
    const OPEN_SQUARE_BRACKET = 202;
    const CLOSE_SQUARE_BRACKET = 203;
    const UNARY_NOT = 204;
    const UNARY_BITWISE_NOT = 205;
    const UNARY_PRE_INCREMENT = 206;
    const UNARY_POST_INCREMENT = 207;
    const UNARY_PRE_DECREMENT = 208;
    const UNARY_POST_DECREMENT = 209;

    // Precedence 3 left associative
    const MULTIPLICATION = 210;
    const DIVISION = 211;
    const MODULUS = 212;

    // Precedence 4 left associative
    const ADDITION = 213;
    const SUBTRACTION = 214;
    const STRING_CONCAT = 215;

    // Precedence 5 left associative
    const SHIFT_LEFT = 216;
    const SHIFT_RIGHT = 217;

    // Precedence 6 n/a
    const LESS_THAN = 218;
    const LESS_THAN_OR_EQUAL = 219;
    const GREATER_THAN = 220;
    const GREATER_THAN_OR_EQUAL = 221;

    // Precedence 7 n/a
    const EQUAL = 222;
    const NOT_EQUAL = 223;
    const IDENTICAL = 224;
    const NOT_IDENTICAL = 225;

    // Precedence 8 left associative
    const BITWISE_AND = 226;

    // Precedence 9 left associative
    const BITWISE_XOR = 227;

    // precedence 10 left associative
    const BITWISE_OR = 228;

    // Precedence 11 left associative
    const LOGIC_AND = 229;

    // Precedence 12
    const LOGIC_OR = 230;

    // Precedence 14 left associative
    const TERNARY = 231;
    // Precedence 13 left associative
    const TERNARY_SEPARATOR = 232;

    // Precedence 15 left associative
    const ASSIGNMENT = 233;
    const ADDITION_ASSIGNMENT = 234;
    const SUBTRACTION_ASSIGNMENT = 235;
    const MULTIPLICATION_ASSIGNMENT = 236;
    const DIVISION_ASSIGNMENT = 237;
    const CONCAT_ASSIGNMENT = 238;
    const MODULUS_ASSIGNMENT = 239;
    const BITWISE_OR_ASSIGNMENT = 240;
    const BITWISE_XOR_ASSIGNMENT = 241;
    const BITWISE_NOT_ASSIGNMENT = 242;
    const SHIFT_LEFT_ASSIGNMENT = 243;
    const SHIFT_RIGHT_ASSIGMENT = 244;

    /** @var array */
    private $operators = [];
    /** @var string */
    public $source;
    /** @var int */
    private $sourceLength;
    /** @var int */
    public $column;
    /** @var Token */
    private $previousToken;

    public function __construct(string $source)
    {
        $this->source = $source;
        $this->sourceLength = strlen($source);
        $this->column = 0;

        // Multi char tokens
        $this->operator(self::EQUAL, '==', 7, false);
        $this->operator(self::NOT_EQUAL, '!=', 7, false);
        $this->operator(self::IDENTICAL, '===', 7, false);
        $this->operator(self::NOT_IDENTICAL, '!==', 7, false);

        $this->operator(self::ADDITION_ASSIGNMENT, '+=', 15, false);
        $this->operator(self::SUBTRACTION_ASSIGNMENT, '-=', 15, false);
        $this->operator(self::MULTIPLICATION_ASSIGNMENT, '*=', 15, false);
        $this->operator(self::DIVISION_ASSIGNMENT, '/=', 15, false);
        $this->operator(self::CONCAT_ASSIGNMENT, '.=', 15, false);
        $this->operator(self::MODULUS_ASSIGNMENT, '%=', 15, false);
        $this->operator(self::BITWISE_OR_ASSIGNMENT, '|=', 15, false);
        $this->operator(self::BITWISE_XOR_ASSIGNMENT, '^=', 15, false);
        $this->operator(self::BITWISE_NOT_ASSIGNMENT, '~=', 15, false);
        $this->operator(self::SHIFT_LEFT_ASSIGNMENT, '<<=', 15, false);
        $this->operator(self::SHIFT_RIGHT_ASSIGMENT, '>>=', 15, false);

        $this->operator(self::LOGIC_AND, '&&', 11, false);
        $this->operator(self::LOGIC_OR, '||', 12, false);

        // Single char tokens
        $this->operator(self::OPEN_BRACKET, '(', 1, false);
        $this->operator(self::CLOSE_BRACKET, ')', 1, false);

        $this->operator(self::OPEN_SQUARE_BRACKET, '[', 2, true);
        $this->operator(self::CLOSE_SQUARE_BRACKET, ']', 2, true);
        $this->operator(self::UNARY_NOT, '!', 2, true);
        $this->operator(self::UNARY_BITWISE_NOT, '~', 2, true);
        $this->operator(self::UNARY_PRE_INCREMENT, '++', 2, true);
        $this->operator(self::UNARY_POST_INCREMENT, '++', 2, true);
        $this->operator(self::UNARY_PRE_DECREMENT, '--', 2, true);
        $this->operator(self::UNARY_POST_DECREMENT, '--', 2, true);

        $this->operator(self::MULTIPLICATION, '*', 3, false);
        $this->operator(self::DIVISION, '/', 3, false);
        $this->operator(self::MODULUS, '%', 3, false);

        $this->operator(self::ADDITION, '+', 4, false);
        $this->operator(self::SUBTRACTION, '-', 4, false);
        $this->operator(self::STRING_CONCAT, '.', 4, false);

        $this->operator(self::SHIFT_LEFT, '<<', 5, false);
        $this->operator(self::SHIFT_RIGHT, '>>', 5, false);

        $this->operator(self::LESS_THAN_OR_EQUAL, '<=', 6, false);
        $this->operator(self::GREATER_THAN_OR_EQUAL, '>=', 6, false);
        $this->operator(self::LESS_THAN, '<', 6, false);
        $this->operator(self::GREATER_THAN, '>', 6, false);

        $this->operator(self::BITWISE_AND, '&', 8, false);
        $this->operator(self::BITWISE_XOR, '^', 8, false);
        $this->operator(self::BITWISE_OR, '|', 8, false);

        $this->operator(self::TERNARY, '?', 14, false);
        $this->operator(self::TERNARY_SEPARATOR, ':', 13, false);

        $this->operator(self::ASSIGNMENT, '=', 15, true);
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
     * @throws \Expression\CompilerError
     */
    public function eatToken(): ?Token
    {
        $operator = false;

        while (ctype_space(substr($this->source, $this->column, 1))) {
            $this->column += 1;
        }
        if ($this->column >= $this->sourceLength) return null;

        $result = new Token();
        if ($this->equals('++')) {
            $operator = true;
        }
        foreach ($this->operators as $key => $info) {
            if ($this->equals($info['symbol'])) {
                $result->type = $key;
                $result->typeInfo = $info;
                $operator = true;
            }
        }

        if (!$operator) {
            if ($this->equals(';')) {
                $result->type = Lexer::END_OF_STATEMENT;
                return $result;
            } elseif (is_numeric(substr($this->source, $this->column, 1))) {
                $start = $this->column;
                while (is_numeric(substr($this->source, $this->column, 1))) {
                    $this->column += 1;
                }
                $result->type = Lexer::LITERAL;
                $result->value = (int)substr($this->source, $start, $this->column - $start);
                return $result;
            } elseif ($this->equals('$')) {
                $start = $this->column - 1;
                if (ctype_alpha(substr($this->source, $this->column, 1))) {
                    $this->column += 1;
                } else {
                    $this->error('Invalid variable name');
                }
                while (ctype_alnum(substr($this->source, $this->column, 1))) {
                    $this->column += 1;
                }
                $result->type = Lexer::VARIABLE;
                $result->string = substr($this->source, $start, $this->column - $start);
                return $result;
            } else {
                $result->type = Lexer::UNKNOWN_TOKEN;
                return $result;
            }
        }

        $this->previousToken = $result;
        return $result;
    }

    /**
     * @return \Expression\Token|null
     * @throws \Expression\CompilerError
     */
    public function peekToken(): ?Token
    {
        $storeColumn = $this->column;
        $token = $this->eatToken();
        $this->column = $storeColumn;
        return $token;
    }

    public function equals($needle)
    {
        $result = false;
        if (substr($this->source, $this->column, strlen($needle)) === $needle) {
            $this->column += strlen($needle);
            $result = true;
        }

        return $result;
    }

    /**
     * @param int $tokenType
     *
     * @return bool
     * @throws \Expression\CompilerError
     */
    public function expect(int $tokenType): bool
    {
        $token = $this->peekToken();
        return !is_null($token) && $token->type === $tokenType;
    }

    public function isEmpty(): bool
    {
        return $this->sourceLength === 0 || $this->column >= $this->sourceLength;
    }

    /**
     * @param string $message
     *
     * @throws \Expression\CompilerError
     */
    public function error(string $message)
    {
        throw new CompilerError("Error on column {$this->column}: $message" . substr($this->source, $this->column));
    }
}
