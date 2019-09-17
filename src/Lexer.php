<?php declare(strict_types=1);

namespace Expression;

class Lexer
{
    /** @var string */
    private $source;
    /** @var int */
    private $pos;
    /** @var int */
    private $line;
    /** @var int */
    private $column;
    /** @var \Expression\TokenType */
    private $tokenType;
    /** @var \Expression\Stack */
    private $tokens;
    /** @var int */
    private $tokenIndex;
    /** @var Token */
    private $previousToken;

    /**
     * Lexer constructor.
     *
     * @param string $source Expects full source in a string
     *
     * @throws \Expression\CompilerException
     */
    public function __construct(string $source)
    {
        $this->source = $source;
        $this->pos = 0;
        $this->line = 1;
        $this->column = 1;
        $this->tokenType = new TokenType();
        $this->tokenIndex = 0;
        $this->tokens = [];
        $this->previousToken = null;

        if (strlen($this->source) === 0) {
            throw new CompilerException(CompilerException::ERROR, 'Unexpected end of line',
                new Location($this->line, $this->column)
            );
        }

        $this->source .= "\0";

        for (; ;) {
            $char = $this->peekAtChar();
            if ($this->isWhiteSpace($char)) {
                $this->eatAllWhitespaces($char);

            } elseif ($this->isNumber($char)) {
                $this->addToken($this->lexNumber($char));

            } elseif ($this->isSingleQuote($char)) {
                $this->addToken($this->lexSingleQuoteString($char));;

            } elseif ($this->isDoubleQuote($char)) {
                $this->addToken($this->lexDoubleQuoteString($char));;

            } elseif ($this->isVariable($char)) {
                $this->addToken($this->lexVariable($char));

            } elseif ($this->isOperator($char)) {
                $this->addToken($this->lexOperator($char));

            } elseif ($this->isEndOfStatement($char)) {
                $this->addToken(new Token(TokenType::END_OF_STATEMENT, new Location($this->line, $this->column), $char));
                $this->eatTheChar();

            } elseif ($char === "\0") {
                break;

            } else {
                $this->error("Syntax error", new Location($this->line, $this->column), $char);
            }
        }
    }

    private function addToken(Token $token)
    {
        $this->tokens[] = $token;
        $this->previousToken = $token;
    }

    private function peekAtChar(): string
    {
        return $this->source[$this->pos];
    }

    private function peekNextChar(): string
    {
        if ($this->charLeft() > 0) {
            return $this->source[$this->pos + 1];
        } else {
            return '';
        }
    }

    private function eatTheChar(): string
    {
        $this->pos++;
        $this->column++;
        $char = $this->source[$this->pos];

        return $char;
    }

    private function eatAllWhitespaces(string $char)
    {
        do {
            if ($this->isNewLine($char)) {
                $this->column = 1;
                $this->line++;
            }
            $char = $this->eatTheChar();
        } while ($this->isWhiteSpace($char));
    }

    public function haveTokens(): bool
    {
        return (count($this->tokens) - $this->tokenIndex) > 0;
    }

    public function expect(int $type): bool
    {
        $token = $this->tokens[$this->tokenIndex];
        return isset($token) && $token->type() === $type;
    }

    public function eatToken(): Token
    {
        return $this->tokens[$this->tokenIndex++];
    }

    public function peekToken(): Token
    {
       return $this->tokens[$this->tokenIndex];
    }

    public function charLeft(): int
    {
        return strlen($this->source) - $this->pos - 1;
    }

    private function isWhiteSpace(string $char): bool
    {
        return $char === ' ' || $char === "\t" || $char === "\r" || $this->isNewLine($char);
    }

    private function isNewLine(string $char): bool
    {
        return $char === "\n";
    }

    private function isEndOfStatement(string $char): bool
    {
        return $char === ';';
    }

    private function isVariable(string $char): bool
    {
        return $char === '$';
    }

    private function isNumber(string $char): bool
    {
        if ($char === '.') {
            if (!ctype_digit($this->peekNextChar())) {
                return false;
            }
        }
        return ctype_digit($char) || $char === '.';
    }

    private function isSingleQuote(string $char): bool
    {
        return ($char === "'");
    }

    private function isDoubleQuote(string $char): bool
    {
        return ($char === '"');
    }

    private function isOperator(string $char): bool
    {
        return strpos("+-/*&?:!=&|", $char) !== false;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function lexNumber(string $char): Token
    {
        $location = new Location($this->line, $this->column);

        $number = '';
        $float = false;
        $decimal = false;
        do {
            if ($char === '.') {
                if ($decimal) {
                    $this->error("Invalid floating point number", $location);
                }
                $float = true;
                $decimal = true;
            }
            $number .= $char;
            $char = $this->eatTheChar();
        } while ($this->isNumber($char));

        if ($float) {
            $number = floatval($number);
        } else {
            $number = intval($number);
        }

        $token = new Token(TokenType::NUMBER, $location, $number);
        return $token;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function lexSingleQuoteString(string $char): Token
    {
        $location = new Location($this->line, $this->column);
        $string = '';

        $char = $this->eatTheChar();
        while ($this->charLeft() > 0 && !$this->isSingleQuote($char) && !$this->isNewLine($char)) {
            $string .= $char;
            $char = $this->eatTheChar();
        }

        if (!$this->isSingleQuote($char)) {
            $this->error("Illegal line end in string literal", $location);
        }
        $this->eatTheChar();

        $token = new Token(TokenType::SINGLE_QUOTED_STRING, $location, $string);
        return $token;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function lexDoubleQuoteString(string $char): Token
    {
        $location = new Location($this->line, $this->column);
        $string = '';

        $char = $this->eatTheChar();
        while ($this->charLeft() > 0 && !$this->isDoubleQuote($char) && !$this->isNewLine($char)) {
            $string .= $char;
            $char = $this->eatTheChar();
        }

        if (!$this->isDoubleQuote($char)) {
            $this->error("Illegal line end in string literal", $location);
        }
        $this->eatTheChar();

        $token = new Token(TokenType::DOUBLE_QUOTED_STRING, $location, $string);
        return $token;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function lexVariable(string $char): Token
    {
        $location = new Location($this->line, $this->column);

        $identifier = '';
        do {
            $this->eatTheChar();
            $identifier .= $char;
            $char = $this->peekAtChar();
        } while (ctype_alnum($char));

        if (strlen($identifier) < 2 || !ctype_alpha($identifier[1])) {
            $this->error("Invalid variable name", $location, $identifier);
        }

        $token = new Token(TokenType::VARIABLE, $location, $identifier);
        return $token;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function lexOperator($char): ?Token
    {
        $location = new Location($this->line, $this->column);

        $operator = '';
        do {
            $this->eatTheChar();
            $operator .= $char;
            $char = $this->peekAtChar();
        } while($this->isOperator($char));

        $type = $this->tokenType->identify($operator);
        if ($type) {
            $token = new Token($type, $location, $operator);
            return $token;
        } else {
            $this->error("Unknown operator", $location, $operator);
        }

        return null;
    }

    /**
     * @throws \Expression\CompilerException
     */
    private function error(string $message, Location $location, string $value = null)
    {
        if ($value) {
            throw new CompilerException(CompilerException::ERROR, $message .
                " at ($location->line, $location->column): unexpected '" . $value . "'", $location);
        } else {
            throw new CompilerException(CompilerException::ERROR, $message .
                " at ($location->line, $location->column)", $location);
        }
    }
}
