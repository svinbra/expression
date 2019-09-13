<?php declare(strict_types=1);

namespace Test\Service\Expression;

use Expression\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testBasicArithmetic() {
        $poeExpression = new Expression("x=n + 1;");
        $variables = $poeExpression->eval(5);
        $this->assertEquals(6, $variables['x']);

        $poeExpression = new Expression("x=n - 1;");
        $variables = $poeExpression->eval(5);
        $this->assertEquals(4, $variables['x']);

        $poeExpression = new Expression("x=n / 2;");
        $variables = $poeExpression->eval(6);
        $this->assertEquals(3, $variables['x']);

        $poeExpression = new Expression("x=n * 2;");
        $variables = $poeExpression->eval(6);
        $this->assertEquals(12, $variables['x']);

        $poeExpression = new Expression("x=n % 10;");
        $variables = $poeExpression->eval(16);
        $this->assertEquals(6, $variables['x']);
    }

    public function testSimpleTernary()
    {
        $poeExpression = new Expression("x=n > 6 ? 1 : 2;");
        $variables = $poeExpression->eval(16);
        $this->assertEquals(1, $variables['x']);

        $poeExpression = new Expression("x=n < 6 ? 1 : 2;");
        $variables = $poeExpression->eval(16);
        $this->assertEquals(2, $variables['x']);
    }

    public function testParentheses()
    {
        $poeExpression = new Expression("x=2 * (2 + 3);");
        $variables = $poeExpression->eval(16);
        $this->assertEquals(10, $variables['x']);

        $poeExpression = new Expression("x=((2 + 3) * (2 + 3));");
        $variables = $poeExpression->eval(16);
        $this->assertEquals(25, $variables['x']);
    }

    public function testDoubleTernary()
    {
        $poeExpression = new Expression("x=(n==1 ? 10 : n > 2 ? 12 : 13);");
        $variables = $poeExpression->eval(2);
        $this->assertEquals(13, $variables['x']);
    }

    public function testPolishPlurals()
    {
        $poeExpression = new Expression("plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2);");
        $variables = $poeExpression->eval(0);
        $this->assertEquals(2, $variables['plural']);
        $variables = $poeExpression->eval(1);
        $this->assertEquals(0, $variables['plural']);
        $variables = $poeExpression->eval(2);
        $this->assertEquals(1, $variables['plural']);
        $variables = $poeExpression->eval(3);
        $this->assertEquals(1, $variables['plural']);
        $variables = $poeExpression->eval(4);
        $this->assertEquals(1, $variables['plural']);
        $variables = $poeExpression->eval(5);
        $this->assertEquals(2, $variables['plural']);
        $variables = $poeExpression->eval(6);
        $this->assertEquals(2, $variables['plural']);
        $variables = $poeExpression->eval(7);
        $this->assertEquals(2, $variables['plural']);
        $variables = $poeExpression->eval(22);
        $this->assertEquals(1, $variables['plural']);
    }
}
