<?php

namespace Expression;

class TokenType
{
    const GROUP_NONE = 1;
    const GROUP_IDENTIFIER = 2;
    const GROUP_BINARY_OPERATOR = 3;
    const GROUP_UNARY_OPERATOR = 4;
    const GROUP_ASSIGNMENT_OPERATOR = 5;
    const GROUP_EQUALITY_OPERATOR = 6;
    const GROUP_DELIMITER = 7;
    const GROUP_SYNTAX = 8;

    const UNKNOWN_TOKEN = 1;

    const VARIABLE = 100;
    const END_OF_STATEMENT = 101;
    const NUMBER = 102;
    const SINGLE_QUOTED_STRING = 103;
    const DOUBLE_QUOTED_STRING = 104;

    // OPERATORS
    // Precedence 1 n/a
    const OPEN_BRACKET = 200;
    const CLOSE_BRACKET = 201;

    // Precedence 2 right associative
    const OPEN_SQUARE_BRACKET = 202;
    const CLOSE_SQUARE_BRACKET = 203;
    const UNARY_NOT = 204;
    const UNARY_BITWISE_NOT = 205;
    const UNARY_INCREMENT = 206;
    const UNARY_DECREMENT = 209;

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
    private $tokensBySymbol = [];
    /** @var array */
    private $tokensByType = [];

    public function __construct()
    {
        $this->token(self::UNKNOWN_TOKEN, null, self::GROUP_NONE, null, null);
        $this->token(self::VARIABLE, null, self::GROUP_IDENTIFIER, null, null);
        $this->token(self::END_OF_STATEMENT, null, self::GROUP_SYNTAX, null, null);
        $this->token(self::NUMBER, null, self::GROUP_NONE, null, null);
        $this->token(self::SINGLE_QUOTED_STRING, null, self::GROUP_NONE, null, null);
        $this->token(self::DOUBLE_QUOTED_STRING, null, self::GROUP_NONE, null, null);

        // Operators
        $this->token(self::OPEN_BRACKET, '(', self::GROUP_DELIMITER, 1, false);
        $this->token(self::CLOSE_BRACKET, ')', self::GROUP_DELIMITER, 1, false);

        $this->token(self::OPEN_SQUARE_BRACKET, '[', self::GROUP_DELIMITER, 2, true);
        $this->token(self::CLOSE_SQUARE_BRACKET, ']', self::GROUP_DELIMITER, 2, true);
        $this->token(self::UNARY_NOT, '!', self::GROUP_UNARY_OPERATOR, 2, true);
        $this->token(self::UNARY_BITWISE_NOT, '~', self::GROUP_UNARY_OPERATOR, 2, true);
        $this->token(self::UNARY_INCREMENT, '++', self::GROUP_UNARY_OPERATOR, 2, true);
        $this->token(self::UNARY_DECREMENT, '--', self::GROUP_UNARY_OPERATOR, 2, true);

        $this->token(self::MULTIPLICATION, '*', self::GROUP_BINARY_OPERATOR, 3, false);
        $this->token(self::DIVISION, '/', self::GROUP_BINARY_OPERATOR, 3, false);
        $this->token(self::MODULUS, '%', self::GROUP_BINARY_OPERATOR, 3, false);

        $this->token(self::ADDITION, '+', self::GROUP_BINARY_OPERATOR, 4, false);
        $this->token(self::SUBTRACTION, '-', self::GROUP_BINARY_OPERATOR, 4, false);
        $this->token(self::STRING_CONCAT, '.', self::GROUP_BINARY_OPERATOR, 4, false);

        $this->token(self::SHIFT_LEFT, '<<', self::GROUP_BINARY_OPERATOR, 5, false);
        $this->token(self::SHIFT_RIGHT, '>>', self::GROUP_BINARY_OPERATOR, 5, false);

        $this->token(self::LESS_THAN_OR_EQUAL, '<=', self::GROUP_EQUALITY_OPERATOR, 6, false);
        $this->token(self::GREATER_THAN_OR_EQUAL, '>=', self::GROUP_EQUALITY_OPERATOR, 6, false);
        $this->token(self::LESS_THAN, '<', self::GROUP_EQUALITY_OPERATOR, 6, false);
        $this->token(self::GREATER_THAN, '>', self::GROUP_EQUALITY_OPERATOR, 6, false);

        $this->token(self::EQUAL, '==', self::GROUP_EQUALITY_OPERATOR, 7, false);
        $this->token(self::NOT_EQUAL, '!=', self::GROUP_EQUALITY_OPERATOR, 7, false);
        $this->token(self::IDENTICAL, '===', self::GROUP_EQUALITY_OPERATOR, 7, false);
        $this->token(self::NOT_IDENTICAL, '!==', self::GROUP_EQUALITY_OPERATOR, 7, false);

        $this->token(self::BITWISE_AND, '&', self::GROUP_BINARY_OPERATOR, 8, false);
        $this->token(self::BITWISE_XOR, '^', self::GROUP_BINARY_OPERATOR, 8, false);
        $this->token(self::BITWISE_OR, '|', self::GROUP_BINARY_OPERATOR, 8, false);

        $this->token(self::LOGIC_AND, '&&', self::GROUP_BINARY_OPERATOR, 11, false);
        $this->token(self::LOGIC_OR, '||', self::GROUP_BINARY_OPERATOR, 12, false);

        $this->token(self::TERNARY, '?', self::GROUP_BINARY_OPERATOR, 14, false);
        $this->token(self::TERNARY_SEPARATOR, ':', self::GROUP_BINARY_OPERATOR, 13, false);

        $this->token(self::ASSIGNMENT, '=', self::GROUP_ASSIGNMENT_OPERATOR, 15, true);
        $this->token(self::ADDITION_ASSIGNMENT, '+=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::SUBTRACTION_ASSIGNMENT, '-=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::MULTIPLICATION_ASSIGNMENT, '*=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::DIVISION_ASSIGNMENT, '/=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::CONCAT_ASSIGNMENT, '.=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::MODULUS_ASSIGNMENT, '%=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::BITWISE_OR_ASSIGNMENT, '|=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::BITWISE_XOR_ASSIGNMENT, '^=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::BITWISE_NOT_ASSIGNMENT, '~=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::SHIFT_LEFT_ASSIGNMENT, '<<=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
        $this->token(self::SHIFT_RIGHT_ASSIGMENT, '>>=', self::GROUP_ASSIGNMENT_OPERATOR, 15, false);
    }

    private function token(int $type, ?string $symbol, int $group, ?int $precedence, ?bool $rightAssociative)
    {
        $this->tokensBySymbol[$symbol] = [
            'type' => $type,
            'symbol' => $symbol,
            'group' => $group,
            'precedence' => $precedence,
            'rightAssociative' => $rightAssociative
        ];
        $this->tokensByType[$type] = [
            'type' => $type,
            'symbol' => $symbol,
            'group' => $group,
            'precedence' => $precedence,
            'rightAssociative' => $rightAssociative
        ];
    }

    /**
     * Identify by string
     *
     * @param string $needle
     *
     * @return array|null
     */
    public function identify(string $needle): ?int
    {
        if (isset($this->tokensBySymbol[$needle])) {
            return $this->tokensBySymbol[$needle]['type'];
        }

        return null;
    }

    /**
     * Get info about the token type.
     *
     * @param int $type
     *
     * @return int
     */
    public function info(int $type): array
    {
        return $this->tokensByType[$type];
    }
}
