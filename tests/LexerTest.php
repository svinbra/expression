<?php

namespace Test;

use Expression\CompilerException;
use Expression\Lexer;
use Expression\TokenType;
use PHPUnit\Framework\TestCase;

class LexerTest extends TestCase
{
    public function testEmptySource()
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage("Unexpected end of line");
        new Lexer("");
    }

    public function testEatAllWhitespaces()
    {
        $lexer = new Lexer(" \t \r  \n \t");
        $this->assertEquals(0, $lexer->charLeft());
    }

    public function testEndOfStatement()
    {
        $lexer = new Lexer(";");
        $this->assertEquals(TokenType::END_OF_STATEMENT, $lexer->eatToken()->type());
    }

    public function testInvalidVariable()
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage("Invalid variable name");
        new Lexer('$1name');
    }

    public function testValidVariable()
    {
        $lexer = new Lexer('$name');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::VARIABLE, $token->type());
        $this->assertEquals('$name', $token->value);
    }

    public function testInvalidNumber()
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage("Invalid floating point number");
        new Lexer('.4.6');
    }

    public function testNumberWithACharacter()
    {
        $this->expectException(CompilerException::class);
        $this->expectExceptionMessage("Syntax error");
        new Lexer('.4a6');
    }

    public function testValidNumber()
    {
        $lexer = new Lexer('5');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::NUMBER, $token->type());
        $this->assertEquals(5, $token->value);

        $lexer = new Lexer('.5');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::NUMBER, $token->type());
        $this->assertEquals(.5, $token->value);

        $lexer = new Lexer('5.5');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::NUMBER, $token->type());
        $this->assertEquals(5.5, $token->value);
    }

    public function testValidSingleQuoteString()
    {
        $lexer = new Lexer("'hello'");
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::SINGLE_QUOTED_STRING, $token->type());
        $this->assertEquals("hello", $token->value);
    }

    public function testValidDoubleQuoteString()
    {
        $lexer = new Lexer('"hello"');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::DOUBLE_QUOTED_STRING, $token->type());
        $this->assertEquals("hello", $token->value);
    }

    public function testPlusOperator()
    {
        $lexer = new Lexer("+");
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::ADDITION, $token->type());

        $lexer = new Lexer('$n++');
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::VARIABLE, $token->type());
        $token = $lexer->eatToken();
        $this->assertEquals(TokenType::UNARY_INCREMENT, $token->type());
    }
}
